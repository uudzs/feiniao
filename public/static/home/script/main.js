(function ($) {
  'use strict';

  // WOW JS
  new WOW().init();

  // // Window Loading JS
  $(window).on('load', function () {
    var localfeiniao = layui.data('feiniao');
    token = localfeiniao.token;
    console.log('token',token);   
    if (!token) $.getToken();
  });

  $.extend({
    postApi: function(url, data, callback) {
        try {
          $.ajax({
              headers: { Token: layui.data('feiniao').token },
              url: url,
              method: "POST",
              data: data,
              dataType: "json",
              success: function (result) {
                  if (parseInt(result.code) == 99) {
                    layer.alert('请先登录', {title: "登录提示",icon: 3, closeBtn: 0, btnAlign: 'c', shadeClose: true}, function(index) {
                      layer.close(index);
                      window.location.href = loginurl;                    
                    });
                  } else if(parseInt(result.code) > 400) {
                      $.getToken();                                     
                  } else {
                      if(typeof callback === "function") {
                          callback(result)
                      } else {
                          return layer.msg("请求方式错误");
                      }
                  }
              },
              error: function (xhr, status, error) {
                console.error('发生错误:', error);  
                layer.msg("发生错误:" + JSON.stringify(error));
              }
          });
      } catch (err) {  
          console.error('发生错误:', err);  
          layer.msg("发生错误:" + JSON.stringify(err));
      }
    },
    getToken: function() {
      try {
          $.ajax({
            url: tokenurl,
            headers: { Token: layui.data('feiniao').token },
            data: {},
            type: "POST",
            dataType: "json",
            success: function (result) {
              if (result.code == 0 && result.data.token) {
                  token = result.data.token;
                  layui.data('feiniao', {
                    key: 'token',
                    value: result.data.token
                  });
                  location.reload();
              } else {
                return layer.msg(result.msg);
              }					
            },
            error: function (xhr, status, error) {
              console.error('发生错误:', error);  
              layer.msg("发生错误:" + JSON.stringify(error));
            },
            complete: function () {}
          });
      } catch (err) {
          console.error('发生错误:', err);  
          layer.msg("发生错误:" + JSON.stringify(err));
      }
    },
    getCookie: function(name) {
      const cookies = document.cookie.split("; ");
      for (let i = 0; i < cookies.length; i++) {
        const cookie = cookies[i].split("=");
        if (cookie[0] === name) {
          return cookie[1];
        }
      }
      return "";
    },
    delCookie: function(name) {
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
  });
})(jQuery);