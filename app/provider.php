<?php

// 容器Provider定义文件
return [
    // 绑定自定义异常处理handle类
    'think\exception\Handle'       => '\\app\\exception\\Http',
    'think\exception\Handle'       => '\\app\\exception\\Handler',
];
