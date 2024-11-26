<?php

return array(
    'open' =>
    array(
        'title' => '是否开启',
        'type' => 'radio',
        'options' =>
        array(
            1 => '是',
            0 => '否',
        ),
        'value' => 0,
        'tips' => '如页面显示异常，请关闭该功能。',
    ),
    'autouptime' =>
    array(
        'title' => '自动更新周期',
        'type' => 'string',
        'value' => 30,
        'tips' => '以分钟为单位，请设置合理的周期，0为不更新。',
    ),

);
