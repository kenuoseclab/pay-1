<?php
namespace Org\Util\Kxdf;

class pay {

    function send_post($url, $post_data) {
        $postdata = $post_data;
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    function serve_data($data, $md5key, $customerNo, $url) {
        //转成数组保存
        $origial_data = json_decode($data, true);
        //数组转url模式，经过md5加密得到sign的值
        ksort($origial_data);  //按键名升序排列 
        unset($origial_data["sign"]);
        $origial_data_str = urldecode(http_build_query($origial_data)) . "&key=" . $md5key;
        $md5_data = strtolower(md5($origial_data_str));

        //rsa加密
        $origial_data = urldecode(http_build_query($origial_data)) . "&sign=" . $md5_data;
        $key_content = file_get_contents(  './cert/kx/rsa_public.pem');
        $rsa = new RSA();
        $cipher_data = $rsa->encrypt($origial_data);

        //url编码(防止特殊符号在post传输中丢失)后，发送post请求
        $cipher_data = urlencode(base64_encode($cipher_data));
        $return_str = $this->send_post($url, 'cipher_data=' . $cipher_data . '&customerNo=' . $customerNo);
        file_put_contents('./Data/kxDf.txt', "【".date('Y-m-d H:i:s')."】：\r\n".$return_str."\r\n\r\n",FILE_APPEND);
        //返回数据处理
        $return_arr = json_decode($return_str, true);
        if ($return_arr['retcode'] == '0000') {
            //解密数据
            $return_cipher_str = $return_arr['cipher_data']; //提取加密字段
            $return_cipher_str = base64_decode(urldecode($return_cipher_str)); //url解码,base64解码
            $return_cipher_str = $rsa->decrypt($return_cipher_str); //rsa解密,坑,解密出来的数据其实是url模式的参数
            parse_str($return_cipher_str, $return_cipher_arr); //url模式转成数组
            //数据填充回去
            $return_cipher_arr['retcode'] = $return_arr['retcode'];
            $return_cipher_arr['retmsg'] = $return_arr['retmsg'];
            if(isset($return_arr['balance'])) {
                $return_cipher_arr['balance'] = $return_arr['balance'];
            }
            if(isset($return_arr['orderState'])) {
                $return_cipher_arr['orderState'] = $return_arr['orderState'];
            }
            if(isset($return_arr['retmsg'])) {
                $return_cipher_arr['retmsg'] = $return_arr['retmsg'];
            }
            //转成json,返回json
            return json_encode($return_cipher_arr);
        } else {
            return $return_str;
        }
    }

    /**
     * 单笔代付
     * @param type $data
     * @param type $md5key
     * @param type $customerNo
     */
    function payService($data, $md5key, $customerNo) {
        file_put_contents('./Data/kxDf.txt', "【".date('Y-m-d H:i:s')."】提交数据：\r\n".$data."\r\n\r\n",FILE_APPEND);
        $return_data = $this->serve_data($data, $md5key, $customerNo, 'http://df.yongdongchina.com/checkAccount/payOther.action');
        return $return_data;
    }

    /**
     * 单笔代付结果查询
     * @param type $data
     * @param type $md5key
     * @param type $customerNo
     */
    function queryResult($data, $md5key, $customerNo) {
        $return_data = $this->serve_data($data, $md5key, $customerNo, 'http://df.yongdongchina.com/checkAccount/queryOther.action');
        return $return_data;
    }

/**
     * 代付余额查询
     * @param type $data
     * @param type $md5key
     * @param type $customerNo
     */
    function queryBalance($data, $md5key, $merReqNo) {
        $return_data = $this->serve_data($data, $md5key, $merReqNo, 'http://120.27.8.38/cts_ttpay_gw_qk_v1/view/server/queryBalance.php');
        return $return_data;
    }

}
