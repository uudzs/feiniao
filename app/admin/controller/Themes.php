<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Config;

class Themes extends BaseController
{

    var $uid;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->uid = get_login_admin('id');
    }
    /**
     * 数据列表
     */
    public function index()
    {
        $floderArr = list_dir('template');
        $config = get_config('theme');
        $themes = [];
        if ($floderArr) {
            foreach ($floderArr as $k => $v) {
                $result = self::getconfig($v);
                if (empty($result) || !isset($result['name']) || empty($result['name'])) continue;
                $themeKey = 'template_' . $result['platform'];
                $result['floder'] = $v;
                $result['isuse'] = (isset($config[$themeKey]) && trim($config[$themeKey]) == $v) ? 1 : 0;
                $themes[] = $result;
            }
        }
        View::assign('themes', $themes);
        return view();
    }

    static private function getconfig($name)
    {
        $result = [];
        $path = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR;
        $copyrightPath = $path . $name . DIRECTORY_SEPARATOR . 'copyright.xml';
        if (file_exists($copyrightPath)) {
            $xmlFile = file_get_contents($copyrightPath);
            $ob = simplexml_load_string($xmlFile);
            $json = json_encode($ob);
            $result = json_decode($json, true);
        }
        $cover = $path . $name . DIRECTORY_SEPARATOR . 'cover.jpg';
        if (file_exists($cover)) {
            $imageData = file_get_contents($cover);
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            $base64Image = base64_encode($imageData);
            $cover = "data:$mimeType;base64,$base64Image";
            $result['cover'] = $cover;
        } else {
            $result['cover'] = '';
        }
        return $result;
    }

    public function setup()
    {
        if (request()->isAjax()) {
            $param = get_params();
            if (!isset($param['name']) || empty($param['name'])) {
                return to_assign(1, '必要参数为空！');
            }
            $name = trim($param['name']);
            $result = self::getconfig($name);
            if (empty($result)) {
                return to_assign(1, '没有配置信息！');
            }
            if (!isset($result['name']) || empty($result['name']) || !isset($result['platform']) || empty($result['platform'])) {
                return to_assign(1, '配置信息有误！');
            }
            $config = get_config('theme');
            if (strtolower($result['platform']) == 'mobile') {
                $config['template_mobile'] = $name;
            }
            if (strtolower($result['platform']) == 'pc') {
                $config['template_pc'] = $name;
            }
            $config_file = app()->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'theme.php';
            if (file_put_contents($config_file, '<?php' . "\n" . 'return ' . var_export($config, true) . ';')) {
                return to_assign(0, '安装成功');
            } else {
                return to_assign(1, '保存失败，请检查权限！');
            }
        }
    }

    public function upgrade()
    {
        if (request()->isAjax()) {
            $param = get_params();
            print_r($param);
        }
    }

    public function market() {}
}
