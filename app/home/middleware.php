<?php

// 这是系统自动生成的middleware定义文件
return [
    //开启session中间件
    //'think\middleware\SessionInit',
    // 多语言加载
    \think\middleware\LoadLangPack::class,
    //验证是否完成安装
    \app\home\middleware\Install::class,
    //验证操作
    \app\home\middleware\Auth::class,
];
