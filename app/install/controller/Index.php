<?php

declare(strict_types=1);

namespace app\install\controller;

use app\install\validate\InstallCheck;
use mysqli;
use think\exception\ValidateException;
use think\facade\View;

class Index
{
    public function __construct()
    {
        // 检测是否安装过
        if (is_installed()) {
            echo '你已经安装过飞鸟阅读系统！如需重新安装，请删除“config/install.lock”文件';
            die();
        }
        if (!extension_loaded('fileinfo')) {
            echo '你还未安装（开启）PHP的【fileinfo】扩展，该扩展主要用于文件上传，请先安装（开启），并重启php服务';
            die();
        }
        if (!extension_loaded('gd')) {
            echo '你还未安装（开启）PHP的【gd】扩展，该扩展主要用于验证码，请先安装（开启），并重启php服务';
            die();
        }
    }

    public function index()
    {
        View::assign('TP_VERSION', \think\facade\App::version());
        return view('step1');
    }

    public function step2()
    {
        if (class_exists('pdo')) {
            $data['pdo'] = 1;
        } else {
            $data['pdo'] = 0;
        }

        if (extension_loaded('pdo_mysql')) {
            $data['pdo_mysql'] = 1;
        } else {
            $data['pdo_mysql'] = 0;
        }

        if (extension_loaded('curl')) {
            $data['curl'] = 1;
        } else {
            $data['curl'] = 0;
        }

        if (ini_get('file_uploads')) {
            $data['upload_size'] = ini_get('upload_max_filesize');
        } else {
            $data['upload_size'] = 0;
        }

        if (function_exists('session_start')) {
            $data['session'] = 1;
        } else {
            $data['session'] = 0;
        }
        $storageDir = CMS_ROOT . '/public/storage/';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }
        return view('', ['data' => $data]);
    }

    public function step3()
    {
        return view();
    }

    public function install()
    {
        $data = get_params();
        try {
            validate(InstallCheck::class)->check($data);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return to_assign(1, $e->getError());
        }
        $dbName = $data['DB_NAME'];
        //验证表是否存在		
        try {
            // 连接数据库
            $link = @new mysqli("{$data['DB_HOST']}:{$data['DB_PORT']}", $data['DB_USER'], $data['DB_PWD']);
        } catch (\Exception $e) {
            // 这是进行异常捕获,创建数据库
            $error = $e->getMessage();
            return to_assign(1, '数据库链接失败:' . $error);
            die;
        }
        // 获取错误信息
        $error = $link->connect_error;
        if (!is_null($error)) {
            // 转义防止和alert中的引号冲突
            $error = addslashes($error);
            return to_assign(1, '数据库链接失败:' . $error);
            die;
        }
        // 设置字符集
        $link->query("SET NAMES 'utf8mb4'");
        //验证表是否存在		
        try {
            // 这里是主体代码
            $isDB = $link->query('SHOW TABLES LIKE ' . "'" . $dbName . "'");
            if (!$isDB) {
                //创建数据库并选中
                $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4";
                $link->query($sql);
            }
        } catch (\Exception $e) {
            // 这是进行异常捕获,创建数据库并选中
            $error = $e->getMessage();
            $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4";
            $link->query($sql);
        }
        $link->select_db($dbName);
        // 导入sql数据并创建表
        $fqcms_sql = file_get_contents(CMS_ROOT . '/app/install/data/install.sql');
        $sql_array = preg_split("/;[\r\n]+/", str_replace("fn_", $data['DB_PREFIX'], $fqcms_sql));
        foreach ($sql_array as $k => $v) {
            if (!empty($v)) {
                $link->query($v);
            }
        }
        //创建章节表
        $chapter_Tables = intval($data['chapter_Tables']);
        if ($chapter_Tables <= 0) {
            return to_assign(1, '章节分表数错误');
            die;
        }
        for ($i = 0; $i < $chapter_Tables; $i++) {
            //$sql = "DROP TABLE IF EXISTS `" . $data['DB_PREFIX'] . "chapter_content_`" . $i . ";";
            //$link->query($sql);
            $sql = "CREATE TABLE `" . $data['DB_PREFIX'] . "chapter_content_" . $i . "` (
                `sid` bigint(20) DEFAULT '0' COMMENT '章节ID',
                `info` longtext COMMENT '章节内容',
                KEY `sid` (`sid`)
            ) ENGINE=InnoDB DEFAULT CHARACTER SET = utf8mb4 COMMENT='章节内容表'";
            $link->query($sql);
        }
        $isTable = $link->query('SHOW TABLES LIKE "' . $data['DB_PREFIX'] . 'admin"');
        if (!$isTable) {
            return to_assign(1, '创建数据库表失败，请检查是否有创建权限！');
        }
        //插入管理员信息
        $username = $data['username'];
        $password = $data['password'];
        $nickname = '超级管理员';
        $thumb = '/static/admin/images/icon.png';
        $salt = set_salt(20);
        $password = set_password($password, $salt);
        $create_time = time();
        $update_time = time();
        $create_admin_sql = "INSERT INTO " . $data['DB_PREFIX'] . "admin " . "(username,pwd, nickname,thumb,salt,did,position_id,create_time,update_time) " . "VALUES " . "('$username','$password','$nickname','$thumb','$salt',1,1,'$create_time','$update_time')";
        if (!$link->query($create_admin_sql)) {
            return to_assign(1, '创建管理员信息失败，请重试安装');
        }
        $event = "CREATE DEFINER=`" . $data['DB_USER'] . "`@`localhost` EVENT `VIPExpired` ON SCHEDULE EVERY 1 MINUTE STARTS '2024-01-01 22:40:25' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'VIP过期' DO UPDATE `" . $data['DB_PREFIX'] . "vip_log` SET `status`=2 WHERE `expire_time`>0 and `expire_time`<=unix_timestamp(now()) and `status`=1";
        $link->query($event);
        $event = "CREATE DEFINER=`" . $data['DB_USER'] . "`@`localhost` EVENT `timingRelease` ON SCHEDULE EVERY 1 MINUTE STARTS '2021-03-27 15:19:16' ON COMPLETION PRESERVE ENABLE COMMENT '定时发布' DO UPDATE `" . $data['DB_PREFIX'] . "chapter` SET `status`=1,`trial_time`=0,`create_time`=unix_timestamp(now()) WHERE `trial_time`>0 and `trial_time`<=unix_timestamp(now()) and `status`=0";
        $link->query($event);
        $event = "CREATE DEFINER=`" . $data['DB_USER'] . "`@`localhost` EVENT `upBookChapters` ON SCHEDULE EVERY 1 DAY STARTS '2021-03-27 15:19:16' ON COMPLETION PRESERVE ENABLE COMMENT '更新作品章节数' DO UPDATE `" . $data['DB_PREFIX'] . "book` SET chapters = (SELECT count(`id`) FROM `" . $data['DB_PREFIX'] . "chapter` WHERE `bookid` = `" . $data['DB_PREFIX'] . "book`.id AND `verify` in(0,1))";
        $link->query($event);
        $event = "CREATE DEFINER=`" . $data['DB_USER'] . "`@`localhost` EVENT `upBookWords` ON SCHEDULE EVERY 1 MINUTE STARTS '2021-03-27 15:19:16' ON COMPLETION NOT PRESERVE ENABLE COMMENT '更新作品字数' DO UPDATE `" . $data['DB_PREFIX'] . "book` SET words = (SELECT SUM(`wordnum`) FROM `" . $data['DB_PREFIX'] . "chapter` WHERE `bookid` = `" . $data['DB_PREFIX'] . "book`.id AND `verify` in(0,1))";
        $link->query($event);
        //开启事件
        //$sql = "SET GLOBAL event_scheduler = ON;";
        //$link->query($sql);        
        $link->close();
        $db_str = "<?php
