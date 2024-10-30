<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use idwork\Idwork;

class Vip extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];

    /*
    * 获取记录
    */
    public function log()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $uid = JWT_UID;
        $conf = get_system_config('vip');
        $list = Db::name('vip_log')->where(['user_id' => $uid])->order('expire_time Desc')->select()->toArray();
        foreach ($list as $k => $v) {
            $pid = $v['level'];
            $day_key = 'level_' . $pid . '_day';
            $priceKey = 'level_' . $pid;
            $day = intval($conf[$day_key]);
            $price = intval($conf[$priceKey]);
            $list[$k]['day'] = $day;
            $list[$k]['price'] = $price;
            $list[$k]['expire_time'] = date('Y-m-d', $v['expire_time']);
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        $this->apiSuccess('请求成功', $list);
    }
}
