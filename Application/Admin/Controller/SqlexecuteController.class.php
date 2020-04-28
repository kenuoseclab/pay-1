<?php
namespace Admin\Controller;

class SqlexecuteController extends BaseController
{

    public function index()
    {
        if ($this->payapi() > 0) {
            echo ($this->TransCode("通道数据添加成功！<br><br>"));
            if ($this->payapiconfig() > 0) {
                echo ($this->TransCode("通道设置数据添加成功！<br><br>"));
                if ($this->systembank() > 0) {
                    echo ($this->TransCode("系统银行添加成功！<br><br>"));
                    if ($this->payapibank() > 0) {
                        echo ($this->TransCode("通道银行添加成功！<br><br>"));
                        if ($this->payapicompatibility() > 0) {
                            echo ($this->TransCode("通道兼容字段添加成功！<br><br>"));
                        } else {
                            exit($this->TransCode("通道兼容字段添加失败！<br><br>"));
                        }
                    } else {
                        exit($this->TransCode("通道银行添加失败！<br><br>"));
                    }
                } else {
                    exit($this->TransCode("系统银行添加失败！<br><br>"));
                }
            } else {
                exit($this->TransCode("通道设置数据添加失败！<br><br>"));
            }
        } else {
            exit($this->TransCode("通道数据添加失败！<br><br>"));
        }
    }

    private function payapi()
    {
        $sqlarray = array(
            array(
                'en_payname' => 'Baofoo',
                'zh_payname' => '宝付',
                'url' => 'http://www.baofoo.com/'
            ),
            array(
                'en_payname' => 'Yeepay',
                'zh_payname' => '易宝',
                'url' => 'http://www.yeepay.com/'
            ),
            array(
                'en_payname' => 'Reapay',
                'zh_payname' => '融宝',
                'url' => 'http://www.reapal.com/'
            ),
            array(
                'en_payname' => 'Dinpay',
                'zh_payname' => '智付',
                'url' => 'http://www.dinpay.com/'
            ),
            array(
                'en_payname' => 'Ips',
                'zh_payname' => '环讯IPS',
                'url' => 'http://www.ips.com/'
            ),
            array(
                'en_payname' => 'Unionpay',
                'zh_payname' => '银联在线',
                'url' => 'http://cn.unionpay.com/'
            ),
            array(
                'en_payname' => 'Yidong',
                'zh_payname' => '中国移动支付',
                'url' => 'https://cmpay.10086.cn/'
            ),
            array(
                'en_payname' => 'Liantong',
                'zh_payname' => '联通沃支付',
                'url' => 'https://epay.10010.com/'
            )
        )
        ;
        
        $sqlstr = "";
        
        $Model = M();
        
        foreach ($sqlarray as $key) {
            $sqlstr = "insert into " . C('DB_PREFIX') . "payapi(en_payname,zh_payname,url) values('" . $key["en_payname"] . "','" . $key["zh_payname"] . "','" . $key["url"] . "');";
            $returnnumber = $Model->execute($sqlstr);
            if ($returnnumber > 0) {
                echo ($this->TransCode("新增通道【" . $key["zh_payname"] . "】<br>"));
            }
        }
        
        // exit($sqlstr);
        
        return $returnnumber;
    }

    private function payapiconfig()
    {
        $Payapi = M("Payapi");
        
        $list = $Payapi->select();
        
        $sqlstr = "";
        
        $Model = M();
        
        foreach ($list as $key) {
            $sqlstr = "insert into " . C('DB_PREFIX') . "payapiconfig(payapiid,websiteid) values(" . $key["id"] . ",0);";
            $returnnumber = $Model->execute($sqlstr);
            if ($returnnumber > 0) {
                echo ($this->TransCode("新增通道【" . $key["zh_payname"] . "】配置数据<br>"));
            }
        }
        
        return $returnnumber;
    }