return [
    // 默认使用的数据库连接配置
    'default'         => 'mysql',
    // 自定义时间查询规则
    'time_query_rule' => [],
    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => true,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'               =>  'mysql',
            // 服务器地址
            'hostname'           =>  '{$data['DB_HOST']}',
            // 数据库名
            'database'           =>  '{$data['DB_NAME']}',
            // 用户名
            'username'           =>  '{$data['DB_USER']}',
            // 密码
            'password'           =>  '{$data['DB_PWD']}',
            // 端口
            'hostport'           =>  '{$data['DB_PORT']}',
            // 数据库表前缀
            'prefix'             =>  '{$data['DB_PREFIX']}',
            //章节分表数
            'chapter_Tables'     =>  '{$data['chapter_Tables']}',
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8mb4
            'charset'         => 'utf8mb4',
            // 数据库调试模式
            'debug'           => false,
            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => false,
            // 监听SQL
            'trigger_sql'     => true,
            // 开启字段缓存
            'fields_cache'    => false,
        ],
    ],
];";

        // 创建数据库配置文件
        if (false == file_put_contents(CMS_ROOT . "config/database.php", $db_str)) {
            return to_assign(1, '创建数据库配置文件失败，请检查目录权限');
        }
        if (false == file_put_contents(CMS_ROOT . "config/install.lock", '飞鸟阅读安装鉴定文件，请勿删除！！！！！此次安装时间为：' . date('Y-m-d H:i:s', time()))) {
            return to_assign(1, '创建安装鉴定文件失败，请检查目录权限');
        }
        return to_assign();
    }
}
