<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
//use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;

class App extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];


    /**
     * APP更新
     * Summary of upgrade
     */
    public function upgrade()
    {
        $param = get_params();
        $edition_number = isset($param['edition_number']) ? floatval($param['edition_number']) : 0;
        $platform = isset($param['platform']) ? strtolower(trim($param['platform'])) : '';
        if ($edition_number <= 0 || empty($platform)) {
            $this->apiError('参数错误');
        }
        $where = ['edition_issue' => 1, 'platform' => $platform, ['edition_number', '>', $edition_number]];
        if ($platform == 'harmony') {
            $where['platform'] = 'harmony';
        } else {
            $where['platform'] = 'android|ios';
        }
        $version = Db::name('app_version')->where($where)->order('create_time asc')->find();
        if (!empty($version)) {
            Db::name('app_version')->where('id', $version['id'])->inc('check_num')->update();
            $version['edition_url']=get_file($version['edition_url']);
        }
        $this->apiSuccess('请求成功', $version ?: []);
    }
}
