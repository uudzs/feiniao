(function () {
    'use strict';

    var options = window.ReverseCollectChapter || {};
    if (!options.enabled || !options.empty || !options.chapterId || !options.endpoint) return;

    var selectors = [
        options.container,
        '#chapter_content',
        '#chapterContent',
        '.chapterList .content',
        '.m-read .u-read-content',
        '#Jcontent .content'
    ];
    var container = null;
    for (var i = 0; i < selectors.length; i++) {
        if (!selectors[i]) continue;
        container = document.querySelector(selectors[i]);
        if (container) break;
    }
    if (!container) return;

    var attempts = 0;
    var maxAttempts = 30;
    var timer = null;

    function render(message, stopped) {
        container.innerHTML = '<div class="reverse-collect-wait" style="padding:48px 16px;text-align:center;line-height:2;color:#888">' +
            '<span class="reverse-collect-spinner" style="display:' + (stopped ? 'none' : 'inline-block') + ';width:22px;height:22px;border:3px solid #ddd;border-top-color:#ff6a00;border-radius:50%;vertical-align:middle;margin-right:10px;animation:reverseCollectSpin .8s linear infinite"></span>' +
            '<span>' + message + '</span>' +
            (stopped ? '<br><button type="button" id="reverse-collect-retry" style="margin-top:12px;padding:7px 18px;border:0;border-radius:4px;background:#ff6a00;color:#fff">继续等待</button>' : '') +
            '</div>';
        if (stopped) {
            var button = document.getElementById('reverse-collect-retry');
            if (button) button.onclick = function () { attempts = 0; render('正在获取章节内容，请稍候…', false); poll(); };
        }
    }

    function poll() {
        clearTimeout(timer);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', options.endpoint, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            attempts++;
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    var data = response.data || {};
                    if (data.ready) {
                        window.location.reload();
                        return;
                    }
                    if (data.status === 'cancelled') {
                        render('章节内容暂时获取失败，请稍后再试', true);
                        return;
                    }
                    if (data.status === 'disabled' || data.status === 'no_source' || data.status === 'not_found') {
                        container.innerHTML = '<div style="padding:48px 16px;text-align:center;color:#888">暂无章节内容</div>';
                        return;
                    }
                } catch (ignore) {}
            }
            if (attempts >= maxAttempts) {
                render('章节仍在采集中，你可以继续等待', true);
                return;
            }
            timer = setTimeout(poll, 1500);
        };
        xhr.onerror = function () {
            attempts++;
            timer = setTimeout(poll, 2000);
        };
        xhr.send('chapter_id=' + encodeURIComponent(options.chapterId));
    }

    var style = document.createElement('style');
    style.textContent = '@keyframes reverseCollectSpin{to{transform:rotate(360deg)}}';
    document.head.appendChild(style);
    render('正在获取章节内容，请稍候…', false);
    poll();
})();
