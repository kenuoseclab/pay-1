<?php
namespace Pay\Lib\QQPay;

/**
 * qpayMch.config.php
 * Created by HelloWorld
 * vers: v1.0.0
 * User: Tencent.com
 */
class QpayMchConf
{
    /**
     * QQ钱包商户号
     */
    const MCH_ID = ''; //此处有修改，与官方示例不同，不用填
    /**
     * 子商户号
     */
    const SUB_MCH_ID = '';

    /**
     * API密钥。
     * QQ钱包商户平台(http://qpay.qq.com/)获取
     */
    const MCH_KEY = ''; //此处有修改，与官方示例不同，不用填
    /**
     * API证书绝对路径。
     * 请将证书放在非web虚拟目录下，防止被下载。
     * QQ钱包商户平台(http://qpay.qq.com/)获取
     */
    const CERT_FILE_PATH = '/xxx/qpay_mch/ssl/cert/apiclient_cert.pem';
    const KEY_FILE_PATH = '/xxx/qpay_mch/ssl/cert/apiclient_key.pem';
    /**
     * 回调url，建议使用https协议，以提高安全性
     */
    const NOTIFY_URL = 'https://10.222.146.71:80/success.xml';

}