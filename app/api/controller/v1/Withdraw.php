<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use idwork\Idwork;
use app\admin\model\Order as OrderModel;

class Withdraw extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];

    /**
     * 提现记录
     * Summary of log
     * @return void
     */
    public function log()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        $uid = JWT_UID;
        $list = Db::name('withdraw')->where(['user_id' => $uid])->order('create_time Desc')->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $list[$k]['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
        }
        $this->apiSuccess('请求成功', $list);
    }

    /**
     * 提现申请
     * Summary of apply
     * @return void
     */
    public function apply()
    {
        $param = get_params();
        $card_id = isset($param['card_id']) ? intval($param['card_id']) : 0;
        $amount = isset($param['amount']) ? intval($param['amount']) : 0;
        $securitypass = isset($param['securitypass']) ? trim($param['securitypass']) : 0;
        if (empty(JWT_UID)) {
            $this->apiError('请先登录', [], 99);
        }
        if (empty($card_id) || empty($amount) || empty($securitypass)) {
            $this->apiError('参数为空');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('用户不存在');
        }
        if (intval($user['realname_status']) != 1) {
            $this->apiError('请先完成实名认证');
        }
        if (!password_verify($securitypass, $user['securitypwd'])) {
            $this->apiError('安全密码错误');
        }
        $card = Db::name('bank_card')->where(['id' => $card_id])->find();
        if (empty($card)) {
            $this->apiError('收款账户不存在');
        }
        if (intval($card['user_id'] != JWT_UID)) {
            $this->apiError('收款账户不存在');
        }
        if (intval($card['auth_status'] != 1)) {
            $this->apiError('收款账户未认证');
        }
        if ($card['full_name'] != $user['name']) {
            $this->apiError('收款账户与实名名称不一致');
        }
        $conf = get_system_config('withdraw');
        if (intval($conf['open'] != 1)) {
            $this->apiError('未开启提现功能');
        }
        $tax = floatval($conf['tax']);
        $ratio = floatval($conf['ratio']);
        $price_min = floatval($conf['price_min']);
        $price_max = floatval($conf['price_max']);
        $apply_coin = Db::name('withdraw')->where(['user_id' => JWT_UID, 'status' => 0])->sum('coin'); //提现中
        if (intval($user['coin']) < $amount) {
            $this->apiError('可提现金币不够');
        }
        if (intval($user['coin']) < intval($apply_coin)) {
            $this->apiError('可提现金币不够');
        }
        $can_coin = intval($user['coin']) - intval($apply_coin);
        if ($can_coin < $amount) {
            $this->apiError('可提现金币不够');
        }
        $money = round(($amount / $ratio), 2); //钱
        $tax_money = round(($tax * $money), 2); //税
        $final_money = round(($money - $tax_money), 2); //最终可提现
        if ($final_money < $price_min) {
            $this->apiError('未达最低提现标准');
        }
        if ($final_money > $price_max) {
            $this->apiError('超过最高提现标准');
        }
        $data = [
            'user_id' => JWT_UID,
            'tax' => $tax_money,
            'card_id' => $card_id,
            'status' => 0,
            'money' => $final_money,
            'coin' => $amount,
            'create_time' => time(),
        ];
        $result = Db::name('withdraw')->strict(false)->field(true)->insertGetId($data);
        if ($result != false) {
            $this->apiSuccess('提现申请成功');
        } else {
            $this->apiError('提现申请失败');
        }
    }
}
