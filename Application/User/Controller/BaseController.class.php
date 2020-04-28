<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-03
 * Time: 1:56
 */

namespace User\Controller;

use Think\Controller;

class BaseController extends Controller
{
    public $_site;
    public $siteconfig;
    const LENGTH = 4; //验证码的长度
    const EXPIRE = 300; //过期时间
    public function __construct()
    {
        parent::__construct();
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
        $this->assign('siteurl', $this->_site);
        $this->assign('sitename', C('WEB_TITLE'));
        //获取系统配置
        $this->siteconfig = M("Websiteconfig")->find();
        $this->assign('siteconfig', $this->siteconfig);
    }

    /**
     * 发送验证码
     * @param  [type] $callInde 要调用的模板代码
     * @param  [type] $mobile 手机号码
     * @param  [type] $product 模板的$product参数
     * @return [type]          [description]
     */
    //protected function send($callIndex, $mobile, $product)
    //{

//        //验证码的长度
//        $length = self::LENGTH;
//        //生成随机验证码
//        $num = range(0, 9);
//        shuffle($num);
//        $randNum      = substr(implode('', $num), 0, $length);
//        $templeData   = getSmsTemplateCode($callIndex);
//        $templateCode = $templeData['template_code'];
//        session('send', null);
//        //记录验证码
//        $sessionCode = 'send.' . $callIndex;
//        $timeSession = 'send.' . $callIndex . '|' . $randNum;
//        session($timeSession, time()); //存入当前生成验证码的时间
//        session($sessionCode, $randNum);
//
//        if ($callIndex == 'loginWarning') {
//            $templeContent = ['time' => time()];
//        } else {
//           //查看模板变量的个数，如果是1个是新模板，2个是旧模板
//            $count = substr_count($templeData['template_content'], '$');
//            //模板参数
//            $templeContent = $count >= 2 ? ['code' => $randNum, 'opration' => $product] : ['code' => $randNum];
//        }
//
//        //发送验证码
//        $res = sendSMS($mobile, $templateCode, $templeContent);
//        if ($res === true) {
//            return ['code' => 1, 'message' => '发送成功'];
//        } else {
//            return ['code' => 0, 'message' => $res];
//        }
//    }
  
  
  
  protected function send($callIndex, $mobile, $product)
    {

        //验证码的长度
        $length = self::LENGTH;
        //生成随机验证码
        $num = range(0, 9);
        shuffle($num);
        $randNum      = substr(implode('', $num), 0, $length);
        $templeData   = getSmsTemplateCode($callIndex);
        $templateCode = $templeData['template_code'];
        session('send', null);
        //记录验证码
        $sessionCode = 'send.' . $callIndex;
        $timeSession = 'send.' . $callIndex . '|' . $randNum;
        session($timeSession, time()); //存入当前生成验证码的时间
        session($sessionCode, $randNum);

        if ($callIndex == 'loginWarning') {
            $templeContent = ['time' => time()];
        } else {
            //查看模板变量的个数，如果是1个是新模板，2个是旧模板
            $count = substr_count($templeData['template_content'], '$');
            //模板参数
            $templeContent = $count >= 2 ? ['code' => $randNum, 'opration' => $product] : ['code' => $randNum];
            // $templeContent = array('code' => $randNum);
        }

        //发送验证码
        $res = jhsjsend($mobile, $templateCode, $randNum);
        if ($res === true) {
            return ['code' => 1, 'message' => '发送成功'];
        } else {
            return ['code' => 0, 'message' => $res];
        }
    }

    /**
     * 发送文本信息
     * @param  [type] $callIndex [description]
     * @param  [type] $mobile    [description]
     * @param  [type] $product   [description]
     * @return [type]            [description]
     */
    protected function sendStr($callIndex, $mobile, $product)
    {
        $templeData    = getSmsTemplateCode($callIndex);
        $templateCode  = $templeData['template_code'];
        $templeContent = ['time' => $product['time'], 'address' => $product['address']];
        //发送
        $res = sendSMS($mobile, $templateCode, $templeContent);
        if ($res === true) {
            return ['code' => 1, 'message' => '发送成功'];
        } else {
            return ['code' => 0, 'message' => $res];
        }
    }

    protected function checkSessionTime($callIndex, $randNum)
    {
        $timeSession = 'send.' . $callIndex . '|' . $randNum;
        $time        = session($timeSession);
        return time() - $time < self::EXPIRE;
    }
}
