<?php

namespace addons\baidupush;

use think\Addons;
use think\facade\App;
use think\facade\Request;
use think\facade\Db;

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

    public function run()
    {
        $info = $this->getInfo();
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
        $param = [
            'site' => $config['site']['value'],
            'token' => $config['token']['value'],
        ];
        $current_url = Request::domain() . Request::url();
        if (empty($current_url)) return false;
        $newpush = Db::name('addons_baidupush')->where(['dateymd' => date('Ymd')])->order('id desc')->find();
        if (!empty($newpush)) {
            if (trim($newpush['result']) == 'over quota') return false;
        }
        $push = Db::name('addons_baidupush')->where(['url' => $current_url])->find();
        if (!empty($push)) {
            if (intval($push['status']) != 1) {
                $param['type'] = 'add';
                $param['data'] = [$current_url];
                $result = $this->request($param);
                if ($result) {
                    $res = json_decode($result, true);
                    if ($res && isset($res['error']) && isset($res['message']) && $res['error']) {
                        Db::name('addons_baidupush')->where('id', $push['id'])->inc('num')->strict(false)->field(true)->update(['dateymd' => date('Ymd'), 'result' => $res['message'], 'update_time' => time(), 'status' => 0]);
                    }
                    if ($res && isset($res['success']) && $res['success']) {
                        Db::name('addons_baidupush')->where('id', $push['id'])->inc('num')->strict(false)->field(true)->update(['dateymd' => date('Ymd'), 'remain' => $res['remain'], 'result' => $res['success'], 'update_time' => time(), 'status' => 1]);
                    }
                } else {
                    return false;
                }
            }
        } else {
            $param['type'] = 'add';
            $param['data'] = [$current_url];
            $result = $this->request($param);
            if ($result) {
                $res = json_decode($result, true);
                if ($res && isset($res['error']) && isset($res['message']) && $res['error']) {
                    Db::name('addons_baidupush')->strict(false)->field(true)->insertGetId([
                        'url' => $current_url,
                        'dateymd' => date('Ymd'),
                        'status' => 0,
                        'num' => 1,
                        'result' => $res['message'],
                        'remain' => 0,
                        'create_time' => time()
                    ]);
                    return false;
                }
                if ($res && isset($res['success']) && $res['success']) {
                    Db::name('addons_baidupush')->strict(false)->field(true)->insertGetId([
                        'url' => $current_url,
                        'dateymd' => date('Ymd'),
                        'status' => 1,
                        'num' => 1,
                        'result' => $res['success'],
                        'remain' => $res['remain'],
                        'create_time' => time()
                    ]);
                    return true;
                }
            }
        }
        return false;
    }

    public function baiduPushHook($param)
    {
        $info = $this->getInfo();
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
        $param['site'] = $config['site']['value'];
        $param['token'] = $config['token']['value'];
        return $this->request($param);
    }

    private function request($param)
    {
        if (!$param) return false;
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
        $url = 'http://data.zz.baidu.com/' . $type . '?site=' . $param['site'] . '&token=' . $param['token'];
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
