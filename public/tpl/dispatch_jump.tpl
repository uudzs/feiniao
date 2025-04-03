<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{:lang('common.jumptitle')}</title>
    <style>
        /* 原有CSS保持不变 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            max-width: 800px;
            width: 100%;
        }

        .error-logo {
            max-width: 220px;
            height: auto;
            margin-bottom: 40px;
        }

        .error-message {
            font-size: 24px;
            color: #636e72;
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.4;
        }

        .home-button {
            padding: 15px 40px;
            background: #0984e3;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .countdown-text {
            display: inline-block;
            font-size: 16px;
            color: rgba(255,255,255,0.9);
        }

        @media (max-width: 768px) {
            /* 原有媒体查询保持不变 */
            .error-logo {
                max-width: 180px;
                margin-bottom: 30px;
            }

            .error-message {
                font-size: 20px;
                padding: 0 15px;
            }

            .home-button {
                padding: 12px 30px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .error-message {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{:get_system_config('web','logo')}" alt="{:get_system_config('web','title')}" class="error-logo">
        <p class="error-message">{$msg}</p>
        <a href="{$url ? $url : '/'}" class="home-button" id="countdownButton">
            {:lang('common.jumplink')}
            <span class="countdown-text" id="countdown"></span>
        </a>
    </div>

    <script>
        // 倒计时设置
        let seconds = parseInt({$wait ? $wait : 0});  // 设置倒计时秒数
        const countdownEl = document.getElementById('countdown');
        const buttonEl = document.getElementById('countdownButton');

        // 更新倒计时显示
        function updateCountdown() {
            countdownEl.innerHTML = `(${seconds}{:lang('common.jumpsecond')})`;
            if(seconds <= 0) {
                window.location.href = '{$url ? $url : "/"}';  // 跳转目标地址
            }
            seconds--;
        }

        // 立即跳转功能
        buttonEl.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = this.href;
        });

        // 初始化倒计时
        if(seconds > 0) {
            updateCountdown();  // 立即执行第一次显示
            const countdownInterval = setInterval(updateCountdown, 1000);
        }        
    </script>
</body>
</html>