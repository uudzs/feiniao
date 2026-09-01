# 创建虚拟环境并激活
# python3 -m venv venv
# source venv/bin/activate
# pip install edge-tts fastapi uvicorn
# uvicorn tts_server:app --host 0.0.0.0 --port 25008 --reload 修改代码后自动重启（开发用） --reload
# nohup uvicorn tts_server:app --host 0.0.0.0 --port 25008 > tts_server.log 2>&1 & 正式后台运行建议去掉 --reload

import io
import time
import hmac
import hashlib
import base64
import logging
import json
from collections import defaultdict
from datetime import datetime
from urllib.parse import urlencode

import edge_tts
from fastapi import FastAPI, Query, HTTPException, Request, Body
from fastapi.responses import StreamingResponse, JSONResponse
from starlette.middleware.base import BaseHTTPMiddleware
from pydantic import BaseModel

# ==================== 配置 ====================
# 防伪 Token 密钥（生产环境应从环境变量读取）
SECRET_KEY = "your-secret-key-change-me-in-production"
# Token 有效期（秒），默认 300 秒 = 5 分钟
TOKEN_EXPIRE_SECONDS = 300
# 是否开启 Token 验证
ENABLE_TOKEN_AUTH = True
# 速率限制：每个 IP 每秒最大请求数
RATE_LIMIT_PER_SECOND = 5
# 速率限制：每个 IP 每分钟最大请求数
RATE_LIMIT_PER_MINUTE = 60

# ==================== 日志 ====================
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)s | %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("tts_server")

# ==================== 支持的音色列表 ====================
VOICE_OPTIONS = {
    # 中文普通话 - 女声
    "zh-CN-XiaoxiaoNeural": "晓晓(女·温柔)",
    "zh-CN-XiaoyiNeural": "晓伊(女·活泼)",
    "zh-CN-XiaochenNeural": "晓辰(女·自然)",
    "zh-CN-XiaohanNeural": "晓涵(女·温柔)",
    "zh-CN-XiaoxuanNeural": "晓萱(女·自信)",
    "zh-CN-XiaomengNeural": "晓梦(女·可爱)",
    "zh-CN-XiaoruiNeural": "晓睿(女·理性)",
    "zh-CN-XiaoshuangNeural": "晓双(女·甜美)",
    # 中文普通话 - 男声
    "zh-CN-YunxiNeural": "云希(男·少年)",
    "zh-CN-YunjianNeural": "云健(男·运动)",
    "zh-CN-YunyangNeural": "云扬(男·新闻)",
    "zh-CN-YunxiaNeural": "云夏(男·青年)",
    "zh-CN-YunyeNeural": "云野(男·成熟)",
    # 中文粤语
    "zh-HK-HiuMaanNeural": "晓曼(粤·女)",
    "zh-HK-HiuGaaiNeural": "晓佳(粤·女)",
    "zh-HK-WanLungNeural": "云龙(粤·男)",
    # 中文台湾
    "zh-TW-HsiaoChenNeural": "晓臻(台·女)",
    "zh-TW-HsiaoYuNeural": "晓雨(台·女)",
    "zh-TW-YunJheNeural": "云哲(台·男)",
    # 英语
    "en-US-JennyNeural": "Jenny(美·女)",
    "en-US-AriaNeural": "Aria(美·女)",
    "en-US-GuyNeural": "Guy(美·男)",
    "en-US-DavisNeural": "Davis(美·男)",
    "en-GB-SoniaNeural": "Sonia(英·女)",
    "en-GB-RyanNeural": "Ryan(英·男)",
    "en-GB-LibbyNeural": "Libby(英·女)",
    # 日语
    "ja-JP-NanamiNeural": "Nanami(日·女)",
    "ja-JP-KeitaNeural": "Keita(日·男)",
    "ja-JP-AoiNeural": "Aoi(日·女)",
    # 韩语
    "ko-KR-SunHiNeural": "SunHi(韩·女)",
    "ko-KR-InJoonNeural": "InJoon(韩·男)",
    # 菲律宾语
    "fil-PH-BlessicaNeural": "Blessica(菲·女)",
    "fil-PH-AngeloNeural": "Angelo(菲·男)",
    # 印度尼西亚语
    "id-ID-ArdiNeural": "Ardi(印尼·男)",
    "id-ID-GadisNeural": "Gadis(印尼·女)",
}

