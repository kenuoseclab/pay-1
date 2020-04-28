<?php
namespace Org\Util\Kxdf;
/**
 * 进钱支付辅助类.
 * User: Michael
 * Date: 2017/4/6
 * Time: 18:42
 */
class AigoHelperAction extends Action
{
    /**
     * 生成签名
     * @param $params
     * @param $signKey
     * @return string
     */
    public function generateSign($params, $signKey) {
        ksort($params);

        $str = '';
        foreach ($params as $key => $param) {

            if ( 'sign' != $key && '' !== $param && 'cipher_data' != $key && 'orderState' != $key && 'orderDesc' != $key) {
                $str .= $key . '=' . ($param) . '&';
            }
        }
        $str .= 'key=' . $signKey;

        return strtoupper(md5($str));
    }

    /**
     * 校验验证码
     * @param $params
     * @param $signKey
     * @return bool
     */
    public function verifySign($params, $signKey) {
        $_sign = $this->generateSign($params, $signKey);

        if ($params['sign'] == $_sign) {
            return true;
        }

        return false;
    }

    /**
     * 公钥加密
     * @param array $params
     * @param string $publicKey
     * @return string
     */
    public function encrypt($params, $publicKey) {
        ksort($params);

        $str = '';
        foreach ($params as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }
        $str = rtrim($str, '&');

        // 读取公钥文件
        $pubKey = $this->getRsaKey($publicKey, 'PUBLIC');
        // 转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        $maxlength = $this->getMaxEncryptBlockSize($res);

        $output='';
        $split = str_split($str, $maxlength);
        foreach($split as $part) {
            openssl_public_encrypt($part, $encrypted, $pubKey, OPENSSL_PKCS1_PADDING);
            $output .= $encrypted;
        }

        $encryptedData = base64_encode($output);

        return $encryptedData;
    }

    /**
     * 私钥加密
     * @param string $data 要解密的数据
     * @param string $privateKey  私钥字符串
     * @return string
     */
    public function decrypt($data, $privateKey) {
        // 读取私钥文件
        $priKey = $this->getRsaKey($privateKey, 'PRIVATE');
        // 转换为openssl格式密钥
        $res = openssl_get_privatekey($priKey);

        $data = base64_decode($data);
        $maxlength = $this->getMaxDecryptBlockSize($res);

        $output = '';
        $split = str_split($data , $maxlength);
        foreach($split as $part){
            openssl_private_decrypt($part, $encrypted, $priKey, OPENSSL_PKCS1_PADDING);
            $output .= $encrypted;
        }

        return $output;
    }

    /**
     * 检测填充类型
     * 加密只支持PKCS1_PADDING
     * 解密支持PKCS1_PADDING和NO_PADDING
     *
     * @param int 填充模式
     * @param string 加密en/解密de
     * @return bool
     */
    private function _checkPadding($padding, $type) {
        if ($type == 'en') {
            switch ($padding) {
                case OPENSSL_PKCS1_PADDING :
                    $ret = true;
                    break;
                default :
                    $ret = false;
            }
        } else {
            switch ($padding) {
                case OPENSSL_PKCS1_PADDING :
                case OPENSSL_NO_PADDING :
                    $ret = true;
                    break;
                default :
                    $ret = false;
            }
        }
        return $ret;
    }

    /**
     *根据key的内容获取最大加密lock的大小，兼容各种长度的rsa keysize（比如1024,2048）
     * 对于1024长度的RSA Key，返回值为117
     * @param $keyRes
     * @return float
     */
    public function getMaxEncryptBlockSize($keyRes){
        $keyDetail = openssl_pkey_get_details($keyRes);
        $modulusSize = $keyDetail['bits'];
        return $modulusSize/8 - 11;
    }
    /**
     * 根据key的内容获取最大解密block的大小，兼容各种长度的rsa keysize（比如1024,2048）
     * 对于1024长度的RSA Key，返回值为128
     * @param $keyRes
     * @return float
     */
    public function getMaxDecryptBlockSize($keyRes){
        $keyDetail = openssl_pkey_get_details($keyRes);
        $modulusSize = $keyDetail['bits'];
        return $modulusSize/8;
    }

