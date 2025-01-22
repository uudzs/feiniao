<?php

namespace addons\qcloudcos;

use think\Addons;
use think\facade\Db;

/**
 * 注意名字不可以修改，只能为Plugin
 */
class Plugin extends Addons
{

    // 插件的安装 [添加文件、执行sql等]
    public function install()
    {
        return true;
    }

    // 插件的卸载 [移除文件、执行sql等]
    public function uninstall()
    {
        return true;
    }

    public function qcloudCosHook($param)
    {
        // 当前插件的基础信息，系统优先获取info.ini中的配置信息
        $info = $this->getInfo();
        // 插件禁用后不再进行上传
        if ($info['install'] == 0 || $info['status'] == 0) {
            return json_encode([
                'error' => 1,
                'msg' => '请先安装并启用',
                'data' => '',
            ]);
        }
        $config = $this->getConfig();
        require_once app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $config['region'],
                'scheme' => 'http', //协议头部，默认为http
                'credentials' => array(
                    'secretId'  => $config['secretid'],
                    'secretKey' => $config['secretkey']
                )
            )
        );
        $filePath = app()->getRootPath() . 'public' . get_config('filesystem.disks.public.url') . '/' . $param['url'];
        # 上传文件
        try {
            $result = $cosClient->putObject(array(
                'Bucket' => $config['bucket'],
                'Key' => $param['url'],
                'Body' => fopen($filePath, 'rb')
            ));
            // 请求成功
            $data = $result->toArray();
            if (isset($data['Location']) && isset($data['Key'])) {
                if ($config['keep_file'] === '0') {
                    unlink($filePath);
                }
                if ($config['domain']) {
                    $url = rtrim($config['domain'], '/') . '/' . $param['url'];
                } else {
                    $url = $data['Location'];
                }
                return json_encode([
                    'error' => 0,
                    'msg' => '上传成功',
                    'data' => $url,
                ]);
            } else {
                $url = get_config('filesystem.disks.public.url') . '/' . $param['url'];
                return json_encode([
                    'error' => 0,
                    'msg' => '上传成功',
                    'data' => $url,
                ]);
            }
        } catch (\Exception $e) {
            return json_encode([
                'error' => 1,
                'msg' => $e->getMessage(),
                'data' => '',
            ]);
        }
    }
}