# 语速映射 (前端值 -> edge-tts rate)
SPEED_OPTIONS = {
    "x-slow": "-50%",
    "slow": "-25%",
    "normal": "+0%",
    "fast": "+25%",
    "x-fast": "+50%",
}

# 音调映射 (前端值 -> edge-tts pitch)
PITCH_OPTIONS = {
    "x-low": "-20Hz",
    "low": "-10Hz",
    "normal": "+0Hz",
    "high": "+10Hz",
    "x-high": "+20Hz",
}

# 语言组（用于快速查找某语言的默认音色）
LANG_DEFAULTS = {
    "zh": "zh-CN-XiaoxiaoNeural",
    "zh-CN": "zh-CN-XiaoxiaoNeural",
    "zh-HK": "zh-HK-HiuMaanNeural",
    "zh-TW": "zh-TW-HsiaoChenNeural",
    "en": "en-US-JennyNeural",
    "en-US": "en-US-JennyNeural",
    "en-GB": "en-GB-SoniaNeural",
    "ja": "ja-JP-NanamiNeural",
    "ko": "ko-KR-SunHiNeural",
    "fil": "fil-PH-BlessicaNeural",
    "id": "id-ID-GadisNeural",
    "id-ID": "id-ID-GadisNeural",
}

# 文本长度限制
MAX_TEXT_LENGTH = 3000
# POST 接口允许的最大文本长度（用于长文本场景）
MAX_TEXT_LENGTH_POST = 50000

app = FastAPI(
    title="Edge-TTS 文本转语音服务",
    description="基于 Microsoft Edge TTS 的多音色、多语言文本转语音 API",
    version="2.0.0",
)


# ==================== 工具函数 ====================

def generate_token(text: str, voice: str, rate: str, pitch: str, expire: int = TOKEN_EXPIRE_SECONDS) -> str:
    """
    生成 HMAC 防伪 Token。
    调用方用同样的 SECRET_KEY 生成 token，服务端验证一致性。
    使用 JSON 序列化 payload，避免文本中含分隔符导致验证失败。
    """
    expire_time = int(time.time()) + expire
    payload = json.dumps({
        "text": text,
        "voice": voice,
        "rate": rate,
        "pitch": pitch,
        "expire": expire_time,
    }, ensure_ascii=False)
    signature = hmac.new(SECRET_KEY.encode(), payload.encode(), hashlib.sha256).hexdigest()
    raw = f"{base64.urlsafe_b64encode(payload.encode()).decode()}.{signature}"
    return raw


def verify_token(token: str) -> dict | None:
    """验证 Token 并返回解码后的 payload 字段。验证失败返回 None。"""
    try:
        raw, signature = token.rsplit(".", 1)
        payload = base64.urlsafe_b64decode(raw.encode()).decode()
        data = json.loads(payload)
        if not isinstance(data, dict):
            return None
        expire_time = int(data.get("expire", 0))
        if time.time() > expire_time:
            return None
        expected_sig = hmac.new(
            SECRET_KEY.encode(), payload.encode(), hashlib.sha256
        ).hexdigest()
        if not hmac.compare_digest(signature, expected_sig):
            return None
        return {
            "text": data.get("text", ""),
            "voice": data.get("voice", ""),
            "rate": data.get("rate", ""),
            "pitch": data.get("pitch", ""),
        }
    except Exception:
        return None