    /**
     * 针对公钥和私钥进行修改
     * @param sting str    原字符串
     * @param string type  PRIVATE: 私钥，PUBLIC：公钥
     * @return string
     */
    private function getRsaKey($str, $type) {
        //格式化：公钥
        if ('PUBLIC' == $type) {
            $rsa = trim(str_replace(array('-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n", "\r"), '',
                $str));
            return "-----BEGIN PUBLIC KEY-----\n".chunk_split($rsa, 64, "\n")."-----END PUBLIC KEY-----";
        } else if ('PRIVATE' == $type) {
            $rsa = trim(str_replace(array('-----BEGIN RSA PRIVATE KEY-----', '-----END RSA PRIVATE KEY-----', "\n", "\r"), '',
                $str));
            return "-----BEGIN RSA PRIVATE KEY-----\n".chunk_split($rsa, 64, "\n")."-----END RSA PRIVATE KEY-----";
        }
    }




    /**
     * 加密
     *
     * @param array $params
     * @param string $publicKey
     * @param string $code
     * @param int $padding（貌似php有bug，所以目前仅支持OPENSSL_PKCS1_PADDING）
     * @return string 密文
     */
    public function encrypt1($params, $publicKey, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING) {
        if (! $this->_checkPadding ( $padding, 'en' ))
            $this->_error ( 'padding error' );
        $len = "117";

        ksort($params);
        $str = '';
        foreach ($params as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }
        $str = rtrim($str, '&');

        $strArray = str_split ( $str, $len );
        $ret = false;

        // 读取公钥文件
        $pubKey = $this->getRsaKey($publicKey, 'PUBLIC');
        // 转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        foreach ( $strArray as $cip ) {
            if (openssl_public_encrypt ( $cip, $result, $res, $padding )) {
                $ret .= $result;
            }
        }

        $s = $ret;
        $hex = $this->_encode ( $s, "hex" );
        $ret = $this->_encode ( $ret, "base64" );
        return $ret;
    }

    /**
     * 解密
     *
     * @param string 密文
     * @param string 密文编码（base64/hex/bin）
     * @param int 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING）
     * @param bool 是否翻转明文（When passing Microsoft CryptoAPI-generated RSA cyphertext, revert the bytes in the block）
     * @return string 明文
     */
    public function decrypt1($data, $privateKey, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING, $rev = false) {
        $ret = false;
        $data = $this->_decode ( $data, $code );
        if (! $this->_checkPadding ( $padding, 'de' ))
            $this->_error ( 'padding error' );
        if ($data != false) {
            $len = "128";
            $strArray = str_split ( $data, $len );

            // 读取私钥文件
            $priKey = $this->getRsaKey($privateKey, 'PRIVATE');
            // 转换为openssl格式密钥
            $res = openssl_get_privatekey($priKey);

            foreach ( $strArray as $cip ) {
                if (openssl_private_decrypt ( $cip, $result, $res, $padding )) {
                    $ret .= $result;
                }
            }
        }
        return $ret;
    }



    private function _encode($data, $code) {
        switch (strtolower ( $code )) {
            case 'base64' :
                $data = base64_encode ( $data );
                break;
            case 'hex' :
                $data = bin2hex ( $data );
                break;
            case 'bin' :
            default :
        }
        return $data;
    }

    private function _decode($data, $code) {
        switch (strtolower ( $code )) {
            case 'base64' :
                $data = base64_decode ( $data );
                break;
            case 'hex' :
                $data = $this->_hex2bin ( $data );
                break;
            case 'bin' :
            default :
        }
        return $data;
    }

}