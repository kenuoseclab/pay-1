<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Code\Controller;

use Think\Verify;

/**
 * 用户登录控制器
 * Class LoginController
 * @package Home\Controller
 */
class LoginController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 代理商户登录
     */
    public function index()
    {
        $module = strtolower(trim(__MODULE__, '/'));
        $module = trim($module, './');
        $loginUrl = U($module . "/Login/checklogin");


        $module = strtolower(trim(__MODULE__, '/'));
        $type="商户";
        $bg="shanghu.png";

        if ($module == C('user')) {

            //普通商户
            $type="商户";
            $bg="shanghu.png";
        } else if ($module == C('agent')) {
            //代理商户
            $type="代理";
            $bg="daili.png";
        }
        $this->assign('type', $type);
        $this->assign('bg', $bg);
        $this->assign('loginUrl', $loginUrl);
        $this->display();
    }

    /**
     * 登录验证
     */
    public function checklogin()
    {
        if (IS_POST) {
            $this->check([8], '码商');
            
        }
    }

    /**
     * 检查登录
     * @param  [type] 代理类型 4=>普通商户 5=>代理商户
     * @return [type]
     */
    private function check($type, $typeName = '码商')
    {
        $username     = I('post.username', '', 'trim');
        $password     = I('post.password', '', 'trim');
        $varification = I('post.varification', '', 'trim');
        $cookiename   = I('post.cookiename');
        if (!$username || !$password || !$varification) {
            $this->error('用户名、密码、验证码不能为空！');
        }
        //验证码
        $verify = new Verify();
        if (!$verify->check($varification)) {
            $this->error('验证码输入有误！');
        }
        $fans = M('Member')->where(['username' => $username])->find();

        //判断是白名单登录
        $ip = get_client_ip();
        if (trim($fans['login_ip'])) {
            $ipItem = explode("\r\n", $fans['login_ip']);
            if (!in_array($ip, $ipItem)) {
                $this->error('登录IP错误');
            }
        }
        //登录错误次数检查
        $res = check_auth_error($fans['id'], 0);
        if(!$res['status']) {
            $this->error($res['msg']);
        }

        if(!$fans) {
            $this->error('用户不存在！');
        }
        //不存在
        if ( $fans['status'] != 1) {
            $this->error('用户已被禁用！');
        }
        if ( !in_array($fans['groupid'], $type)) {
            $this->error('用户不存在！');
        }

        //判断用户登录最后一次错误时间是否在昨天
        $lastErrorTime = date('Ymd', $fans['last_error_time']);
        $today         = date('Ymd');
        if ($lastErrorTime > $today) {
            //如果是昨天未超过错误登录次数，重置为0
            M('Member')->where(['id' => $fans['id']])->save(['login_error_num' => 0]);
        }

        //密码验证
        if (md5($password . $fans['salt']) != $fans['password']) {
            log_auth_error($fans['id'],0);
            $this->error('密码输入有误！');
        } else {
            clear_auth_error($fans['id'],6);
            $session_random = randpw(32);
            M('Member')->where(['id' => $fans['id']])->save(['login_error_num' => 0, 'session_random' => $session_random, 'last_login_time' => time()]);
        }
        //用户登录
        $user_auth = [
            'uid'      => $fans['id'],
            'username' => $fans['username'],
            'groupid'  => $fans['groupid'],
            'password' => $fans['password'],
            'session_random' => $session_random
        ];
        //登录后重置session_id
        session_regenerate_id(true);
        session('code_auth', $user_auth);
        ksort($user_auth); //排序
        $code = http_build_query($user_auth); //url编码并生成query字符串
        $sign = sha1($code);
        session('code_auth_sign', $sign);

        // 登录记录
        $rows['userid']        = $fans['id'];
        $rows['logindatetime'] = date("Y-m-d H:i:s");
        //旧的获取地区数据
        // $Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
        // $location = $Ip->getlocation(); // 获取某个IP地址所在的位置

        $location             = \Org\Net\NIpLocation::find($ip); //返回式一个数组，索引0 国家 1省份 2城市
        $rows['loginip']      = $ip;
        $rows['loginaddress'] = $location[1] . "-" . $location[2];
        $rows['type'] = 0;
        //常用地址
        $localCountry = [];
        //获取最近登录地址
        $latestLoginData = M("Loginrecord")->where(['userid' => $fans['id'], 'type'=>0])->order('id desc')->limit(3)->select();
        $address         = @array_column((array) $latestLoginData, 'loginaddress', 'id');
        $address = @array_values($address);
        $country         = @array_map(function ($item) {
            $adress = explode('-', $item);
            $count = 0;
            foreach ($adress as $v) {
                if($v) {$count++;}
            }
            if($count>1) {
                return $adress[1];
            } else {
                return $adress[0];
            }
        }, $address);
        //获取数组中的重复数据
        $repeatItem = @array_unique($country);
        if ($repeatItem) {
            //获取最近三次登录重复的地址
            $localCountry = array_diff_assoc($country, $repeatItem);
        }
        //如果异地登录就发送通知信息
        $sms_is_open = smsStatus();
        $product     = ['time' => date('Y-m-d H:i:s'), 'address' => $location[1] . $location[2]];
        if ($localCountry && !in_array($location[2], $localCountry) && $fans['mobile'] && $sms_is_open) {
            $ret = $this->sendStr('loginWarning', $fans['mobile'], $product);
        } else if ($localCountry && !in_array($location['country'], $localCountry) && $fans['email']) {
            $message = "您的账号于{$product['time']}登录异常，异常登录地址：{$product['address']}，如非本人操纵，请及时修改账号密码。";
            $ret     = sendEmail($fans['email'], C('WEB_TITLE'), $message);
        }

        M("Loginrecord")->add($rows);
        $module = strtolower(trim(__MODULE__, '/'));
        $module = trim($module, './');
        $this->success('登录成功', U($module.'/Index/index'));
    }

    /**
     * 登出
     */
    public function loginout()
    {
        $user_auth = session('code_auth');
        $url       = 'Home_Index_codeLogin.html';
        //session('user_auth', null);
        //session('user_auth_sign', null);
        //session('user_google_auth', null);
      	session(null);
        $this->success('正在退出...', $url);
    }

    /**
     * 注册
     */
    public function register()
    {
        $this->display();
    }

    /**
     * 注册表单
     */
    public function checkRegister()
    {
        if (IS_POST) {
            $username        = I('post.username', '', 'trim');
            $password        = I('post.password', '', 'trim');
            $confirmpassword = I('post.confirmpassword', '', 'trim');
            $email           = I('post.email', '', 'trim');
            $invitecode      = I('post.invitecode', '', 'trim');

            if ($password != $confirmpassword) {
                $this->ajaxReturn(['errono' => 10002, 'msg' => '密码输入不一致!']);
            }

            //邀请码验证
            if ($this->siteconfig['invitecode']) {
                $verifycode = M('Invitecode')
                    ->where(['invitecode' => $invitecode, 'status' => 1, 'yxdatetime' => array('egt', time())])
                    ->find();
                if (!$verifycode) {
                    $this->ajaxReturn(array('errorno' => 10001, 'msg' => '邀请码无效!'));
                }
            }
            $isuserid = M("Member")->where(['username' => $username])->getField("id");
            if ($isuserid) {
                $this->ajaxReturn(array('errorno' => 10005, 'msg' => '用户名重复!'));
            }

            $user = [
                'username'   => $username,
                'password'   => $password,
                'email'      => $email,
                'verifycode' => $verifycode,
            ];
            $userdata = generateUser($user, $this->siteconfig);

            $newuid = M('Member')->add($userdata);
            //添加用户组权限
            /**
             * 不需要使用用户权限
             * author: feng
             * create: 2017/10/21 10:47
             */
            //M('AuthGroupAccess')->add(['uid'=>$newuid,'group_id'=>$_verfycode['regtype'] ? $_verfycode['regtype'] :4]);

            //失效邀请码
            $_failinvitecode = array('syusernameid' => $newuid, 'sydatetime' => time(), 'status' => 2);
            M('Invitecode')->where(['invitecode' => $invitecode])->save($_failinvitecode);
            if($this->siteconfig['register_need_activate']) {
                //发送注册激活邮件
                $returnEmail = sendRegemail($username, $email, $userdata['activate'], $this->siteconfig);
                if ($returnEmail) {
                    $tel    = $this->siteconfig["tel"];
                    $qqlist = $this->siteconfig['qq'];
                    $mail   = explode('@', $email)[1];
                    $this->ajaxReturn(array('errorno' => 0, 'need_activate'=>1,'msg' => array('tel' => $tel, 'qq' => $qqlist, 'email' => $email, 'mail' => 'http://mail.' . $mail)));
                } else {
                    $this->ajaxReturn(['errorno' => 10003, 'msg' => $returnEmail]);
                }
            } else {
                $this->ajaxReturn(['errorno' => 0, 'need_activate'=>0,'msg' => '注册成功！']);
            }
        } else {
            $this->ajaxReturn(array('errorno' => 10004, 'msg' => '注册失败'));
        }
    }

    /**
     * 用户名验证
     */
    public function checkuser()
    {
        $username = I("post.username");
        $userid   = M("Member")->where(['username' => $username])->getField("id");
        $valid    = true;
        if ($userid) {
            $valid = false;
            echo json_encode(array('valid' => $valid));
        } else {
            echo json_encode(array('valid' => $valid));
        }
    }

    /**
     * email 验证
     */
    public function checkemail()
    {
        $email = I("post.email");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array('valid' => false));
            die;
        }
        $userid = M("Member")->where(array('email' => $email))->getField("id");
        $valid  = true;
        if ($userid) {
            $valid = false;
            echo json_encode(array('valid' => $valid));
        } else {
            echo json_encode(array('valid' => $valid));
        }
    }

    /**
     * 邀请码验证
     */
    public function checkinvitecode()
    {
        $invite_code         = I("post.invitecode");
        $Invitecode          = M("Invitecode");
        $where['invitecode'] = $invite_code;
        $where['status']     = 1;
        $where['yxdatetime'] = array('egt', time());
        $id                  = $Invitecode->where($where)->getField("id");
        $valid               = true;
        if ($id) {
            echo json_encode(array('valid' => $valid));
        } else {
            $valid = false;
            echo json_encode(array('valid' => $valid));
        }
    }

    /**
     * 验证码
     */
    public function verifycode()
    {
        $config = array(
            'length'   => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'useImgBg' => false, // 使用背景图片
            'useZh'    => false, // 使用中文验证码
            'useCurve' => false, // 是否画混淆曲线
            'useNoise' => true, // 是否添加杂点
        );
        ob_clean();
        $verify = new Verify($config);
        $verify->entry();
    }

    /**
     * 验证码验证
     */
    public function checkverify()
    {
        $code   = I("request.code", "");
        $verify = new Verify();
        if ($verify->check($code)) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function forgetpwd()
    {
        if (IS_POST) {
            $username        = I("post.username");
            $password        = I('post.password');
            $confirmpassword = I('post.confirmpassword');
            $email           = I('post.email');
            $code            = I('post.varification', '', 'trim');
            if (!$username) {
                $this->ajaxReturn(array('status' => 0, 'msg' => '用户名不能为空'));
            }
            if (!$email) {
                $this->ajaxReturn(array('status' => 0, 'msg' => '邮箱不能为空'));
            }
            if (!$code) {
                $this->ajaxReturn(array('status' => 0, 'msg' => '验证码不能为空'));
            }
            if (!$password || !$confirmpassword) {
                $this->ajaxReturn(array('status' => 0, 'msg' => '密码不能为空'));
            }
            if ($password != $confirmpassword) {
                $this->ajaxReturn(['status' => 0, 'msg' => '密码输入不一致!']);
            }
            $codemodel = M("user_code")->where(['username' => $username, 'email' => $email, 'code' => $code, 'status' => 0, 'type' => 0, 'endtime' => array('gt', time())])->order('id desc')->find();
            if (!$codemodel) {
                $this->ajaxReturn(array('status' => 0, 'msg' => '验证码不正确或过期'));
            }
            $member = M("member")->field('id,salt')->where(array("username" => $username, "email" => $email))->find();
            if ($member && M("member")->where(["id" => $member["id"]])->setField("password", md5($password . $member['salt'])) !== false) {
                M("user_code")->where(['id' => $codemodel["id"]])->save(array("status" => 1, "uptime" => time()));
                $this->ajaxReturn(['status' => 1, 'msg' => '修改成功!']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '修改失败!']);
            }

        }
        $this->display();
    }

    /**
     * 发送邮箱验证码
     * author: feng
     * create: 2017/10/19 10:21
     */
    public function sendUserCode()
    {
        $username = I("post.username");
        $email    = I("post.email");
        if (!$username) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '用户名不能为空'));
        }
        if (!$email) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '邮箱不能为空'));
        }
        $member = M("member")->where(array("username" => $username, "email" => $email))->find();
        if (!$member) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '用户或邮箱不正确'));
        }
        $code        = rand(10000, 99999);
        $returnEmail = sendFindpwdemail($username, $email, $code, $this->siteconfig);
        if ($returnEmail) {
            $curTime = time();
            $data    = array("type" => "0",
                "code"                  => $code,
                "username"              => $username,
                "email"                 => $email,
                "status"                => 0,
                "ctime"                 => time(),
                "endtime"               => ($curTime + 600),
            );
            if (M("user_code")->add($data)) {
                $this->ajaxReturn(array('status' => 1, 'msg' => '发送邮件成功'));
            }
        }
        $this->ajaxReturn(array('status' => 0, 'msg' => '发送邮件失败'));

    }

}
