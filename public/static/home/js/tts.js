!(function (document, window) {
    "use strict";

    const DEFAULT_CONFIG = {
        ttsvoice: '',      // 音色 ID（与 tts_config.voice_options 的 value 对应）
        ttsrate: 'normal', // 语速：x-slow/slow/normal/fast/x-fast
        ttspitch: 'normal',// 音调：x-low/low/normal/high/x-high
        ttsautoread: 0     // 是否自动朗读：0/1
    };

    // DOM 元素缓存
    const elements = {
        contentElement: TTSChapterContainer,
        ttsStartBtn: $("#ttsstart"),
        ttsStopBtn: $("#ttsstop"),
        ttsRateSelect: $("#tts-rate"),
        ttsVoicesSelect: $("#tts-voices"),
        ttsPitchSelect: $("#tts-pitch"),
        ttsAutoReadInput: $("#tts-read")
    };

    // 使用后端下发的 Edge TTS 配置替换模板中可能遗留的旧选项。
    function renderSelectOptions($select, options, selectedValue) {
        if (!$select.length || !Array.isArray(options)) return;
        $select.empty();
        options.forEach(function (option) {
            const $option = $("<option></option>")
                .attr("value", option.value)
                .text(option.label);
            if (option.value === selectedValue) {
                $option.prop("selected", true);
            }
            $select.append($option);
        });
    }

    function applyServerConfig() {
        const config = window.TTSConfig;
        if (!config) return;
        renderSelectOptions(elements.ttsVoicesSelect, config.voice_options, config.default_voice);
        renderSelectOptions(elements.ttsRateSelect, config.speed_options, config.default_speed);
        renderSelectOptions(elements.ttsPitchSelect, config.pitch_options, config.default_pitch);
    }

    function showMessage(message) {
        if (window.layer && typeof window.layer.msg === "function") {
            window.layer.msg(message);
        } else if (window.bui && typeof window.bui.hint === "function") {
            window.bui.hint(message);
        } else if (window.console && typeof window.console.warn === "function") {
            window.console.warn(message);
        }
    }

    function ensureSharedStyles() {
        if (document.getElementById("feiniao-tts-shared-style")) return;
        const style = document.createElement("style");
        style.id = "feiniao-tts-shared-style";
        style.textContent = [
            "@keyframes feiniao-tts-spin{to{transform:rotate(360deg)}}",
            "#ttsstart.tts-loading{position:relative;color:transparent!important;pointer-events:none}",
            "#ttsstart.tts-loading:after{content:'';position:absolute;left:50%;top:50%;width:18px;height:18px;margin:-10px 0 0 -10px;border:2px solid rgba(255,255,255,.45);border-top-color:#fff;border-radius:50%;animation:feiniao-tts-spin .7s linear infinite}",
            ".tts-container.tts-requesting select,.tts-container.tts-requesting input{opacity:.55;pointer-events:none}",
            ".tts-highlight{background:rgba(72,149,239,.3)!important;box-shadow:0 0 12px rgba(72,149,239,.35);transition:background .25s ease,box-shadow .25s ease}",
            ".tts-spoken{opacity:.82;transition:opacity .25s ease}"
        ].join("");
        document.head.appendChild(style);
    }

    // 配置管理
    let userConfig = null;

    // 音频播放对象
    let audioElement = null;
    // 段落数据
    let paragraphs = [];
    let paragraphTimings = [];
    let currentParagraphIndex = -1;
    let totalDuration = 0;
    // 是否正在朗读
    let isReading = false;
    let isLoading = false;
    // 是否正在请求/播放当前段（用于防止重复推进）
    let isBusy = false;
    // 是否主动停止（避免触发自动下一段）
    let isManualStop = false;
    // 当前正在请求的 AbortController（用于中断）
    let currentAbortController = null;
    // 请求超时定时器
    let requestTimeoutTimer = null;
    // 整章合成可能耗时较长，超时需大于 PHP 代理的等待时间。
    const REQUEST_TIMEOUT_MS = 190000;

    // 持久化配置（cookie 中存储）
    function persistConfig() {
        const configValues = [
            userConfig.ttsvoice,
            userConfig.ttsrate,
            userConfig.ttspitch,
            userConfig.ttsautoread
        ].join(",");
        feiniaoSetCookie(configValues);
    }

    // 解析配置
    function parseConfig(rawConfig) {
        if (!rawConfig) return null;
        let parts = rawConfig.split(",");
        if (parts.length < 4) {
            const decodedStr = decodeURIComponent(rawConfig);
            parts = decodedStr.split(",");
        }
        return {
            ttsvoice: parts[0] || DEFAULT_CONFIG.ttsvoice,
            ttsrate: parts[1] || DEFAULT_CONFIG.ttsrate,
            ttspitch: parts[2] || DEFAULT_CONFIG.ttspitch,
            ttsautoread: parseInt(parts[3]) || DEFAULT_CONFIG.ttsautoread,
        };
    }

    // 初始化音频元素
    function initAudio() {
        if (audioElement) return;
        audioElement = new Audio();
        audioElement.preload = "auto";
        // 整章播放结束
        audioElement.addEventListener("ended", onAudioEnded);
        audioElement.addEventListener("loadedmetadata", onAudioMetadataLoaded);
        audioElement.addEventListener("timeupdate", onAudioTimeUpdate);
        // 错误处理
        audioElement.addEventListener("error", onAudioError);
    }

    // 标记是否正在切换 src（避免 load()/清空 src 触发的 error 事件）
    let isSwitchingSrc = false;

    // 准备段落内容用于高亮显示
    function prepareParagraphs() {
        paragraphs = [];
        const contentElements = $(elements.contentElement).last().find('p, h2, h3, h4');
        if (contentElements.length === 0) {
            return;
        }
        contentElements.each(function (index) {
            const $element = $(this);
            const text = $element.text().trim();
            if (text) {
                paragraphs.push({
                    element: $element,
                    text: text,
                    index: index
                });
            }
        });
    }

    function getFullChapterText() {
        return paragraphs.map(function (paragraph) {
            return paragraph.text;
        }).join("\n");
    }

    function estimateParagraphTimings(duration) {
        const totalChars = paragraphs.reduce(function (total, paragraph) {
            return total + paragraph.text.length;
        }, 0);
        paragraphTimings = [];
        totalDuration = duration;
        if (!totalChars || !duration) return;

        let elapsed = 0;
        paragraphs.forEach(function (paragraph, index) {
            const paragraphDuration = duration * paragraph.text.length / totalChars;
            paragraphTimings.push({
                index: index,
                start: elapsed,
                end: elapsed + paragraphDuration
            });
            elapsed += paragraphDuration;
        });
    }

    function findParagraphIndex(currentTime) {
        for (let index = 0; index < paragraphTimings.length; index++) {
            const timing = paragraphTimings[index];
            if (currentTime >= timing.start && currentTime < timing.end) {
                return timing.index;
            }
        }
        return paragraphTimings.length ? paragraphTimings.length - 1 : -1;
    }

    function scrollParagraphIntoView($paragraph) {
        const element = $paragraph[0];
        if (!element) return;
        if (typeof TTSChapterScrollContainer !== "undefined" && TTSChapterScrollContainer && TTSChapterScrollContainer.length) {
            const container = TTSChapterScrollContainer[0];
            const containerRect = container.getBoundingClientRect();
            const paragraphRect = element.getBoundingClientRect();
            if (paragraphRect.top < containerRect.top || paragraphRect.bottom > containerRect.bottom) {
                TTSChapterScrollContainer.animate({
                    scrollTop: container.scrollTop + paragraphRect.top - containerRect.top - 100
                }, 300);
            }
        } else {
            const rect = element.getBoundingClientRect();
            if (rect.top < 0 || rect.bottom > window.innerHeight) {
                element.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        }
    }

    function highlightParagraph(index) {
        if (index === currentParagraphIndex || !paragraphs[index]) return;
        paragraphs.forEach(function (paragraph, paragraphIndex) {
            paragraph.element
                .toggleClass("tts-highlight", paragraphIndex === index)
                .toggleClass("tts-spoken", paragraphIndex < index);
        });
        currentParagraphIndex = index;
        scrollParagraphIntoView(paragraphs[index].element);
    }

    function onAudioMetadataLoaded() {
        const duration = Number(audioElement.duration);
        const fallbackDuration = getFullChapterText().length * 0.25;
        estimateParagraphTimings(isFinite(duration) && duration > 0 ? duration : fallbackDuration);
    }

    function onAudioTimeUpdate() {
        if (!isReading || !paragraphTimings.length) return;
        highlightParagraph(findParagraphIndex(audioElement.currentTime));
    }

    function setLoading(loading) {
        isLoading = loading;
        elements.ttsStartBtn.toggleClass("tts-loading", loading).attr("aria-busy", loading ? "true" : "false");
        elements.ttsStartBtn.closest(".tts-container").toggleClass("tts-requesting", loading);
        updateTTSButtons();
    }

    // 更新 TTS 按钮状态
    function updateTTSButtons() {
        const isSpeaking = isReading || isLoading;
        elements.ttsStartBtn.prop("disabled", isSpeaking);
        elements.ttsStopBtn.prop("disabled", !isSpeaking);
    }

    // 请求 TTS 音频并播放
    function fetchAndPlay(text, voice, rate, pitch) {
        // 中断前一个请求
        abortCurrentRequest();

        if (typeof AbortController !== "undefined") {
            currentAbortController = new AbortController();
        }

        const body = JSON.stringify({
            text: text,
            voice: voice,
            rate: rate,
            pitch: pitch
        });

        const signal = currentAbortController ? currentAbortController.signal : undefined;

        // 设置请求超时定时器，超时则中断并 reject
        requestTimeoutTimer = setTimeout(function () {
            abortCurrentRequest();
        }, REQUEST_TIMEOUT_MS);

        return fetch(TTS_PROXY_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "audio/*"
            },
            body: body,
            signal: signal
        }).then(function (response) {
            if (!response.ok) {
                return response.text().then(function (txt) {
                    throw new Error("HTTP " + response.status + (txt ? (": " + txt) : ""));
                });
            }
            // 检查响应类型，避免把 JSON 错误信息当成音频
            const respType = response.headers.get("Content-Type") || "";
            if (respType.indexOf("application/json") !== -1 || respType.indexOf("text/") !== -1) {
                return response.text().then(function (txt) {
                    let msg = txt;
                    try {
                        const obj = JSON.parse(txt);
                        msg = obj.msg || obj.error || obj.message || txt;
                    } catch (e) {}
                    throw new Error("服务返回非音频数据: " + msg);
                });
            }
            return response.blob();
        }).then(function (blob) {
            clearRequestTimeout();
            if (!blob || blob.size === 0) {
                throw new Error("音频数据为空");
            }
            // 二次校验：blob 类型必须是音频，否则可能是错误信息
            if (blob.type && blob.type.indexOf("audio/") === -1 && blob.type.indexOf("application/octet-stream") === -1) {
                // 尝试读取内容作为错误消息
                return blob.text().then(function (txt) {
                    let msg = txt;
                    try {
                        const obj = JSON.parse(txt);
                        msg = obj.msg || obj.error || obj.message || txt;
                    } catch (e) {}
                    throw new Error("返回非音频(" + blob.type + "): " + msg);
                });
            }
            // 释放上一个 blob URL（如果有）
            if (audioElement.src && audioElement.src.indexOf("blob:") === 0) {
                URL.revokeObjectURL(audioElement.src);
            }
            // 标记正在切换 src，避免触发 error 事件
            isSwitchingSrc = true;
            const url = URL.createObjectURL(blob);
            audioElement.src = url;
            isSwitchingSrc = false;
            // play() 返回 Promise，可能因浏览器策略 reject
            return audioElement.play().then(function () {
                setLoading(false);
            }).catch(function (playErr) {
                console.error("音频播放 play() 被拒绝:", playErr);
                throw new Error("无法播放音频: " + (playErr.message || playErr.name));
            });
        }).catch(function (err) {
            clearRequestTimeout();
            setLoading(false);
            if (err && err.name === "AbortError") {
                // 主动中断，忽略
                return Promise.reject(err);
            }
            console.error("TTS 请求失败:", err);
            return Promise.reject(err);
        });
    }

    // 清除请求超时定时器
    function clearRequestTimeout() {
        if (requestTimeoutTimer) {
            clearTimeout(requestTimeoutTimer);
            requestTimeoutTimer = null;
        }
    }

    // 中断当前请求
    function abortCurrentRequest() {
        clearRequestTimeout();
        if (currentAbortController) {
            try { currentAbortController.abort(); } catch (e) {}
            currentAbortController = null;
        }
    }

    // 音频播放结束回调
    function onAudioEnded() {
        // 释放上一个 ObjectURL（用标记避免触发 error 事件）
        isSwitchingSrc = true;
        if (audioElement.src && audioElement.src.indexOf("blob:") === 0) {
            URL.revokeObjectURL(audioElement.src);
        }
        audioElement.removeAttribute("src");
        isSwitchingSrc = false;
        if (!isReading) return;
        isBusy = false;
        setLoading(false);
        if (typeof TTSChapterNextPageObj !== "undefined" && TTSChapterNextPageObj) {
            const url = TTSChapterNextPageObj.attr('href');
            if (url && url !== 'javascript:void(0);' && userConfig.ttsautoread > 0) {
                window.location.href = url;
                return;
            }
        }
        stopTTS();
    }

    // 音频错误回调
    function onAudioError(e) {
        // 忽略主动切换 src 时触发的错误
        if (isSwitchingSrc) return;
        const errCode = audioElement.error ? audioElement.error.code : 'unknown';
        const errDetail = audioElement.error ? audioElement.error.message : '';
        console.error("音频播放错误: code=", errCode, "detail=", errDetail, "src=", audioElement.src ? audioElement.src.substring(0, 100) : '(empty)');
        isBusy = false;
        if (!isReading) return;
        // 播放出错，停止朗读（避免无限跳段）
        showMessage("音频播放失败（code=" + errCode + "），已停止");
        stopTTS();
    }

    // 开始听书
    function startTTS() {
        if (isManualStop) {
            isManualStop = false;
        }
        // 停止当前播放
        stopAudioPlayback();

        // BUI 等模板会动态替换章节内容，每次开始都重新收集当前整章。
        prepareParagraphs();
        if (paragraphs.length === 0) {
            showMessage("未找到可朗读的内容");
            return;
        }

        const fullText = getFullChapterText();
        const voice = userConfig.ttsvoice || (elements.ttsVoicesSelect.val() || '');
        const rate = userConfig.ttsrate || 'normal';
        const pitch = userConfig.ttspitch || 'normal';

        isReading = true;
        isBusy = true;
        $(elements.contentElement).addClass("tts-active");
        setLoading(true);
        fetchAndPlay(fullText, voice, rate, pitch).then(function () {
            // 整章音频已开始播放，等待 ended 事件处理下一章。
        }).catch(function (err) {
            isBusy = false;
            if (err && err.name === "AbortError") {
                // 主动中断，不停止朗读流程
                return;
            }
            // 请求失败，停止朗读
            showMessage("朗读服务暂不可用，已停止");
            stopTTS();
        });
    }

    // 停止音频播放（不改变 isReading 状态）
    function stopAudioPlayback() {
        abortCurrentRequest();
        if (audioElement) {
            try {
                // 标记正在切换，避免清空 src 触发 error 事件
                isSwitchingSrc = true;
                audioElement.pause();
                if (audioElement.src && audioElement.src.indexOf("blob:") === 0) {
                    URL.revokeObjectURL(audioElement.src);
                }
                audioElement.removeAttribute("src");
                isSwitchingSrc = false;
            } catch (e) {
                isSwitchingSrc = false;
            }
        }
        isBusy = false;
        setLoading(false);
    }

    // 停止听书
    function stopTTS() {
        isManualStop = true;
        isReading = false;
        stopAudioPlayback();
        $(elements.contentElement).removeClass("tts-active");
        $('.tts-highlight, .tts-spoken').removeClass('tts-highlight tts-spoken');
        paragraphTimings = [];
        currentParagraphIndex = -1;
        totalDuration = 0;
        updateTTSButtons();
    }

    // 判断某个值是否在 select 的可选项中
    function isOptionInSelect($select, value) {
        if (!value) return false;
        return $select.find('option[value="' + value + '"]').length > 0;
    }

    // 应用配置到 DOM
    function applyConfigToDOM() {
        // 读取模板渲染时 select 的默认选中值（由 option 的 selected 决定）
        const defaultVoice = elements.ttsVoicesSelect.val();
        const defaultRate = elements.ttsRateSelect.val();
        const defaultPitch = elements.ttsPitchSelect.val();

        // 仅当 cookie 中的值在可选项中存在时才覆盖，否则保留模板默认选中项
        if (isOptionInSelect(elements.ttsVoicesSelect, userConfig.ttsvoice)) {
            elements.ttsVoicesSelect.val(userConfig.ttsvoice);
        } else {
            userConfig.ttsvoice = defaultVoice || '';
        }
        if (isOptionInSelect(elements.ttsRateSelect, userConfig.ttsrate)) {
            elements.ttsRateSelect.val(userConfig.ttsrate);
        } else {
            userConfig.ttsrate = defaultRate || 'normal';
        }
        if (isOptionInSelect(elements.ttsPitchSelect, userConfig.ttspitch)) {
            elements.ttsPitchSelect.val(userConfig.ttspitch);
        } else {
            userConfig.ttspitch = defaultPitch || 'normal';
        }
        elements.ttsAutoReadInput.prop('checked', userConfig.ttsautoread === 1 ? true : false);

        // 启用控件
        elements.ttsStartBtn.prop("disabled", false);
        elements.ttsStopBtn.prop("disabled", true);
        elements.ttsRateSelect.prop("disabled", false);
        elements.ttsVoicesSelect.prop("disabled", false);
        if (elements.ttsPitchSelect.length) {
            elements.ttsPitchSelect.prop("disabled", false);
        }
    }

    // 配置 API
    const configApi = {
        init() {
            const cookieValue = feiniaoGetCookie();
            userConfig = parseConfig(cookieValue) || { ...DEFAULT_CONFIG };
            if (!cookieValue) {
                persistConfig();
            }
            $(document).ready(function () {
                initAudio();
                applyConfigToDOM();
                if (parseInt(userConfig.ttsautoread) > 0) {
                    setTimeout(function () {
                        startTTS();
                    }, 3000);
                }
            });
        },
        updateTTSConf(voice, rate, pitch, autoread) {
            userConfig.ttsvoice = voice;
            userConfig.ttsrate = rate;
            userConfig.ttspitch = pitch;
            userConfig.ttsautoread = autoread ? 1 : 0;
            persistConfig();
        }
    };

    // 事件绑定
    function bindEvents() {
        elements.ttsStartBtn.on("click", function () {
            startTTS();
        });
        elements.ttsStopBtn.on("click", function () {
            stopTTS();
        });

        elements.ttsRateSelect.on("change", function () {
            const rate = $(this).val();
            if (rate) {
                userConfig.ttsrate = rate;
                persistConfig();
            }
        });

        elements.ttsVoicesSelect.on("change", function () {
            const voice = $(this).val();
            if (voice) {
                userConfig.ttsvoice = voice;
                persistConfig();
            }
        });

        if (elements.ttsPitchSelect.length) {
            elements.ttsPitchSelect.on("change", function () {
                const pitch = $(this).val();
                if (pitch) {
                    userConfig.ttspitch = pitch;
                    persistConfig();
                }
            });
        }

        elements.ttsAutoReadInput.on("change", function () {
            const isChecked = $(this).prop('checked');
            configApi.updateTTSConf(
                userConfig.ttsvoice,
                userConfig.ttsrate,
                userConfig.ttspitch,
                isChecked
            );
        });
    }

    // 初始化
    ensureSharedStyles();
    applyServerConfig();
    configApi.init();
    bindEvents();

    // 全局导出
    window.TTSconfig = configApi;

})(document, window);
