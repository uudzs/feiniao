<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use app\admin\model\Advsr as AdvsrModel;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;

class Info extends BaseController
{

    public function index($page = 1)
    {
        $ismakecache = $this->usecache();
        $param = get_params();
        $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
        $list = Db::name('advsr')->where(['status' => 1, 'adver_id' => get_system_config('page', 'home_notice'), 'type' => 6])->order('level desc')->paginate($rows, false, ['query' => $param]);
        View::assign('list', $list);
        if ($ismakecache) $this->makecache(View::fetch());
        return view();
    }

    public function detail()
    {
        $ismakecache = $this->usecache();
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $advsr = new AdvsrModel();
        $advsr::where('id', $id)->inc('hits')->update();
        $detail = $advsr->getAdvsrById($id);
        View::assign('detail', $detail);
        if ($ismakecache) $this->makecache(View::fetch());
        return view();
    }
}
