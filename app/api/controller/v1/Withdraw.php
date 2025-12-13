<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use app\api\middleware\Auth;
use think\facade\Db;

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
            $this->apiError('common.isnotlogin', [], 99);
        }
        $uid = JWT_UID;
        $list = Db::name('withdraw')->where(['user_id' => $uid])->order('create_time Desc')->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $list[$k]['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
        }
        $this->apiSuccess('success', $list);
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
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($card_id) || empty($amount) || empty($securitypass)) {
            $this->apiError('empty');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        if (intval($user['realname_status']) !== 1) {
            $this->apiError('user.authentication');
        }
        if (!password_verify($securitypass, $user['securitypwd'])) {
            $this->apiError('login.passerr');
        }
        $card = Db::name('bank_card')->where(['id' => $card_id])->find();
        if (empty($card)) {
            $this->apiError('404');
        }
        if (intval($card['user_id'] !== JWT_UID)) {
            $this->apiError('404');
        }
        if (intval($card['auth_status'] !== 1)) {
            $this->apiError('user.receivingaccountauthentication');
        }
        if ($card['full_name'] != $user['name']) {
            $this->apiError('inconsistent');
        }
        $conf = get_system_config('withdraw');
        if (intval($conf['open'] !== 1)) {
            $this->apiError('407');
        }
        $tax = floatval($conf['tax']);
        $ratio = floatval($conf['ratio']);
        $price_min = floatval($conf['price_min']);
        $price_max = floatval($conf['price_max']);
        $apply_coin = Db::name('withdraw')->where(['user_id' => JWT_UID, 'status' => 0])->sum('coin'); //提现中
        if (intval($user['coin']) < $amount) {
            $this->apiError('user.withdrawableerr');
        }
        if (intval($user['coin']) < intval($apply_coin)) {
            $this->apiError('user.withdrawableerr');
        }
        $can_coin = intval($user['coin']) - intval($apply_coin);
        if ($can_coin < $amount) {
            $this->apiError('user.withdrawableerr');
        }
        $money = round(($amount / $ratio), 2); //钱
        $tax_money = round(($tax * $money), 2); //税
        $final_money = round(($money - $tax_money), 2); //最终可提现
        if ($final_money < $price_min) {
            $this->apiError('user.minnumbers');
        }
        if ($final_money > $price_max) {
            $this->apiError('user.maxnumbers');
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
            $this->apiSuccess('success');
        } else {
            $this->apiError('fail');
        }
    }
}