def validate_voice(voice: str) -> str:
    """验证并返回有效音色名称，无效则使用默认值。"""
    if voice in VOICE_OPTIONS:
        return voice
    return "zh-CN-XiaoxiaoNeural"


def validate_rate(rate: str) -> str:
    """验证并返回有效语速值。"""
    if rate in SPEED_OPTIONS:
        return SPEED_OPTIONS[rate]
    # 也支持直接传 edge-tts 格式的 rate
    if rate.endswith("%") and rate[:-1].lstrip("-+").isdigit():
        return rate
    return "+0%"


def validate_pitch(pitch: str) -> str:
    """验证并返回有效音调值。"""
    if pitch in PITCH_OPTIONS:
        return PITCH_OPTIONS[pitch]
    if (pitch.endswith("Hz") and pitch[:-2].lstrip("-+").isdigit()) or \
       (pitch.endswith("Hz") and pitch[:-2].lstrip("-+").replace(".", "", 1).isdigit()):
        return pitch
    return "+0Hz"


def resolve_voice_by_lang(lang: str) -> str:
    """根据语言代码解析默认音色。"""
    return LANG_DEFAULTS.get(lang, "zh-CN-XiaoxiaoNeural")


# ==================== 速率限制中间件 ====================

class RateLimitMiddleware(BaseHTTPMiddleware):
    def __init__(self, app, per_second: int = 5, per_minute: int = 60):
        super().__init__(app)
        self.per_second = per_second
        self.per_minute = per_minute
        self.requests: dict[str, list[float]] = defaultdict(list)

    async def dispatch(self, request: Request, call_next):
        # 跳过非 TTS 路由
        if not request.url.path.startswith("/tts"):
            return await call_next(request)

        client_ip = request.client.host
        now = time.time()

        # 清理旧记录
        self.requests[client_ip] = [
            t for t in self.requests[client_ip] if now - t < 60
        ]

        # 检查秒级限制
        recent = [t for t in self.requests[client_ip] if now - t < 1]
        if len(recent) >= self.per_second:
            logger.warning(f"速率限制触发: IP={client_ip}, 每秒请求数={len(recent)}")
            return JSONResponse(
                status_code=429,
                content={"error": "请求过于频繁，请稍后重试", "retry_after": 1},
            )

        # 检查分钟级限制
        minute_requests = [t for t in self.requests[client_ip] if now - t < 60]
        if len(minute_requests) >= self.per_minute:
            logger.warning(f"速率限制触发: IP={client_ip}, 每分钟请求数={len(minute_requests)}")
            return JSONResponse(
                status_code=429,
                content={"error": "请求超过每分钟限制，请稍后重试", "retry_after": 60},
            )

        self.requests[client_ip].append(now)
        return await call_next(request)


app.add_middleware(RateLimitMiddleware, per_second=RATE_LIMIT_PER_SECOND, per_minute=RATE_LIMIT_PER_MINUTE)


# ==================== 请求模型 ====================

class TtsPostRequest(BaseModel):
    text: str
    voice: str = "zh-CN-XiaoxiaoNeural"
    rate: str = "normal"
    pitch: str = "normal"
    lang: str | None = None
    token: str | None = None
    response_format: str = "mp3"


# ==================== 核心 TTS 生成逻辑 ====================