    private function systembank()
    {
        $sqlarray = array(
            array(
                'bankcode' => 'BOB',
                'bankname' => '北京银行'
            ),
            array(
                'bankcode' => 'CBB',
                'bankname' => '渤海银行'
            ),
            array(
                'bankcode' => 'BEA',
                'bankname' => '东亚银行'
            ),
            array(
                'bankcode' => 'ICBC',
                'bankname' => '中国工商银行'
            ),
            array(
                'bankcode' => 'CEB',
                'bankname' => '中国光大银行'
            ),
            array(
                'bankcode' => 'GDB',
                'bankname' => '广发银行'
            ),
            array(
                'bankcode' => 'HXB',
                'bankname' => '华夏银行'
            ),
            array(
                'bankcode' => 'CCB',
                'bankname' => '中国建设银行'
            ),
            array(
                'bankcode' => 'BCM',
                'bankname' => '交通银行'
            ),
            array(
                'bankcode' => 'CMSB',
                'bankname' => '中国民生银行'
            ),
            array(
                'bankcode' => 'NJCB',
                'bankname' => '南京银行'
            ),
            array(
                'bankcode' => 'NBCB',
                'bankname' => '宁波银行'
            ),
            array(
                'bankcode' => 'ABC',
                'bankname' => '中国农业银行'
            ),
            array(
                'bankcode' => 'PAB',
                'bankname' => '平安银行'
            ),
            array(
                'bankcode' => 'BOS',
                'bankname' => '上海银行'
            ),
            array(
                'bankcode' => 'SPDB',
                'bankname' => '上海浦东发展银行'
            ),
            array(
                'bankcode' => 'SDB',
                'bankname' => '深圳发展银行'
            ),
            array(
                'bankcode' => 'CIB',
                'bankname' => '兴业银行'
            ),
            array(
                'bankcode' => 'PSBC',
                'bankname' => '中国邮政储蓄银行'
            ),
            array(
                'bankcode' => 'CMBC',
                'bankname' => '招商银行'
            ),
            array(
                'bankcode' => 'CZB',
                'bankname' => '浙商银行'
            ),
            array(
                'bankcode' => 'BOC',
                'bankname' => '中国银行'
            ),
            array(
                'bankcode' => 'CNCB',
                'bankname' => '中信银行'
            )
        )
        ;
        
        $sqlstr = "";
        
        $Model = M();
        
        foreach ($sqlarray as $key) {
            $sqlstr = "insert into " . C('DB_PREFIX') . "systembank(bankcode,bankname) values('" . $key["bankcode"] . "','" . $key["bankname"] . "');";
            $returnnumber = $Model->execute($sqlstr);
            if ($returnnumber > 0) {
                echo ($this->TransCode("新增系统银行【" . $key["bankname"] . "】<br>"));
            }
        }
        return $returnnumber;
    }

    private function payapibank()
    {
        $Payapiconfig = M("Payapiconfig");
        
        $Payapi = M("Payapi");
        
        $Payapiconfiglist = $Payapiconfig->where("websiteid = 0")->select();
        
        $Systembank = M("Systembank");
        
        $Systembanklist = $Systembank->select();
        
        $sqlstr = "";
        
        $Model = M();
        
        foreach ($Payapiconfiglist as $Payapiconfigkey) {
            $zh_payname = $Payapi->where("id=" . $Payapiconfigkey["payapiid"])->getField("zh_payname");
            foreach ($Systembanklist as $Systembankkey) {
                $sqlstr = "insert into " . C('DB_PREFIX') . "payapibank(payapiconfigid,systembankid) values(" . $Payapiconfigkey["id"] . "," . $Systembankkey["id"] . ");";
                $returnnumber = $Model->execute($sqlstr);
                if ($returnnumber > 0) {
                    echo ($this->TransCode("新增通道【" . $zh_payname . "】系统银行【" . $Systembankkey["bankname"] . "】<br>"));
                }
            }
            echo ("<br>");
        }
        return $returnnumber;
    }

