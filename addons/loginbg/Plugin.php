<?php
namespace addons\loginbg;
use think\Addons;

/**
 * 注意名字不可以修改，只能为Plugin
 */
class Plugin extends Addons
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

    /**
     * 实现的钩子方法
     * @return mixed
     */
    public function admin_login_style($param)
    {
        $info = $this->getInfo();
        if (empty($info) || !isset($info['install']) || intval($info['install']) != 1 || !isset($info['status']) || intval($info['status']) != 1) {
            return '';
        }
        $config = $this->getConfig();
        if (!empty($config) && $config['mode'] == 'random') {
            $getTime = mt_rand(-1, 7);
            $background = $this->getbing_bgpic($getTime);
        } else {            
            $background = $config['pic'];
        }
        if (!$background) {
            return '';
        }
        $bg = "background: url('{$background}') 50% 50% / cover;";
        return $bg;
    }

    /**
     *获取bing背景图
     */
    private function getbing_bgpic($getTime = 0)
    {
        $api = "https://www.bing.com/HPImageArchive.aspx?format=js&idx=$getTime&n=1";
        $data = json_decode(get_url($api), true);
        if ($data && isset($data['images'][0]['url']) && $data['images'][0]['url']) {
            return "https://www.bing.com" . $data['images'][0]['url'] . "_1920x1080.jpg";//如果图片地址存在，则输出图片地址
        } else {
            return false;
        }
    }
}