async def _generate_tts_audio(text: str, voice: str, rate: str, pitch: str, response_format: str):
    """核心 TTS 生成函数，供 GET 和 POST 接口共用"""
    text = text.strip()
    if not text:
        raise HTTPException(status_code=400, detail="text 参数不能为空")

    voice = validate_voice(voice)
    rate_value = validate_rate(rate)
    pitch_value = validate_pitch(pitch)

    format_map = {
        "mp3": "audio-48khz-96kbitrate-mono-mp3",
        "wav": "riff-24khz-16bit-mono-pcm",
        "ogg": "ogg-48khz-192kbitrate-opus",
        "webm": "webm-24khz-16bit-mono-opus",
    }
    output_format = format_map.get(response_format, format_map["mp3"])
    content_type_map = {
        "mp3": "audio/mpeg",
        "wav": "audio/wav",
        "ogg": "audio/ogg",
        "webm": "audio/webm",
    }

    logger.info(
        f"TTS请求 | 文本长度={len(text)} | 音色={voice} | 语速={rate_value} | 音调={pitch_value} | 格式={response_format}"
    )

    communicate = edge_tts.Communicate(
        text=text,
        voice=voice,
        rate=rate_value,
        pitch=pitch_value,
    )

    async def audio_stream():
        async for chunk in communicate.stream():
            if chunk["type"] == "audio":
                yield chunk["data"]
            elif chunk["type"] == "WordBoundary":
                pass

    return StreamingResponse(
        audio_stream(),
        media_type=content_type_map.get(response_format, "audio/mpeg"),
        headers={
            "X-TTS-Voice": voice,
            "X-TTS-Rate": rate_value,
            "X-TTS-Pitch": pitch_value,
            "X-TTS-Text-Length": str(len(text)),
        },
    )


# ==================== API 路由 ====================

@app.get("/tts")
async def tts(
    text: str = Query(..., description="要转换的文本内容"),
    voice: str = Query("zh-CN-XiaoxiaoNeural", description="音色名称"),
    rate: str = Query("normal", description="语速: x-slow/slow/normal/fast/x-fast 或直接传百分比如 -20%"),
    pitch: str = Query("normal", description="音调: x-low/low/normal/high/x-high 或直接传 Hz 值如 -5Hz"),
    lang: str = Query(None, description="语言代码，自动匹配默认音色（优先级低于 voice）"),
    token: str = Query(None, description="防伪 Token（开启验证时必传）"),
    response_format: str = Query("mp3", description="输出格式: mp3 / wav / ogg / webm"),
):
    """
    文本转语音接口（GET 方式，适用于短文本）。

    使用示例:
        /tts?text=你好世界&voice=zh-CN-XiaoxiaoNeural&rate=normal&pitch=normal
        /tts?text=Hello&lang=en&rate=slow&pitch=high
    """
    # --- Token 验证 ---
    if ENABLE_TOKEN_AUTH:
        if not token:
            raise HTTPException(status_code=401, detail="缺少 token 参数，请先生成 Token")
        payload = verify_token(token)
        if payload is None:
            raise HTTPException(status_code=403, detail="Token 无效或已过期")

    # --- 参数验证 ---
    if len(text.strip()) > MAX_TEXT_LENGTH:
        raise HTTPException(
            status_code=400,
            detail=f"文本长度不能超过 {MAX_TEXT_LENGTH} 个字符，当前 {len(text)} 个，请使用 POST /tts 接口",
        )

    # --- 音色解析 ---
    if lang and voice == "zh-CN-XiaoxiaoNeural":
        voice = resolve_voice_by_lang(lang)

    return await _generate_tts_audio(text, voice, rate, pitch, response_format)


@app.post("/tts")
async def tts_post(body: TtsPostRequest):
    """
    文本转语音接口（POST 方式，适用于长文本）。

    使用示例:
        POST /tts
        {
            "text": "长文本内容...",
            "voice": "id-ID-ArdiNeural",
            "rate": "normal",
            "pitch": "normal"
        }
    """
    # --- Token 验证 ---
    if ENABLE_TOKEN_AUTH:
        if not body.token:
            raise HTTPException(status_code=401, detail="缺少 token 参数，请先生成 Token")
        payload = verify_token(body.token)
        if payload is None:
            raise HTTPException(status_code=403, detail="Token 无效或已过期")

    # --- 参数验证 ---
    if len(body.text.strip()) > MAX_TEXT_LENGTH_POST:
        raise HTTPException(
            status_code=400,
            detail=f"文本长度不能超过 {MAX_TEXT_LENGTH_POST} 个字符，当前 {len(body.text)} 个",
        )

    # --- 音色解析 ---
    voice = body.voice
    if body.lang and voice == "zh-CN-XiaoxiaoNeural":
        voice = resolve_voice_by_lang(body.lang)

    return await _generate_tts_audio(body.text, voice, body.rate, body.pitch, body.response_format)


