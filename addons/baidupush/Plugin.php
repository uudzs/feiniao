<?php

namespace addons\baidupush;

use think\Addons;
use think\facade\App;

/**
 * 注意名字不可以修改，只能为Plugin
 */
class Plugin extends Addons    // 需继承think\Addons类
{
    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    public function baiduPushHook($param)
    {
        if (!$param) return false;
        // 当前插件的基础信息，系统优先获取info.ini中的配置信息
        $info = $this->getInfo();
        // 插件禁用后不再进行上传
        if ($info['install'] == 0 || $info['status'] == 0) return false;
        $config = [];
        $rootPath = App::getRootPath();
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $config = (array) include $config_file;
        }
        if (empty($config)) return false;
        if (empty($config['site']['value'])) return false;
        if (empty($config['token']['value'])) return false;
        switch ($param['type']) {
            case 'edit':
                $type = "update";
                break;
            case 'del':
                $type = "del";
                break;
            default:
                $type = "urls";
                break;
        }
        $url = 'http://data.zz.baidu.com/' . $type . '?site=' . $config['site']['value'] . '&token=' . $config['token']['value'];
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $param['data']),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        return $result;
    }
}
