<?php
namespace addons\aliyunsms;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\Addons;

/**
 * 注意名字不可以修改，只能为Plugin
 */
class Plugin extends Addons	// 需继承think\Addons类
{
    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    public function aliyunSmsSendHook($param)
    {
        // 当前插件的基础信息，系统优先获取info.ini中的配置信息
        $info = $this->getInfo();
        // 插件禁用后不再进行上传
        if ($info['install'] == 0 || $info['status'] == 0) {
            return json_encode([
                'Code' => 1,
                'Message' => '阿里云sms短信插件已禁用或未安装，请启用或安装后再试。',
                'data' => '',
            ]);
        }
        try {
            $config = $this->getConfig();
            # 转换成json
            $templateParam = json_encode(['code' => $param['code']]);
            // 创建客户端
            AlibabaCloud::accessKeyClient($config['access_key_id'], $config['access_key_secret'])->regionId($config['region'])->asDefaultClient();
            // 发送请求
            $result = AlibabaCloud::rpc()
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
            return json_encode($result->toArray());
        } catch (ServerException $e) {
            return json_encode([
                'Code' => 'error',
                'Message' => $e->getErrorMessage(),
                'data' => '',
            ]);
        } catch (ClientException $e) {
            return json_encode([
                'Code' => 'error',
                'Message' => $e->getErrorMessage(),
                'data' => '',
            ]);
        }
    }
}