<?php

// +----------------------------------------------------------------------

// | 控制台配置

// +----------------------------------------------------------------------

return [

    // 指令定义

    'commands' => [
		'crud' => 'app\crud\command\Crud',
		'crud-c' => 'app\crud\command\CrudController',
		'crud-m' => 'app\crud\command\CrudModel',
		'crud-v' => 'app\crud\command\CrudValidate',
		'crud-l' => 'app\crud\command\CrudList',
		'crud-a' => 'app\crud\command\CrudAdd',
		'crud-e' => 'app\crud\command\CrudEdit',
		'crud-r' => 'app\crud\command\CrudRead',
    ],

];

