!(function (document, window) {
    "use strict";

    const DEFAULT_CONFIG = {
        ttsvoice: 0,
        ttsrate: 1.0,
        ttsautoread: 0
    };

    // DOM元素缓存
    const elements = {
        contentElement: TTSChapterContainer,
        ttsStartBtn: $("#ttsstart"),
        ttsStopBtn: $("#ttsstop"),
        ttsRateSelect: $("#tts-rate"),
        ttsVoicesSelect: $("#tts-voices"),
        ttsAutoReadInput: $("#tts-read")
    };

    // 配置管理
    let userConfig = null;
    // 语音合成对象
    let speechSynthesis = window.speechSynthesis;
    let speechUtterance = null;
    let voices = [];
    let currentVoiceIndex = 0;
    let currentParagraphIndex = 0;
    let paragraphs = [];
    let isReading = false;

    // 持久化配置
    function persistConfig() {
        const configValues = [
            userConfig.ttsvoice,
            userConfig.ttsrate,
            userConfig.ttsautoread
        ].join(",");
        feiniaoSetCookie(configValues);
    }

    // 解析配置
    function parseConfig(rawConfig) {
        if (!rawConfig) return null;
        let parts = rawConfig.split(",");
        if(parts.length < 3) {
            const decodedStr = decodeURIComponent(rawConfig);
            parts = decodedStr.split(",");
        }
        return {
            ttsvoice: parseInt(parts[0]) || DEFAULT_CONFIG.ttsvoice,
            ttsrate: parseFloat(parts[1]) || DEFAULT_CONFIG.ttsrate,
            ttsautoread: parseInt(parts[2]) || DEFAULT_CONFIG.ttsautoread,
        };
    }

    // 初始化语音合成
    function initSpeechSynthesis() {
        if (!speechSynthesis) {
            console.warn("Speech synthesis not supported in this browser.");
            return;
        }        
        // 加载可用语音
        voices = speechSynthesis.getVoices();
        if (voices.length === 0) {
            speechSynthesis.addEventListener("voiceschanged", () => {
                voices = speechSynthesis.getVoices();
                populateVoiceList();
            });
        } else {
            populateVoiceList();
        }        
        // 更新按钮状态
        updateTTSButtons();
    }

    // 准备段落内容用于高亮显示
    function prepareParagraphs() {
        // 清空现有段落
        paragraphs = [];
        // 获取所有段落元素
        const contentElements = $(elements.contentElement).last().find('p, h2, h3, h4');
        // 如果没有元素，则使用整个内容
        if (contentElements.length === 0) {
            return;
        }        
        // 收集段落
        contentElements.each(function(index) {
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

    // 填充语音列表
    function populateVoiceList() {
        if (!voices.length) return;
        
        // 清空现有选项
        elements.ttsVoicesSelect.empty();
        
        // 添加新选项
        voices.forEach((voice, index) => {
            const option = document.createElement("option");
            option.value = index;
            option.textContent = `${voice.name} (${voice.lang})`;
            elements.ttsVoicesSelect.append(option);
        });
        
        // 设置选中的语音
        const voiceIndex = Math.min(userConfig.ttsvoice, voices.length - 1);
        elements.ttsVoicesSelect.val(voiceIndex);
        currentVoiceIndex = voiceIndex;
        
        // 启用控件
        elements.ttsStartBtn.prop("disabled", false);
        elements.ttsStopBtn.prop("disabled", false);
        elements.ttsRateSelect.prop("disabled", false);
        elements.ttsVoicesSelect.prop("disabled", false);
    }

    // 更新TTS按钮状态
    function updateTTSButtons() {
        const isSpeaking = speechSynthesis && speechSynthesis.speaking;
        elements.ttsStartBtn.prop("disabled", isSpeaking);
        elements.ttsStopBtn.prop("disabled", !isSpeaking);
    }

    // 高亮当前段落
    function highlightCurrentParagraph() {
        // 移除之前的高亮
        $('.tts-highlight').removeClass('tts-highlight');        
        // 高亮当前段落
        if (paragraphs[currentParagraphIndex] && paragraphs[currentParagraphIndex].element) {
            const $currentPara = paragraphs[currentParagraphIndex].element;
            $currentPara.addClass('tts-highlight');            
            // 滚动到可见位置
            const offset = $currentPara.offset();
            if (offset) {
                $('html, body').animate({
                    scrollTop: offset.top - 100
                }, 300);
            }
        }
    }

    // 开始听书
    function startTTS() {
        if (!speechSynthesis) return;
        
        // 停止当前语音
        if (speechSynthesis.speaking) {
            speechSynthesis.cancel();
        }
        
        // 确保有段落内容
        if (paragraphs.length === 0) {
            prepareParagraphs();
        }
        
        // 重置索引
        currentParagraphIndex = 0;
        isReading = true;
        
        // 开始朗读第一个段落
        readNextParagraph();
    }

    // 朗读下一个段落
    function readNextParagraph() {
        if (!isReading || currentParagraphIndex >= paragraphs.length) {
            if(TTSChapterContentOpen === 0) {
                paragraphs = [];
                currentParagraphIndex = 0;
                return startTTS();
            } else {
                let url = TTSChapterNextPageObj.attr('href');
                if(url && url !== 'javascript:void(0);' && userConfig.ttsautoread > 0) {
                    window.location.href = url;
                }
            }
            // 朗读结束
            stopTTS();
            return;
        }
        
        // 高亮当前段落
        highlightCurrentParagraph();
        
        // 获取当前段落文本
        const paragraph = paragraphs[currentParagraphIndex];
        const text = paragraph.text;
        
        // 创建新的语音实例
        speechUtterance = new SpeechSynthesisUtterance(text);
        
        // 设置语音参数
        speechUtterance.voice = voices[currentVoiceIndex];
        speechUtterance.rate = userConfig.ttsrate;
        speechUtterance.pitch = 1;
        speechUtterance.volume = 1;
        speechUtterance.lang = "zh-CN";
        
        // 事件监听
        speechUtterance.onstart = () => {
            updateTTSButtons();
            $(elements.contentElement).addClass("tts-active");
        };
        
        speechUtterance.onend = () => {
            // 移动到下一个段落
            currentParagraphIndex++;
            readNextParagraph();
        };
        
        speechUtterance.onerror = (event) => {
            console.error("Speech synthesis error:", event.error);
            stopTTS();
        };
        
        // 开始朗读
        speechSynthesis.speak(speechUtterance);
        updateTTSButtons();
    }

    // 停止听书
    function stopTTS() {
        if (speechSynthesis && speechSynthesis.speaking) {
            speechSynthesis.cancel();
        }        
        // 重置状态
        isReading = false;
        $(elements.contentElement).removeClass("tts-active");
        $('.tts-highlight').removeClass('tts-highlight');
        updateTTSButtons();
    }

    // 应用配置到DOM
    function applyConfigToDOM() {
        // 应用语音设置
        if(userConfig.ttsrate === 1) userConfig.ttsrate = '1.0'
        elements.ttsRateSelect.val(userConfig.ttsrate);
        elements.ttsAutoReadInput.prop('checked', userConfig.ttsautoread === 1 ? true : false);
        // 初始化语音合成
        initSpeechSynthesis();
    }

    // 配置API
    const configApi = {
        init() {
            // 获取或初始化配置
            const cookieValue = feiniaoGetCookie();
            userConfig = parseConfig(cookieValue) || {...DEFAULT_CONFIG};
            if (!cookieValue) {
                persistConfig();
            }            
            // 确保DOM完全加载后再应用配置
            $(document).ready(() => {
                // 应用配置到DOM元素
                applyConfigToDOM();                
                if(parseInt(userConfig.ttsautoread) > 0) {         
                    setTimeout(() => {
                        startTTS();
                    }, 3000);
                }
            });
        },   
        updateTTSConf(voiceIndex, rate, autoread) {
            userConfig.ttsvoice = voiceIndex;
            userConfig.ttsrate = parseFloat(rate).toFixed(1);
            userConfig.ttsautoread = autoread ? 1 : 0;
            persistConfig();            
            // 更新当前语音
            currentVoiceIndex = voiceIndex;
        }
    };

    // 事件绑定
    function bindEvents() {

        // 听书功能事件绑定
        elements.ttsStartBtn.on("click", startTTS);
        elements.ttsStopBtn.on("click", stopTTS);        
        elements.ttsRateSelect.on("change", function() {
            const rate = parseFloat($(this).val());
            if (!isNaN(rate)) {
                userConfig.ttsrate = rate;
                persistConfig();                
                // 如果正在朗读，更新参数
                if (speechSynthesis && speechSynthesis.speaking && speechUtterance) {
                    speechUtterance.rate = rate;
                }
            }
        });        
        elements.ttsVoicesSelect.on("change", function() {
            const voiceIndex = parseInt($(this).val());
            if (!isNaN(voiceIndex) && voiceIndex < voices.length) {
                currentVoiceIndex = voiceIndex;
                configApi.updateTTSConf(voiceIndex, userConfig.ttsrate, userConfig.ttsautoread);                
                // 如果正在朗读，更新语音
                if (speechSynthesis && speechSynthesis.speaking && speechUtterance) {
                    speechUtterance.voice = voices[voiceIndex];
                }
            }
        });
        elements.ttsAutoReadInput.on("change", function() {
            const isChecked = $(this).prop('checked');
            if (!isNaN(isChecked)) {
                configApi.updateTTSConf(userConfig.ttsvoice, userConfig.ttsrate, isChecked);                
            }
        });
    }
        
    // 初始化    
    configApi.init();
    bindEvents();
    
    // 全局导出
    window.TTSconfig = configApi;

})(document, window);