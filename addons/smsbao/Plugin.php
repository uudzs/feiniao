<?php

namespace addons\smsbao;

use think\Addons;


class Plugin extends Addons
{

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function smssend($param)
    {
        $info = $this->getInfo();
        if ($info['install'] == 0 || $info['status'] == 0) {
            return json_encode([
                'code' => 1,
                'msg' => '插件已禁用或未安装',
                'data' => '',
            ]);
        }
        try {
            $config = $this->getConfig();
            if (empty($config) || empty($config['account']) || empty($config['password'])) {
                return json_encode([
                    'code' => 1,
                    'msg' => '请先配置参数',
                    'data' => '',
                ]);
            }
            $account = trim($config['account']);
            $password = trim($config['password']);
            $sign = trim($config['sign']);
            $content = $sign . '您的验证码是：' . $param['code'] . '，请勿泄露。';
            $sendurl = 'http://api.smsbao.com/sms?u=' . $account . "&p=" . md5($password) . "&m=" . $param['phone'] . "&c=" . urlencode($content);
            $result = file_get_contents($sendurl);
            $statusStr = array(
                "0" => "短信发送成功",
                "-1" => "参数不全",
                "-2" => "请求失败",
                "30" => "密码错误",
                "40" => "账号不存在",
                "41" => "余额不足",
                "42" => "帐户已过期",
                "43" => "IP地址限制",
                "50" => "内容含有敏感词"
            );
            if (isset($statusStr[$result])) {
                $message = $statusStr[$result];
                if ($message == '短信发送成功') {
                    return json_encode([
                        'code' => 0,
                        'msg' => $message,
                        'data' => '',
                    ]);
                } else {
                    return json_encode([
                        'code' => 1,
                        'msg' => $message,
                        'data' => '',
                    ]);
                }
            } else {
                return json_encode([
                    'code' => 1,
                    'msg' => '发送错误',
                    'data' => '',
                ]);
            }
        } catch (\Exception $e) {
            return json_encode([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => '',
            ]);
        }
    }
}