@app.get("/voices")
async def list_voices():
    """获取所有支持的音色列表。"""
    voices = []
    for voice_id, desc in VOICE_OPTIONS.items():
        lang_part = voice_id.split("-")[0] + "-" + voice_id.split("-")[1] if "-" in voice_id else voice_id
        voices.append({
            "id": voice_id,
            "name": desc,
            "locale": lang_part,
            "gender": "Female" if any(kw in desc for kw in ["女", "Female"]) else "Male",
        })
    return JSONResponse({
        "count": len(voices),
        "voices": voices,
        "default_voice": "zh-CN-XiaoxiaoNeural",
        "speed_options": list(SPEED_OPTIONS.keys()),
        "pitch_options": list(PITCH_OPTIONS.keys()),
        "format_options": ["mp3", "wav", "ogg", "webm"],
        "lang_defaults": LANG_DEFAULTS,
    })


@app.get("/token")
async def get_token(
    text: str = Query(..., description="要转换的文本"),
    voice: str = Query("zh-CN-XiaoxiaoNeural", description="音色"),
    rate: str = Query("normal", description="语速"),
    pitch: str = Query("normal", description="音调"),
    expire: int = Query(TOKEN_EXPIRE_SECONDS, description="Token 有效期（秒）"),
):
    """
    生成防伪 Token（GET 方式，适用于短文本）。
    调用方先请求此接口获取 token，再用 token 调用 /tts 接口。
    """
    voice = validate_voice(voice)
    rate_value = validate_rate(rate)
    pitch_value = validate_pitch(pitch)
    token = generate_token(text, voice, rate_value, pitch_value, expire)
    return JSONResponse({
        "token": token,
        "expires_in": expire,
        "generated_at": datetime.now().isoformat(),
        "params": {
            "text": text[:50] + "..." if len(text) > 50 else text,
            "voice": voice,
            "rate": rate_value,
            "pitch": pitch_value,
        },
    })


class TokenPostRequest(BaseModel):
    text: str
    voice: str = "zh-CN-XiaoxiaoNeural"
    rate: str = "normal"
    pitch: str = "normal"
    expire: int = TOKEN_EXPIRE_SECONDS


@app.post("/token")
async def get_token_post(body: TokenPostRequest):
    """
    生成防伪 Token（POST 方式，适用于长文本）。
    """
    voice = validate_voice(body.voice)
    rate_value = validate_rate(body.rate)
    pitch_value = validate_pitch(body.pitch)
    token = generate_token(body.text, voice, rate_value, pitch_value, body.expire)
    return JSONResponse({
        "token": token,
        "expires_in": body.expire,
        "generated_at": datetime.now().isoformat(),
        "params": {
            "text": body.text[:50] + "..." if len(body.text) > 50 else body.text,
            "voice": voice,
            "rate": rate_value,
            "pitch": pitch_value,
        },
    })


@app.get("/")
async def root():
    """API 文档首页"""
    return JSONResponse({
        "service": "Edge-TTS 文本转语音服务",
        "version": "2.0.0",
        "endpoints": {
            "/tts": "GET - 文本转语音（核心接口）",
            "/voices": "GET - 获取支持的音色列表",
            "/token": "GET - 生成防伪 Token",
            "/docs": "GET - Swagger API 文档",
        },
        "example": "/tts?text=你好世界&voice=zh-CN-XiaoxiaoNeural&rate=normal&pitch=normal&lang=zh",
        "token_auth_enabled": ENABLE_TOKEN_AUTH,
    })


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=25008, reload=False)
