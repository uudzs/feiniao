var storage = bui.storage();
var token = storage.get('token', 0);
if (!token) gettoken();

// 封装的POST请求函数
async function post(url, data, callback) {
    try {
        await bui.ajax({
            headers: { Token: token },
            url: url,
            method: "POST",
            data: data,
            dataType: "json",
            success: function (result) {
                if (parseInt(result.code) == 99) {
                    if(isWeChat()) {
                        delCookie(sessionname);
                    }
                    bui.alert("请先登录", function(e) {
                        if(isWeChat()) {
                            window.location.href = '/';
                        } else {
                            window.location.href = loginurl;
                        }
                    }, { autoClose: false })
                } else if(parseInt(result.code) > 400) {
                    gettoken();                                     
                } else {
                    if(typeof callback === "function") {
                        callback(result)
                    } else {
                        return bui.hint("请求方式错误");
                    }
                }
            },
            fail: function (err) {
                bui.hint("请求失败" + err);
                console.error("请求失败:", err);
            }
        });
    } catch (err) {  
        console.error('发生错误:', err);  
        bui.hint("发生错误:" + JSON.stringify(err));
    }
}

//获取token
async function gettoken() {
    try {
        if(isWeChat()) {
            delCookie(sessionname);
        }
        await bui.ajax({
            headers: { Token: token },
            url: tokenurl,
            method: "POST",
            data: {},
            dataType: "json",
            success: function (result) {
                if (result.code == 0 && result.data.token) {
                    token = result.data.token;
                    storage.set("token", result.data.token);
                    bui.refresh();
                } else {
                   return bui.hint(result.msg);
                }
            },
            fail: function (err) {
                bui.hint("请求失败" + err);
                console.error("请求失败:", err);
            }
        });
    } catch (err) {
        console.error('发生错误:', err);  
        bui.hint("发生错误:" + JSON.stringify(err));
    }   
}

function isWeChat() {
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == 'micromessenger' && parseInt(is_official_open) === 1) {
        return true;
    } else {
        return false;
    }
}

function setCookie(name, value, days, options) {
    options = options || {};
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    var cookieString = name + "=" + (value || "") + expires + "; path=" + (options.path || "/");
    if (options.domain) cookieString += "; domain=" + options.domain;
    if (options.secure) cookieString += "; secure";
    if (options.sameSite) cookieString += "; samesite=" + options.sameSite; // 'Strict' or 'Lax'
    document.cookie = cookieString;
}

function getCookie(name) {
    const cookies = document.cookie.split("; ");
    for (let i = 0; i < cookies.length; i++) {
      const cookie = cookies[i].split("=");
      if (cookie[0] === name) {
        return cookie[1];
      }
    }
    return "";
}

function delCookie(name) {
    try {
        // 设置过期时间为一个过去的时间
        var date = new Date();
        date.setTime(date.getTime() - (24 * 60 * 60 * 1000)); // 24小时前
        var expires = "expires=" + date.toUTCString();        
        // 创建cookie，值设置为空字符串
        document.cookie = name + "=" + "" + "; " + expires + "; path=/";
    } catch (err) {  
        console.error('发生错误:', err);
    }
}