<?php
namespace Code\Controller;

class SkglController extends UserController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function chongzhi()
    {
        $Userpayapi = M("Userpayapi");
        $find = $Userpayapi->where(["userid" => session("userid")])->find();
        $payapicontent = $find["payapicontent"];
        $defaultpayapi = $find["defaultpayapi"];
        $this->assign("defaultpayapi", $defaultpayapi);
        $array = explode("|", $payapicontent);
        $strarray = "";
        foreach ($array as $key => $val) {
            $strarray = "'" . $val . "'," . $strarray;
        }
        $strarray = $strarray . "''";
        $Payapi = M("Payapi");
        $apilist = $Payapi->where("en_payname in (" . $strarray . ")")->select();
        $this->assign("apilist", $apilist);
        $this->display();
    }

    public function loadbank()
    {
        $payapiid = I("post.payapiid");
        $Payapiconfig = M("Payapiconfig");
        $payapiconfigid = $Payapiconfig->where(["payapiid" => $payapiid])->getField("id");
        $Payapibank = M("Payapibank");
        $bankidlist = $Payapibank->field("systembankid")
            ->where("payapiconfigid = " . $payapiconfigid . " and bankcode <> ''")
            ->select(false);
        $Systembank = M("Systembank");
        $list = $Systembank->where("id in (" . $bankidlist . ")")->select();
        $this->ajaxReturn($list, "json");
    }

    public function checkpaypassword()
    {
        $paypassword = I("post.paypassword", "");
        if ($paypassword == "") {
            exit("nullerror");
        } else {
            $Userpassword = M("Userpassword");
            $count = $Userpassword->where(["userid" => session("userid"), "paypassword" => md5($paypassword)])->count();
            if ($count > 0) {
                exit("ok");
            } else {
                exit("passworderror");
            }
        }
    }

    public function czcz()
    {
        header("Content-type: text/html; charset=utf-8");
        $money = I("post.Money", "");
        $tongdao = I("post.tongdaoname", "");
        $bankcode = I("post.bankname", "");
        $paypassword = I("post.paypassword", "");
        
        if ($money == "" || $tongdao == "" || $bankcode == "" || $paypassword == "") {
            exit("请不要非法提交！");
        } else {
            $Userverifyinfo = M("Userverifyinfo");
            $Md5key = $Userverifyinfo->where(["userid" => session("userid")])->getField("md5key");
            if (! $Md5key) {
                exit("密钥错误 ");
            }
            $Userpassword = M("Userpassword");
            $count = $Userpassword->where(["userid" => session("userid"), "paypassword" => md5($paypassword)])->count();
            if ($count > 0) {
                $url = $_SERVER["HTTP_REFERER"]; // 获取完整的来路URL
                $str = str_replace("http://", "", $url); // 去掉http://
                $strdomain = explode("/", $str); // 以“/”分开成数组
                $domain = $strdomain[0]; // 取第一个“/”以前的字符
                $Payapi = M("Payapi");
                $tongdao = $Payapi->where(["id" => $tongdao])->getField("en_payname");
                
                $str = '<form name="Form1" method="post" action="/Pay_Index.html">';
                $str .= '<input type="hidden" name="pay_tongdao" value="' . $tongdao . '">';
                $str .= '<input type="hidden" name="pay_memberid" value="' . (intval(session("userid")) + 10000) . '">';
                $str .= '<input type="hidden" name="pay_orderid" value="">';
                $str .= '<input type="hidden" name="pay_amount" value="' . $money . '">';
                $pay_applydate = date("Y-m-d H:i:s");
                $str .= '<input type="hidden" name="pay_applydate" value="' . $pay_applydate . '">';
                $str .= '<input type="hidden" name="pay_bankcode" value="' . $bankcode . '">';
                $str .= '<input type="hidden" name="ddlx" value="1">';
                $str .= '<input type="hidden" name="pay_notifyurl" value="' . $this->_site . 'Pay_'.$tongdao.'_notifyurl.html">';
                $str .= '<input type="hidden" name="pay_callbackurl" value="' . $this->_site . 'Pay_'.$tongdao.'_callbackurl.html">';
                $requestarray = array(
                    "pay_memberid" => intval(session("userid")) + 10000,
                    "pay_orderid" => "",
                    "pay_amount" => $money,
                    "pay_applydate" => $pay_applydate,
                    "pay_bankcode" => $bankcode,
                    "pay_notifyurl" => $this->_site . 'Pay_'.$tongdao.'_notifyurl.html',
                    "pay_callbackurl" => $this->_site . 'Pay_'.$tongdao.'_callbackurl.html'
                );

                ksort($requestarray);
                $md5str = "";
                foreach ($requestarray as $key => $val) {
                    $md5str = $md5str . $key . "=" . $val . "&";
                }
                //echo($md5str . "key=" . $Md5key);
                $pay_md5sign = strtoupper(md5($md5str . "key=" . $Md5key));
                $str .= '<input type="hidden" name="pay_md5sign" value="' . $pay_md5sign . '">';
                $str .= '<input type="submit" value="正在跳转中......">';
                $str .= '</form>';
                $str .= '<script>';
                $str .= 'document.Form1.submit();';
                $str .= '</script>';
                echo $str;
            } else {
                exit("支付密码错误！");
            }
        }
        
        // echo $money."----".$tongdao."----".$bankcode."----".$paypassword;
    }

    protected function md5sign($Md5key, $list)
    {
        ksort($list);
        reset($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            $md5str = $md5str . $key . "=>" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        return $sign;
    }
}