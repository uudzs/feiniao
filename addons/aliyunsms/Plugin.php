<?php

namespace addons\aliyunsms;

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
                'msg' => '短信插件已禁用或未安装',
                'data' => '',
            ]);
        }
        try {
            $config = $this->getConfig();
            require_once app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            # 转换成json
            $templateParam = json_encode(['code' => $param['code']]);
            // 创建客户端
            \AlibabaCloud\Client\AlibabaCloud::accessKeyClient($config['access_key_id'], $config['access_key_secret'])->regionId($config['region'])->asDefaultClient();
            // 发送请求
            $result = \AlibabaCloud\Client\AlibabaCloud::rpc()
                # 具体产品
                ->product($config['product'])
                ->version('2017-05-25') # 指定版本不可修改
                ->action('SendSms')
                ->method('POST')
                ->host($config['domain']) # host地址
                ->options([
                    'query' => [
                        # 指定区域
                        'RegionId' => $config['region'],
                        //需要发送到那个手机
                        'PhoneNumbers' => $param['phone'],
                        //必填项 签名(需要在阿里云短信服务后台申请)
                        'SignName' => $config['sign_name'],
                        //必填项 短信模板code (需要在阿里云短信服务后台申请)
                        'TemplateCode' => $config['template_code'],
                        //如果在短信中添加了${code} 变量则此项必填 要求为JSON格式
                        'TemplateParam' => $templateParam,
                    ],
                ])->request();
            $result = $result->toArray();
            if ($result && is_array($result) && $result['Code'] == 'OK') {
                return json_encode([
                    'code' => 0,
                    'msg' => '发送成功',
                    'data' => '',
                ]);
            } else {
                return json_encode([
                    'code' => 1,
                    'msg' => $result['Message'],
                    'data' => '',
                ]);
            }
        } catch (\AlibabaCloud\Client\Exception\ServerException $e) {
            return json_encode([
                'code' => 1,
                'msg' => $e->getErrorMessage(),
                'data' => '',
            ]);
        } catch (\AlibabaCloud\Client\Exception\ClientException $e) {
            return json_encode([
                'code' => 1,
                'msg' => $e->getErrorMessage(),
                'data' => '',
            ]);
        }
    }
}