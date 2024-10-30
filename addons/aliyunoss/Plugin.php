<?php
namespace addons\aliyunoss;
use OSS\Core\OssException;
use OSS\OssClient;
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
        // $config_web = get_system_config('web');
        // if (is_array($config_web)) {
        //     $config_web['upload_driver'] = 2;
        //     $content = serialize($config_web);
        //     Db::name('Config')->where(['name' => 'web'])->strict(false)->field(true)->update(['content' => $content]);
        //     clear_cache('system_config_web');
        //     return true;
        // }
        return true;
    }

    // 插件的卸载 [移除文件、执行sql等]
    public function uninstall()
    {
        // $config_web = get_system_config('web');
        // if (is_array($config_web)) {
        //     if (isset($config_web['upload_driver']) && $config_web['upload_driver'] == 2) {
        //         $config_web['upload_driver'] = 1;
        //         $content = serialize($config_web);
        //         Db::name('Config')->where(['name' => 'web'])->strict(false)->field(true)->update(['content' => $content]);
        //         clear_cache('system_config_web');
        //     }
        // }
        return true;
    }

    /**
     * 实现的aliyunOssHook钩子方法
     * @param $param 调用钩子时候的参数信息
     * @return array
     *               用法：
     *               模版中调用：<div>{:hook('aliyunOssHook', ['url'=>''])}</div>
     *               PHP中调用：hook('aliyunOssHook', ['url'=>'']);
     */
    public function aliyunOssHook($param)
    {
        // 当前插件的基础信息，系统优先获取info.ini中的配置信息
        $info = $this->getInfo();

        // 插件禁用后不再进行上传
        if ($info['install'] == 0 || $info['status'] == 0) {
            return json_encode([
                'error' => 1,
                'msg' => '阿里云OSS上传插件已禁用，请启用或更改上传驱动为本地上传',
                'data' => '',
            ]);
        }
        // 上传文件
        return $this->upload($param['url']);
    }

    /**
     * 上传文件
     * @param string $fileName 文件目录
     * @return false|string
     */
    private function upload($fileName = '')
    {
        $fileName = ltrim($fileName, '/');
        if ($fileName) {
            // 当前插件的配置信息，配置信息存在当前目录的config.php文件中
            $config = $this->getConfig();
            //require_once app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . 'aliyunoss' . DIRECTORY_SEPARATOR . 'aliyun-oss-php-sdk-2.4.1' . DIRECTORY_SEPARATOR . 'autoload.php';
            $accessKeyId = $config['access_key_id'];     // AccessKey ID
            $accessKeySecret = $config['access_key_secret']; // AccessKey Secret
            $endpoint = $config['endpoint'];          // Endpoint（地域节点）
            $bucket = $config['bucket'];            // Bucket（存储空间名称）
            $domain = $config['domain'];            // 绑定域名
            $keepFile = $config['keep_file'];         // 保留本地文件
            $object = $fileName;                    // 设置文件名称
            $filePath = app()->getRootPath() . 'public' . get_config('filesystem.disks.public.url') . '/' . $fileName; // 由本地文件路径加文件名包括后缀组成，如/users/local/myfile.txt
            try {
                $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                // 文件上传
                $result = $ossClient->uploadFile($bucket, $object, $filePath);
                if (isset($result['info']['http_code']) && $result['info']['http_code'] == 200) {
                    if ($domain) {
                        $url = rtrim($domain, '/') . '/' . $fileName;
                    } else {
                        $url = $result['info']['url'];
                    }
                    // 删除本地文件
                    if ($keepFile === '0') {
                        unlink($filePath);
                    }
                    return json_encode([
                        'error' => 0,
                        'msg' => '上传成功',
                        'data' => $url,
                    ]);
                }
            } catch (OssException $e) {
                return json_encode([
                    'error' => 1,
                    'msg' => $e->getMessage(),
                    'data' => '',
                ]);
            }
        }
    }
}