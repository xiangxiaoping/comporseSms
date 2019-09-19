<?php

namespace Sms;

use Aliyunsms\Aliyunsms;

class Sms
{
    /**
     * @var string 应用平台名称（XXX网络科技有限公司）
     */
    private static $app_name = '比心星';

    /**
     * @var string 短信平台账号名称
     */
    private static $sms_id;

    /**
     * @var string 短信平台账户密码
     */
    private static $sms_pwd;

    /**
     * @var string 聚合短信密匙
     */
    private static $juhe_key = '';

    /**
     * @var string 聚合短信模板ID
     */
    private static $juhe_tpl_id = '';

    /**
     * @var array 阿里短信配置参数
     */
    private static $ali_config = [
            'accessKeyId'  => '',
            'accessSecret' => '',
            'regionId'     => 'cn-hangzhou',
            'SignName'     => '',
            'TemplateCode' => '',
        ];

    /**
     * 发送短信入口
     * @param int $phone 要发送到的手机号
     * @param int $code 短信验证码
     * @return bool
     */
    public static function send_content($phone, $code)
    {
        //获取平台名称
        $sys_name = self::$app_name;

        //组装短信发送内容
        $content = '您的短信验证码是：' . $code . '【' . $sys_name . '】';

        //执行短信发送
        $result = self::sendSMS_deal($phone, $content);

        //返回结果 true:成功 false：失败
        return $result;
    }

    /**
     * 短信验证码发送执行
     * @param int $phone 要发送到的手机号
     * @param string $content 发送内容
     * @return bool
     */
    private static function sendSMS_deal($phone, $content)
    {
        //获取短信平台账号密码
        $SmsId  = self::$sms_id;
        $SmsPwd = self::$sms_pwd;

        //短信平台接口url
        $url = "http://service.winic.org:8009/sys_port/gateway/?";

        //短信平台接口url,必要参数
        $data = "id=%s&pwd=%s&to=%s&content=%s&time=";

        //设置登录账号
        $SmsId = iconv('UTF-8', 'GB2312', $SmsId);

        //设置登录密码
        $content = urlencode(iconv("UTF-8", "GB2312", $content));

        //组装发送参数
        $parse_data = sprintf($data, $SmsId, $SmsPwd, $phone, $content);

        //发送curl请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parse_data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);

        //获取短信验证结果
        $result = substr($result, 0, 3);

        //判断短信是否发送成功
        if ($result == "000") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 聚合数据（JUHE.CN）短信API服务接口 （短信的抬头名称在聚合短信模板中自行设置，目前一个账号可以设置20个模板）
     * @param int $phone 被发送人手机号
     * @param int $code 本次发送的验证码
     * @return bool true:成功 ； false:失败
     */
    public static function juhe_sms($phone, $code)
    {

        //设置header信息
        header('content-type:text/html;charset=utf-8');

        //短信接口的URL
        $sendUrl = 'http://v.juhe.cn/sms/send';

        //组装短信数据
        $smsConf = [
            'key'       => self::$juhe_key,    //您申请的APPKEY
            'mobile'    => $phone,             //接受短信的用户手机号码
            'tpl_id'    => self::$juhe_tpl_id, //设置短信模板（申请的短信模板ID）
            'tpl_value' => '#code#=' . $code,  //设置短信内容(根据自己设置的聚合短信模板改动)
        ];

        //执行发送短信
        $content = self::juheSMS_deal($sendUrl, $smsConf, true);

        //根据返回结果，判断短信是否发送成功
        if ($content) {

            //读取json数据
            $result = json_decode($content, true);

            //获取返回数据中的error_code
            $error_code = $result['error_code'];

            //状态为0，说明短信发送成功 ;状态非0，说明失败
            if ($error_code == 0) {

                //echo "短信发送成功,短信ID：" . $result['result']['sid'];

                return true;

            } else {

                //$msg = $result['reason'];
                //echo "短信发送失败(" . $error_code . ")：" . $msg;

                return false;
            }
        } else {

            //返回内容异常，以下可根据业务逻辑自行修改
            //echo "请求发送短信失败";

            return false;
        }
    }

    /**
     * 聚合数据短信 请求接口返回内容
     * @param string $url 请求的URL地址
     * @param bool $params 请求的短信参数
     * @param bool $is_post true:post请求 ； false：get请求
     * @return string
     */
    private static function juheSMS_deal($url, $params = false, $is_post = false)
    {

        $httpInfo = [];

        //组装curl请求数据
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //判断请求方式
        if ($is_post) {
            //添加curl post请求数据
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);

        } else {

            //判断是否有短信参数
            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        //执行curl请求
        $response = curl_exec($ch);
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }

    /**
     * 阿里发送短信入口
     * @param int $phone 被发送人手机号
     * @param int $code 验证码
     * @return bool true:成功 false： 失败
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public static function aliyun_sms($phone,$code){

        //获取阿里短信配置
        $config = self::$ali_config;

        //执行发送短信
        $result = Aliyunsms::sendCode($config,$phone,$code);

        //根据返回结果，判断短信是否发送成功 code:OK 表示成功，否则都是失败，可以打印 $result 查看具体返回结果
        if($result['code'] != 'OK'){

            return true;

        }else{

            return false;
        }

    }
}

