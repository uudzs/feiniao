<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use app\api\middleware\Auth;
use app\admin\model\CoinLog as CoinLogModel;

class Coin extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];

    /**
     * 获取金币水流列表
     * Summary of index
     * @return void
     */
    public function index()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        $where = ['user_id' => JWT_UID];
        if (!isset($param['order']) || empty($param['order'])) {
            $param['order'] = 'create_time DESC';
        }
        if (isset($param['limit'])) {
            $param['limit'] = intval($param['limit']);
        }
        $list = (new CoinLogModel())->getCoinLogList($where, $param); 
        $this->apiSuccess('success', $list);
    }
}