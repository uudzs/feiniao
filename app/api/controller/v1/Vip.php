<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use app\api\middleware\Auth;
use think\facade\Db;

class Vip extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['userlevel', 'rechargeplans', 'paymentmethods']]
    ];

    public function userlevel()
    {
        $level = Db::name('user_level')->where('status', 1)->order('id asc')->select();
        $this->apiSuccess('success', $level);
    }

    public function paymentmethods()
    {
        $methods = [
            0 => [
                'id' => 1,
                'name' => 'wechat',
                'title' => '微信',
            ],
            1 => [
                'id' => 2,
                'name' => 'alipay',
                'title' => '支付宝',
            ]
        ];
        $this->apiSuccess('success', []);
    }

    public function rechargeplans()
    {
        $vipconf = get_system_config('vip');
        $retult = [
            'open' => 0,
            'list' => []
        ];
        if (!empty($vipconf)) {
            $retult['open'] = isset($vipconf['open']) ? $vipconf['open'] : 0;
            unset($vipconf['open'], $vipconf['id']);
            for ($i = 1; $i < 6; $i++) {
                if (isset($vipconf['price_' . $i]) && $vipconf['price_' . $i]) {
                    $retult['list'][$i] = [
                        'id' => $i,
                        'price' => $vipconf['price_' . $i],
                        'title' => $vipconf['title_' . $i],
                    ];
                }
            }
        }
        $this->apiSuccess('success', $retult);
    }

    /*
    * 获取记录
    */
    public function log()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
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
        $this->apiSuccess('success', $list);
    }
}
