<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;
use EasyWeChat\Factory;
use think\facade\Cookie;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Yansongda\Pay\Pay as YansongdaPay;
use Yansongda\Pay\Log;
use think\facade\Route;

class Pay extends BaseController
{
    /**
     *  微信支付
     * Summary of wechat
     * @return \think\response\View
     */
    public function wechat()
    {
        //如果是微信环境
        if (isWeChat()) {
            $wechat = get_system_config('wechat');
            if (intval($wechat['pay_open']) == 1) {
                $param = get_params();
                $order_id = isset($param['order_id']) ? intval($param['order_id']) : 0;
                if (empty($order_id)) {
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '订单号为空', 'url' => furl('/', [], true, 'home')]);
                }
                $order = Db::name('order')->where(['id' => $order_id])->find();
                if (empty($order)) {
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '订单不存在', 'url' => furl('/', [], true, 'home')]);
                }
                $config = [
                    'sandbox'            => false, // 设置为 false 或注释则关闭沙箱模式
                    'app_id'             => $wechat['appid'],
                    'mch_id'             => $wechat['mchid'],
                    'key'                => $wechat['secrect_key'],
                    'cert_path'          => app()->getRootPath() . 'public' . $wechat['cert_url'], //绝对路径！！！！
                    'key_path'           => app()->getRootPath() . 'public' . $wechat['key_url'], // 绝对路径！！！！
                    'notify_url'         => (string) Route::buildUrl('wechat_pay_callback', [])->suffix(true)->domain(true),     // 你也可以在下单时单独设置来想覆盖它
                ];
                $app = Factory::payment($config);
                $jssdk = $app->jssdk;
                $session_user = get_config('app.session_user');
                $token = Cookie::get($session_user);
                if (empty($token)) {
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '请先登录', 'url' => furl('/', [], true, 'home')]);
                }
                try {
                    if (count(explode('.', $token)) != 3) {
                        return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '非法请求', 'url' => furl('/', [], true, 'home')]);
                    }
                    $wechatcnf = get_system_config('token');
                    $decoded = JWT::decode($token, new Key($wechatcnf['secrect'], 'HS256')); //HS256方式,这里要和签发的时候对应
                    $data = json_decode(json_encode($decoded), TRUE);
                    $jwt_data = $data['data'];
                    $uid = $jwt_data['userid'];
                    if (empty($uid)) {
                        return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '请先登录', 'url' => furl('/', [], true, 'home')]);
                    }
                    $third = Db::name('third')->where(['user_id' => $uid])->find();
                    if (empty($third)) {
                        return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '用户不存在', 'url' => furl('/', [], true, 'home')]);
                    }
                    $user = Db::name('user')->where(['id' => $uid])->find();
                    if (empty($user)) {
                        return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '用户不存在', 'url' => furl('/', [], true, 'home')]);
                    }
                    if ($user['id'] != $order['user_id']) {
                        return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '错误订单', 'url' => furl('/', [], true, 'home')]);
                    }
                    //构建统一下单所需的数据               
                    $result = $app->order->unify([
                        'body' => $order['product_type'] == 'vip' ? $order['virtual_info'] : $order['mark'], //商品描述,根据实际情况设置
                        'out_trade_no' => $order['order_id'], // 商户订单号,根据实际情况生成
                        'total_fee' => intval($order['pay_price'] * 100), //订单总金额,单位:分,需要转换成整数
                        'spbill_create_ip' => request()->ip(), // 可选,如不传该参数,SDK 将会自动获取相应 IP 地址
                        'notify_url' => (string) Route::buildUrl('wechat_pay_callback', [])->suffix(true)->domain(true), // 支付结果通知网址,如果不设置则会使用配置里的默认地址
                        'trade_type' => 'JSAPI', // 交易类型,根据实际情况设置请对应换成你的支付方式对应的值类型
                        'openid' => $third['openid'], //用户的openID,根据实际情况获取
                    ]);
                    if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS' && isset($result['prepay_id']) && $result['prepay_id']) {
                        //$payjs = $jssdk->bridgeConfig($result['prepay_id'], false); // 返回 json 字符串，如果想返回数组，传第二个参数 false                        
                        $payjs = $jssdk->sdkConfig($result['prepay_id']);
                        $payjs['oid'] = $order['id'];
                        return view('wechat', ['jssdk' => $payjs, 'code' => 0, 'msg' => '请支付', 'url' => 'javascript:;']);
                    } else {
                        return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => $result['return_msg'], 'url' => furl('/', [], true, 'home')]);
                    }
                } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                } catch (\Exception $e) {  //其他错误
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                } catch (\UnexpectedValueException $e) {  //其他错误
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                } catch (\DomainException $e) {  //其他错误
                    return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                }
            } else {
                return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '支付未开启', 'url' => furl('/', [], true, 'home')]);
            }
        }
        return view('wechat', ['jssdk' => '', 'code' => 1, 'msg' => '敬请期待', 'url' => furl('/', [], true, 'home')]);
    }

    /**
     *  支付宝支付
     * Summary of alipay
     * @return \think\response\View
     */
    public function alipay()
    {
        $conf = get_system_config('alipay');
        if (intval($conf['open']) == 1) {
            $param = get_params();
            $order_id = isset($param['order_id']) ? intval($param['order_id']) : 0;
            if (empty($order_id)) {
                return view('alipay', ['code' => 1, 'msg' => '订单号为空', 'url' => furl('/', [], true, 'home')]);
            }
            $order = Db::name('order')->where(['id' => $order_id])->find();
            if (empty($order)) {
                return view('alipay', ['code' => 1, 'msg' => '订单不存在', 'url' => furl('/', [], true, 'home')]);
            }
            $config = [
                'alipay' => [
                    'default' => [
                        // 必填-支付宝分配的 app_id
                        'app_id' => trim($conf['appid']),
                        // 必填-应用私钥 字符串或路径
                        'app_secret_cert' => $conf['private'],
                        // 必填-应用公钥证书 路径
                        'app_public_cert_path' => app()->getRootPath() . 'public' . $conf['public_cert'],
                        // 必填-支付宝公钥证书 路径
                        'alipay_public_cert_path' => app()->getRootPath() . 'public' . $conf['alipay_public_cert_path'],
                        // 必填-支付宝根证书 路径
                        'alipay_root_cert_path' => app()->getRootPath() . 'public' . $conf['alipay_root_cert_path'],
                        'return_url' => (string) Route::buildUrl('vip', [])->suffix(true)->domain(true),
                        'notify_url' => (string) Route::buildUrl('alipay_h5_pay_callback', [])->suffix(true)->domain(true),
                        // 选填-第三方应用授权token
                        'app_auth_token' => '',
                        // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                        'service_provider_id' => '',
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                        'mode' => YansongdaPay::MODE_NORMAL,
                    ]
                ],
            ];
            YansongdaPay::config($config);
            return YansongdaPay::alipay()->h5([
                'out_trade_no' => $order['order_id'],
                'total_amount' => $order['pay_price'],
                'subject' => $order['product_type'] == 'vip' ? $order['virtual_info'] : $order['mark'],
            ]);
        } else {
            return view('alipay', ['code' => 1, 'msg' => '支付未开启', 'url' => furl('/', [], true, 'home')]);
        }
    }

    public function alipay_h5_pay_callback()
    {
        $conf = get_system_config('alipay');
        if (intval($conf['open']) == 1) {
            $config = [
                'alipay' => [
                    'default' => [
                        // 必填-支付宝分配的 app_id
                        'app_id' => trim($conf['appid']),
                        // 必填-应用私钥 字符串或路径
                        'app_secret_cert' => $conf['private'],
                        // 必填-应用公钥证书 路径
                        'app_public_cert_path' => app()->getRootPath() . 'public' . $conf['public_cert'],
                        // 必填-支付宝公钥证书 路径
                        'alipay_public_cert_path' => app()->getRootPath() . 'public' . $conf['alipay_public_cert_path'],
                        // 必填-支付宝根证书 路径
                        'alipay_root_cert_path' => app()->getRootPath() . 'public' . $conf['alipay_root_cert_path'],
                        'return_url' => (string) Route::buildUrl('vip', [])->suffix(true)->domain(true),
                        'notify_url' => (string) Route::buildUrl('alipay_h5_pay_callback', [])->suffix(true)->domain(true),
                        // 选填-第三方应用授权token
                        'app_auth_token' => '',
                        // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                        'service_provider_id' => '',
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                        'mode' => YansongdaPay::MODE_NORMAL,
                    ]
                ],
            ];
            try {
                YansongdaPay::config($config);
                $result = YansongdaPay::alipay()->callback();
                // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
                // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
                // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
                // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
                // 4、验证app_id是否为该商户本身。
                // 5、其它业务逻辑情况
                if (trim($result->trade_status) === 'TRADE_SUCCESS') {
                    $order_id = trim($result->out_trade_no);
                    if (empty($order_id)) {
                        return false;
                    }
                    $order = Db::name('order')->where(['order_id' => $order_id])->find();
                    if (empty($order)) {
                        return false;
                    }
                    if (intval($order['paid']) != 0) {
                        return false;
                    }
                    $viptask = [];
                    if ($order['product_type'] == 'vip') {
                        $expire_time = 0;
                        $vipconf = get_system_config('vip');
                        if (intval($vipconf['open'] != 1)) {
                            return false;
                        }
                        $pid = $order['pid'];
                        $day_key = 'level_' . $pid . '_day';
                        if (!isset($vipconf[$day_key])) {
                            return false;
                        }
                        $day = intval($vipconf[$day_key]);
                        if ($day <= 0) {
                            return false;
                        }
                        $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => $order['user_id']])->order('expire_time desc')->find();
                        if (!empty($vip) && intval($vip['expire_time']) > time()) {
                            $expire_time = intval($vip['expire_time']) + 86400 * $day;
                        } else {
                            $expire_time = time() + 86400 * $day;
                        }
                        $reward_conf = get_system_config('reward');
                        $viptask = Db::name('task')->where(['user_id' => $order['user_id'], 'taskid' => $reward_conf['vip_id'], 'status' => 0])->find();
                    }
                    //   更新订单
                    Db::startTrans();
                    try {
                        // 执行数据库操作
                        Db::name('order')->where(['id' => $order['id']])->update(['trade_no' => $result->trade_no, 'paid' => 1, 'pay_time' => time(), 'pay_type' => 'alipay', 'is_channel' => 5]);
                        if ($order['product_type'] == 'vip') {
                            if ($viptask) {
                                Db::name('user')->where('id', $order['user_id'])->inc('coin', $viptask['reward'])->update();
                                add_coin_log($order['user_id'], $viptask['reward'], 1, '成为VIP奖励');
                                Db::name('task')->where('id', $viptask['id'])->update(['status' => 1, 'update_time' => time()]);
                            }
                            Db::name('vip_log')->strict(false)->field(true)->insertGetId([
                                'user_id' => $order['user_id'],
                                'level' => $order['pid'],
                                'order_id' => $order['id'],
                                'expire_time' => $expire_time,
                                'status' => 1,
                                'ip' => app('request')->ip(),
                                'create_time' => time()
                            ]);
                            Db::name('order')->where(['id' => $order['id']])->update(['status' => 2]);
                        }
                        // 提交事务
                        Db::commit();
                        return YansongdaPay::alipay()->success();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    public function wechat_pay_callback()
    {
        $wechat = get_system_config('wechat');
        if (intval($wechat['pay_open']) == 1) {
            $config = [
                'sandbox'            => false, // 设置为 false 或注释则关闭沙箱模式
                'app_id'             => $wechat['appid'],
                'mch_id'             => $wechat['mchid'],
                'key'                => $wechat['secrect_key'],
                'cert_path'          => app()->getRootPath() . 'public' . $wechat['cert_url'], //绝对路径！！！！
                'key_path'           => app()->getRootPath() . 'public' . $wechat['key_url'], // 绝对路径！！！！
            ];
            $app = Factory::payment($config);
            $response = $app->handlePaidNotify(function ($message, $fail) {
                // 成功
                if (trim($message['return_code']) === 'SUCCESS' && trim($message['result_code']) === 'SUCCESS') {
                    $order_id = trim($message['out_trade_no']);
                    if (empty($order_id)) {
                        return false;
                    }
                    $order = Db::name('order')->where(['order_id' => $order_id])->find();
                    if (empty($order)) {
                        return false;
                    }
                    if (intval($order['paid']) != 0) {
                        return false;
                    }
                    $viptask = [];
                    if ($order['product_type'] == 'vip') {
                        $expire_time = 0;
                        $conf = get_system_config('vip');
                        if (intval($conf['open'] != 1)) {
                            return false;
                        }
                        $pid = $order['pid'];
                        $day_key = 'level_' . $pid . '_day';
                        if (!isset($conf[$day_key])) {
                            return false;
                        }
                        $day = intval($conf[$day_key]);
                        if ($day <= 0) {
                            return false;
                        }
                        $vip = Db::name('vip_log')->where(['status' => 1, 'user_id' => $order['user_id']])->order('expire_time desc')->find();
                        if (!empty($vip) && intval($vip['expire_time']) > time()) {
                            $expire_time = intval($vip['expire_time']) + 86400 * $day;
                        } else {
                            $expire_time = time() + 86400 * $day;
                        }
                        $reward_conf = get_system_config('reward');
                        $viptask = Db::name('task')->where(['user_id' => $order['user_id'], 'taskid' => $reward_conf['vip_id'], 'status' => 0])->find();
                    }
                    //   更新订单
                    Db::startTrans();
                    try {
                        // 执行数据库操作
                        Db::name('order')->where(['id' => $order['id']])->update(['trade_no' => $message['transaction_id'], 'paid' => 1, 'pay_time' => time(), 'pay_type' => 'weixin', 'is_channel' => 0]);
                        if ($order['product_type'] == 'vip') {
                            if ($viptask) {
                                Db::name('user')->where('id', $order['user_id'])->inc('coin', $viptask['reward'])->update();
                                add_coin_log($order['user_id'], $viptask['reward'], 1, '成为VIP奖励');
                                Db::name('task')->where('id', $viptask['id'])->update(['status' => 1, 'update_time' => time()]);
                            }
                            Db::name('vip_log')->strict(false)->field(true)->insertGetId([
                                'user_id' => $order['user_id'],
                                'level' => $order['pid'],
                                'order_id' => $order['id'],
                                'expire_time' => $expire_time,
                                'status' => 1,
                                'ip' => app('request')->ip(),
                                'create_time' => time()
                            ]);
                            Db::name('order')->where(['id' => $order['id']])->update(['status' => 2]);
                        }
                        // 提交事务
                        Db::commit();
                        return true;
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        return false;
                    }
                } else {
                    return false;
                }
            });
            return $response;
        } else {
            return true; // 返回处理完成
        }
    }
}
