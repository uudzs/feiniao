<?php
namespace addons\booktag;

use think\facade\App;
use think\Addons;

/**
 * 注意名字不可以修改，只能为Plugin
 */
class Plugin extends Addons	// 需继承think\Addons类
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

    public function bookTagHook($name = '')
    {
        // 当前插件的基础信息，系统优先获取info.ini中的配置信息
        $info = $this->getInfo();
        // 插件禁用后不再进行上传
        if ($info['install'] == 0 || $info['status'] == 0) {
            return json_encode([
                'code' => 1,
                'msg' => '作品标签插件已禁用或未安装，请启用或安装后再试。',
                'data' => '',
            ]);
        }
        $result = [];
        $rootPath = App::getRootPath();
        // 获取插件目录路径
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $temp_arr = (array) include $config_file;
            foreach ($temp_arr as $key => $value) {
                $result[$key]['name'] = $value['title'];
                $result[$key]['data'] = explode(',', str_ireplace('，', ',', $value['value']));
            }
            unset($temp_arr);
            if ($name & isset($result[$name]) && $result[$name]) {
                return json_encode([
                    'code' => 0,
                    'msg' => '成功',
                    'data' => $result[$name],
                ]);
            } else {
                return json_encode([
                    'code' => 0,
                    'msg' => '成功',
                    'data' => $result,
                ]);
            }
        } else {
            return json_encode([
                'code' => 1,
                'msg' => '配置文件不存在。',
                'data' => '',
            ]);
        }
    }
}