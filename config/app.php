<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用地址
    'app_host' => env('app.host', ''),
    // 应用的命名空间
    'app_namespace' => '',
    // 是否启用路由
    'with_route' => true,
    // 默认应用
    'default_app' => 'home',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 自动多应用模式
    'auto_multi_app' => false,
    // 应用映射（自动多应用模式有效）
    'app_map' => [],
    // 域名绑定（自动多应用模式有效）
    /**
     *  示例
     * 'domain_bind' => [
     *      'admin.youdomain.com' => 'admin',
     *      'author.youdomain.com' => 'author',
     *      'api.youdomain.com' => 'api',
     *      '*' => 'home'
     *  ],
    */
    'domain_bind' => [],
    //home模块域名，只有domain_bind设置多域名时有效，并且只适用home模块
    /**
     *  示例
     *  'home_domain' => 'www.youdomain.com',
     */
    'home_domain' => '',
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list' => [],
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter' => 'htmlspecialchars',
    // 异常页面的模板文件
    'exception_tmpl' => app()->getRootPath() . '/public/tpl/think_exception.tpl',
    // 默认跳转页面对应的模板文件【新增】
    'dispatch_success_tmpl' => app()->getRootPath() . '/public/tpl/dispatch_jump.tpl',
    'dispatch_error_tmpl' => app()->getRootPath() . '/public/tpl/dispatch_jump.tpl',
    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg' => false,
    'page_size' => 20,
    'session_author' => 'cms_author',
    'session_user' => 'cms_user',
    'session_admin' => 'cms_admin',
    'session_invite' => 'cms_invite',
    'http_exception_template' => [
        // 登录失败
        401 => public_path() . 'tpl/401.html',
        // 禁止访问
        403 => public_path() . 'tpl/403.html',
        // 无法找到文件
        404 => public_path() . 'tpl/404.html',
        // 无权限访问
        405 => public_path() . 'tpl/405.html',
        // 找不到数据
        406 => public_path() . 'tpl/406.html',
        //内部服务器错误
        500 => public_path() . 'tpl/500.html',
    ]
];