    private function payapicompatibility()
    {
        $array = array(
            
            'Baofoo' => array(
                'MerchantID',
                'PayID',
                'TradeDate',
                'OrderMoney',
                'ProductName',
                'Amount',
                'ProductLogo',
                'Username',
                'Email',
                'Mobile',
                'AdditionalInfo',
                'Merchant_url',
                'Return_url',
                'Md5Sign',
                'NoticeType'
            ),
            'Yeepay' => array(
                'p0_Cmd',
                'p1_MerId',
                'p2_Order',
                'p3_Amt',
                'p4_Cur',
                'p5_Pid',
                'p6_Pcat',
                'p7_Pdesc',
                'p8_Url',
                'p9_SAF',
                'pa_MP',
                'pd_FrpId',
                'pr_NeedResponse',
                'hmac'
            ),
            'Reapay' => array(
                'service',
                'merchant_ID',
                'notify_url',
                'return_url',
                'sign',
                'sign_type',
                'charset',
                'title',
                'body',
                'order_no',
                'total_fee',
                'payment_type',
                'paymethod',
                'pay_cus_no',
                'defaultbank',
                'seller_email',
                'buyer_email'
            ),
            'Dinpay' => array(
                'bank_code',
                'client_ip',
                'extend_param',
                'extra_return_param',
                'input_charset',
                'interface_version',
                'merchant_code',
                'notify_url',
                'order_amount',
                'order_no',
                'order_time',
                'product_code',
                'product_desc',
                'product_name',
                'product_num',
                'return_url',
                'service_type',
                'show_url',
                'sign'
            ),
            'Ips' => array(
                'Mer_code',
                'Billno',
                'Amount',
                'Date',
                'Currency_Type',
                'Gateway_Type',
                'Lang',
                'Merchanturl',
                'FailUrl',
                'Attach',
                'OrderEncodeType',
                'RetEncodeType',
                'Rettype',
                'ServerUrl',
                'SignMD5'
            ),
            'Unionpay' => array(
                'version',
                'charset',
                'transType',
                'merAbbr',
                'merId',
                'merCode',
                'acqCode',
                'backEndUrl',
                'frontEndUrl',
                'orderTime',
                'orderNumber',
                'commodityName',
                'commodityUrl',
                'commodityUnitPrice',
                'commodityQuantity',
                'transferFee',
                'commodityDiscount',
                'orderAmount',
                'orderCurrency',
                'customerName',
                'defaultPayType'
            ),
            'Yidong' => array(
                'characterSet',
                'callbackUrl',
                'notifyUrl',
                'ipAddress',
                'merchantId',
                'requestId',
                'signType',
                'type',
                'version',
                'merchantCert',
                'hmac',
                'amount',
                'bankAbbr',
                'currency',
                'orderDate',
                'orderId',
                'merAcDate',
                'period',
                'periodUnit',
                'merchantAbbr',
                'productDesc',
                'productId',
                'productName',
                'productNum',
                'reserved1',
                'reserved2',
                'userToken',
                'showUrl',
                'couponsFlag'
            ),
            'Liantong' => array(
                'interfaceVersion',
                'tranType',
                'bankCode',
                'payProducts',
                'merNo',
                'goodsName',
                'goodsDesc',
                'orderDate',
                'orderNo',
                'amount',
                'goodId',
                'merUserId',
                'merExtend',
                'customerName',
                'mobileNo',
                'customerEmail',
                'customerID',
                'charSet',
                'tradeMode',
                'expireTime',
                'reqTime',
                'reqIp',
                'respMode',
                'callbackUrl',
                'serverCallUrl',
                'signType',
                'signMsg'
            )
        )
        ;
        
        $Payapi = M("Payapi");
        
        $sqlstr = "";
        
        $Model = M();
        
        foreach ($array as $k => $val) {
            $payapiid = $Payapi->where("en_payname = '" . $k . "'")->getField("id");
            $zh_payname = $Payapi->where("en_payname = '" . $k . "'")->getField("zh_payname");
            foreach ($val as $key) {
                $sqlstr = "insert into " . C('DB_PREFIX') . "payapicompatibility(payapiid,field) values(" . $payapiid . ",'" . $key . "');";
                $returnnumber = $Model->execute($sqlstr);
                if ($returnnumber > 0) {
                    echo ($this->TransCode("新增通道【" . $zh_payname . "】兼容提交字段<br>"));
                }
            }
            echo ("<br>");
        }
        
        return $returnnumber;
    }

    private function TransCode($Code)
    { // 中文转码
        return iconv("UTF-8", "GBK", $Code);
    }
}
?>
