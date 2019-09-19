<?php

namespace Aliyunsms;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;


class Aliyunsms
{

    /**
     * @param array $config 阿里短信配置参数
     * @param int $phone 被发送人手机号码
     * @param int $code 验证码
     * @return array
     * @throws ClientException
     */
    public static function sendCode($config, $phone, $code)
    {

        $param = ["code"=>(string)$code];

        AlibabaCloud::accessKeyClient($config['accessKeyId'], $config['accessSecret'])
            ->regionId($config['regionId'])// replace regionId as you need
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId'      => "default",
                        'PhoneNumbers'  => (string)$phone,
                        'SignName'      => $config['SignName'],
                        'TemplateCode'  => $config['TemplateCode'],
                        'TemplateParam' => json_encode($param),
                    ],
                ])
                ->request();
            return $result->toArray();
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }
    }

}


