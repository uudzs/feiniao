<?php

namespace addons\baidupush\controller;

use think\facade\Db;
use think\facade\View;
use app\admin\facade\ThinkAddons;
use think\facade\App;
use think\facade\Session;

set_time_limit(0);
ini_set('memory_limit', '-1');

class Kernel
{

    // 配置信息
    private $config = [];
    private $addons_name = 'baidupush';

    // 初始化
    public function __construct()
    {
        $config    = ThinkAddons::config($this->addons_name);
        $configVal = [];
        foreach ($config as $k => $v) {
            $configVal[$k] = $v['value'] ?? '';
        }
        $this->config = $configVal;
        View::assign('config', $config);
    }

    private function auth()
    {
        $session_admin = get_config('app.session_admin');
        if (!Session::has($session_admin)) {
            die;
        }
    }

    private function temp($action = '')
    {
        return App::getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $this->addons_name . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . app('request')->controller() . DIRECTORY_SEPARATOR . ($action ? $action : app('request')->action()) . '.html';
    }

    public function index()
    {
        $this->auth();
        if (request()->isAjax()) {
            $param = get_params();
            $where = [];
            if (!empty($param['keywords'])) {
                $where[] = ['url', 'like', '%' . $param['keywords'] . '%'];
                unset($param['keywords']);
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $order = empty($param['order']) ? 'id desc' : $param['order'];
            $list = Db::name('addons_baidupush')->where($where)->order($order)->paginate($rows, false, ['query' => $param]);
            return table_assign(0, '', $list);
        }
    }

    public function config()
    {
        $this->auth();
        $param = get_params();
        if (request()->isAjax()) {
            unset($param['addon'], $param['controller'], $param['action']);
            $param['id'] = $this->addons_name;
            $result = ThinkAddons::configPost($param);
            return to_assign($result['code'], $result['msg']);
        } else {
            return view($this->temp());
        }
    }
}
