<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-02
 * Time: 23:01
 */

namespace Admin\Controller;

use Org\Util\Str;
use Pay\Model\ComplaintsDepositModel;
use Think\Page;

/**
 * 用户管理控制
 * Class UserController
 * @package Admin\Controller
 */

class UserController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        //通道
        $channels = M('Channel')
            ->where(['status' => 1])
            ->field('id,code,title,paytype,status')
            ->select();
        $this->assign('channels', json_encode($channels));
        $this->assign('channellist', $channels);
    }

    /**
     * 用户列表
     */
    public function index()
    {

        $groupid     = I('get.groupid', '');
        $username    = I("get.username", '', 'trim');
        $status      = I("get.status");
        $authorized  = I("get.authorized");
        $parentid    = I('get.parentid');
        $regdatetime = I('get.regdatetime');

        if ($groupid != '') {
            $where['groupid'] = $groupid != 1 ? $groupid : ['neq', '4'];
        }

        if (!empty($username) && !is_numeric($username)) {
            $where['username'] = ['like', "%" . $username . "%"];
        } elseif (intval($username) - 10000 > 0) {
            $where['id'] = intval($username) - 10000;
        }

        if ($status != '') {
            $where['status'] = $status;
        }

        if ($authorized != '') {
            $where['authorized'] = $authorized;
        }

        if (!empty($parentid) && !is_numeric($parentid)) {
            $User              = M("Member");
            $pid               = $User->where(['username' => $parentid])->getField("id");
            $where['parentid'] = $pid;
        } elseif ($parentid) {
            $where['parentid'] = $parentid;
        }

        if ($regdatetime) {
            list($starttime, $endtime) = explode('|', $regdatetime);
            $where['regdatetime']      = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        //统计
        if ($status == 1) {
            //商户数量
            $stat['membercount'] = M('Member')->where(['status' => 1, 'groupid' => 4])->count();
            //代理数量
            $stat['agentcount'] = M('Member')->where(['status' => 1, 'groupid' => ['gt', 4]])->count();
            //可提现金额
            $stat['balance'] = M('Member')->where(['status' => 1])->sum('balance');
            //冻结金额
            $stat['blockedbalance'] = M('Member')->where(['status' => 1])->sum('blockedbalance');
            //冻结保证金
            $stat['complaints_deposit_freeze'] = M('complaints_deposit')->where(['status' => 0])->sum('freeze_money');
            //已结算保证金
            $stat['complaints_deposit_unfreeze'] = M('complaints_deposit')->where(['status' => 1])->sum('freeze_money');
            foreach ($stat as $k => $v) {
                $stat[$k] = $v + 0;
            }
            $this->assign('stat', $stat);
        }
        $count = M('Member')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }

        $page = new Page($count, $rows);
        $list = M('Member')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('regdatetime desc')
            ->select();

        foreach ($list as $k => $v) {
            $list[$k]['groupname']               = $this->groupId[$v['groupid']];
            $deposit                             = ComplaintsDepositModel::getComplaintsDeposit($v['id']);
            $list[$k]['complaintsDeposit']       = number_format((double) $deposit['complaintsDeposit'], 2, '.', '');
            $list[$k]['complaintsDepositPaused'] = number_format((double) $deposit['complaintsDepositPaused'], 2, '.', '');
        }
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    public function invitecode()
    {
        $invitecode = I("get.invitecode");
        $fbusername = I("get.fbusername");
        $syusername = I("get.syusername");
        $regtype    = I("get.groupid");
        $status     = I("get.status");
        if (!empty($invitecode)) {
            $where['invitecode'] = ["like", "%" . $invitecode . "%"];
        }
        if (!empty($fbusername)) {
            $fbusernameid          = M("Member")->where("username = '" . $fbusername . "'")->getField("id");
            $where['fmusernameid'] = $fbusernameid;
        }
        if (!empty($syusername)) {
            $syusernameid          = M("Member")->where("username = '" . $syusername . "'")->getField("id");
            $where['syusernameid'] = $syusernameid;
        }
        if (!empty($regtype)) {
            $where['regtype'] = $regtype;
        }
        $regdatetime = urldecode(I("request.regdatetime"));
        if ($regdatetime) {
            list($cstime, $cetime) = explode('|', $regdatetime);
            $where['fbdatetime']   = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        if (!empty($status)) {
            $where['status'] = $status;
        }
        $count = M('Invitecode')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size);
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Invitecode')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        $Admin = M('Admin');
        foreach ($list as $k => $v) {
            if ($v['is_admin']) {
                $username                 = $Admin->where(['id' => $v['fmusernameid']])->getField('username');
                $list[$k]['fmusernameid'] = $username;
            } else {
                $list[$k]['fmusernameid'] = getusername($v['fmusernameid']);
            }
            $list[$k]['is_admin']  = $v['is_admin'] ? '管理员' : '代理商';
            $list[$k]['groupname'] = $this->groupId[$v['regtype']];
        }
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    public function setInvite()
    {
        $data = M("Inviteconfig")->find();
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 保存邀请码设置
     */
    public function saveInviteConfig()
    {
        if (IS_POST) {
            $Inviteconfig                   = M("Inviteconfig");
            $_formdata['invitezt']          = I('post.invitezt');
            $_formdata['invitetype2number'] = I('post.invitetype2number');
            $_formdata['invitetype2ff']     = I('post.invitetype2ff');
            $_formdata['invitetype5number'] = I('post.invitetype5number');
            $_formdata['invitetype5ff']     = I('post.invitetype5ff');
            $_formdata['invitetype6number'] = I('post.invitetype6number');
            $_formdata['invitetype6ff']     = I('post.invitetype6ff');
            $result                         = $Inviteconfig->where(array('id' => I('post.id')))->save($_formdata);
            $this->ajaxReturn(['status' => $result]);
        }
    }

    /**
     * 查看码商所有子账号
     */
    public function allChannelAccount(){
        $owner_id = I('get.uid');
        $userinfo=M('member')->getById($owner_id);
        $userinfo['memberid']=$owner_id+10000;
        $where['add_user_id']=$owner_id;
        if($_GET['aid']){
            $where['id']=I('get.aid');
        }
        $accounts   = M('channel_account')->where($where)->select();
        $this->assign('userinfo', $userinfo);
        $this->assign('accounts', $accounts);
        $this->display();
    }

    /**
     * 添加邀请码
     */
    public function addInvite()
    {
        $invitecode = $this->createInvitecode();
        $this->assign('invitecode', $invitecode);
        $this->assign('datetime', date('Y-m-d H:i:s', time() + 86400));
        $this->display();
    }

    /**
     * 邀请码
     * @return string
     */
    private function createInvitecode()
    {
        $invitecodestr = random_str(C('INVITECODE')); //生成邀请码的长度在Application/Commom/Conf/config.php中修改
        $Invitecode    = M("Invitecode");
        $id            = $Invitecode->where("invitecode = '" . $invitecodestr . "'")->getField("id");
        if (!$id) {
            return $invitecodestr;
        } else {
            $this->createInvitecode();
        }
    }

    /**
     * 添加邀请码
     */
    public function addInvitecode()
    {
        if (IS_POST) {
            $invitecode = I('post.invitecode');
            $yxdatetime = I('post.yxdatetime');
            $regtype    = I('post.regtype');
            $Invitecode = M("Invitecode");

            $_formdata = array(
                'invitecode'     => $invitecode,
                'yxdatetime'     => strtotime($yxdatetime),
                'regtype'        => $regtype,
                'fmusernameid'   => session('admin_auth.uid'),
                'inviteconfigzt' => 1,
                'fbdatetime'     => time(),
                'is_admin'       => 1,
            );
            $result = $Invitecode->add($_formdata);
            $this->ajaxReturn(['status' => $result]);
        }
    }

    /**
     * 删除邀请码
     */
    public function delInvitecode()
    {
        if (IS_POST) {
            $id  = I('post.id', 0, 'intval');
            $res = M('Invitecode')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    public function getRandstr()
    {
        echo random_str();
    }
    public function batchdel()
    {
        $ids  = I("post.ids");
        $ids  = trim($ids, ',');
        $type = M("User")->where(array('id' => array('in', $ids)))->delete();
        M('Money')->where(array('userid' => array('in', $ids)))->delete();
        M('userbasicinfo')->where(array('userid' => array('in', $ids)))->delete();
        M('userpassword')->where(array('userid' => array('in', $ids)))->delete();
        M('userpayapi')->where(array('userid' => array('in', $ids)))->delete();
        M('Userrate')->where(array('userid' => array('in', $ids)))->delete();
        M('userverifyinfo')->where(array('userid' => array('in', $ids)))->delete();
        if ($type) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    /**
     * 删除用户
     */
    public function delUser()
    {
        if (IS_POST) {
            $id  = I('post.uid', 0, 'intval');
            $res = M('Member')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //一键下号码商
    public function downAllAccount(){
        if (IS_POST) {
            $id  = I('post.uid', 0, 'intval');
            $res = M('channelAccount')->where(['add_user_id' => $id])->save(['status'=>0]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    public function upAllAccount(){
        if (IS_POST) {
            $id  = I('post.uid', 0, 'intval');
            $res = M('channelAccount')->where(['add_user_id' => $id])->save(['status'=>1]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //导出用户
    public function exportuser()
    {
        $username   = I("get.username");
        $status     = I("get.status");
        $authorized = I("get.authorized");
        $parentid   = I("get.parentid");
        $groupid    = I("get.groupid");
        $is_agent   = I("get.isagent");

        if (is_numeric($username)) {
            $map['id'] = array('eq', intval($username) - 10000);
        } else {
            if ($username) {
                $map['username'] = array('like', '%' . $username . '%');
            }
        }
        if ($status) {
            $map['status'] = array('eq', $status);
        }
        if ($authorized) {
            $map['authorized'] = array("eq", $authorized);
        }
        if ($parentid) {
            if (is_numeric($parentid)) {
                $sjuserid = M('Member')->where(["id" => ($parentid - 10000)])->getField("id");
            } else {
                if ($parentid) {
                    $sjuserid = M('Member')->where(["username" => ["like", '%' . $parentid . '%']])->getField("id");
                }
            }
            $map['parentid'] = array('eq', $sjuserid);
        }
        $regdatetime = urldecode(I("request.regdatetime"));
        if ($regdatetime) {
            list($cstime, $cetime) = explode('|', $regdatetime);
            $map['regdatetime']    = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        if ($is_agent && !$groupid) {
            $map['agent_cate'] = ['gt', 4];
        } else {
            $map['groupid'] = 4;
        }
        $map['groupid'] = $groupid ? array('eq', $groupid) : array('neq', 0);

        $title = array('用户名', '商户号', '用户类型', '上级用户名', '状态', '认证', '可用余额', '冻结余额', '注册时间');
        $data  = M('Member')
            ->where($map)
            ->select();
        foreach ($data as $item) {
            $usertypestr = $this->groupId[$item['groupid']];
            switch ($item['status']) {
                case 0:
                    $userstatus = '未激活';
                    break;
                case 1:
                    $userstatus = '正常';
                    break;
                case 2:
                    $userstatus = '已禁用';
                    break;
            }
            switch ($item['authorized']) {
                case 1:
                    $rzstauts = '已认证';
                    break;
                case 0:
                    $rzstauts = '未认证';
                    break;
                case 2:
                    $rzstauts = '等待审核';
                    break;
            }
            $list[] = array(
                'username'    => $item['username'],
                'userid'      => $item['id'] + 10000,
                'groupid'     => $usertypestr,
                'parentid'    => getParentName($item['parentid'], 1),
                'status'      => $userstatus,
                'authorized'  => $rzstauts,
                'total'       => $item['balance'],
                'block'       => $item['blockedbalance'],
                'regdatetime' => date('Y-m-d H:i:s', $item['regdatetime']),
            );
        }
        $numberField = ['total'];
        exportexcel($list, $title, $numberField);
    }

    public function jbxx()
    {
        $userid           = I("post.userid", 0, 'intval');
        $Userbasicinfo    = M("Userbasicinfo");
        $list             = $Userbasicinfo->where(["userid" => $userid])->find();
        $list['username'] = M('User')->where(array('id' => $userid))->getField('username');
        $list['usertype'] = M('User')->where(array('id' => $userid))->getField('usertype');
        $this->ajaxReturn($list, "json");
    }

    public function editjbxx()
    {
        if (IS_POST) {
            $rows['fullname']    = I('post.fullname');
            $rows['sfznumber']   = I('post.sfznumber');
            $rows['birthday']    = I('post.birthday');
            $rows['phonenumber'] = I('post.phonenumber');
            $rows['qqnumber']    = I('post.sfznumber');
            $rows['address']     = I('post.address');
            $rows['sex']         = I('post.sex');
            $usertype            = I('post.usertype');
            M('User')->where(array('id' => I('post.userid')))->save(array('usertype' => $usertype));
            $returnstr = M("Userbasicinfo")->where(array('id' => I('post.id')))->save($rows);
            if ($returnstr == 1 || $returnstr == 0) {
                exit("ok");
            } else {
                exit("no");
            }
        }
    }

    public function zhuangtai()
    {
        $userid = I("post.userid", 0, 'intval');
        $User   = M("User");
        $status = $User->where(["id" => $userid])->getField("status");
        exit($status);
    }

    public function xgzhuangtai()
    {
        $userid         = I("post.userid", 0, 'intval');
        $status         = I("post.status");
        $User           = M("User");
        $data["status"] = $status;
        $returnstr      = $User->where(["id" => $userid])->save($data);
        if ($returnstr == 1 || $returnstr == 0) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function renzheng()
    {
        $userid         = I("post.userid", 0, 'intval');
        $Userverifyinfo = M("Userverifyinfo");
        $list           = $Userverifyinfo->where(["userid" => $userid])->find();
        $this->ajaxReturn($list, "json");
    }

    /**
     * 保存认证
     */
    public function editAuthoize()
    {
        if (IS_POST) {
            $rows   = I('post.u');
            $userid = intval($rows['userid']);
            unset($rows['userid']);
            $res = M('Member')->where(['id' => $userid])->save($rows);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    public function renzhengeditdomain()
    {
        $userid         = I("post.userid", 0, 'intval');
        $domain         = I("post.domain");
        $Userverifyinfo = M("Userverifyinfo");
        $data["domain"] = $domain;
        $returnstr      = $Userverifyinfo->where(["userid" => $userid])->save($data);
        if ($returnstr == 1 || $returnstr == 0) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function renzhengeditmd5key()
    {
        $userid         = I("post.userid", 0, 'intval');
        $md5key         = I("post.md5key");
        $Userverifyinfo = M("Userverifyinfo");
        $data["md5key"] = $md5key;
        $returnstr      = $Userverifyinfo->where(["userid" => $userid])->save($data);
        if ($returnstr == 1 || $returnstr == 0) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    /**
     * 修改密码
     */
    public function editPassword()
    {
        if (IS_POST) {
            $userid  = I("post.userid", 0, 'intval');
            $salt    = I("post.salt");
            $groupid = I('post.groupid');
            $u       = I('post.u');
            if ($u['password']) {
                $data['password'] = md5($u['password'] . ($groupid < 4 ? C('DATA_AUTH_KEY') : $salt));
            }
            if ($u['paypassword']) {
                $data['paypassword'] = md5($u['paypassword']);
            }
            $res = M('Member')->where(["id" => $userid])->save($data);
            $this->ajaxReturn(['status' => $res]);
        } else {
            $userid = I('get.uid', 0, 'intval');
            if ($userid) {
                $data = M('Member')
                    ->where(['id' => $userid])->find();
                $this->assign('u', $data);
            }

            $this->display();
        }
    }

    public function bankcard()
    {
        $userid   = I("post.userid", 0, 'intval');
        $Bankcard = M("Bankcard");
        $list     = $Bankcard->where("userid=" . $userid)->find();
        $this->ajaxReturn($list, "json");
    }

    public function editbankcard()
    {
        if (IS_POST) {
            $id   = I('post.id', 0, 'intval');
            $rows = [
                'bankname'     => I('post.bankname', '', 'trim'),
                'bankzhiname'  => I('post.bankzhiname', '', 'trim'),
                'banknumber'   => I('post.banknumber', '', 'trim'),
                'bankfullname' => I('post.bankfullname', '', 'trim'),
                'sheng'        => I('post.sheng', '', 'trim'),
                'shi'          => I('post.shi', '', 'trim'),
            ];
            $returnstr = M("Bankcard")->where(['id' => $id])->save($rows);
            if ($returnstr == 1 || $returnstr == 0) {
                exit("ok");
            } else {
                exit("no");
            }
        }
    }

    public function suoding()
    {
        $id               = I("post.id", 0, 'intval');
        $disabled         = I("post.disabled");
        $data["disabled"] = $disabled;
        if ($disabled == 0) {
            $data["jdatetime"] = date("Y-m-d H:i:s");
        }
        $Bankcard  = M("Bankcard");
        $returnstr = $Bankcard->where(["id" => $id])->save($data);
        if ($returnstr == 1 || $returnstr == 0) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function tongdao()
    {
        $userid     = I("post.userid", 0, 'intval');
        $Userpayapi = M("Userpayapi");
        $list       = $Userpayapi->where(["userid" => $userid])->find();
        if (!$list) {
            $Payapiconfig              = M("Payapiconfig");
            $payapiid                  = $Payapiconfig->where("`default`=1")->getField("payapiid");
            $Payapi                    = M("Payapi");
            $en_payname                = $Payapi->where(["id" => $payapiid])->getField("en_payname");
            $Userpayapi->userid        = $userid;
            $Userpayapi->payapicontent = $en_payname . "|";
            $Userpayapi->add();
            $list = $Userpayapi->where(["userid" => $userid])->find();
        }
        $Payapiconfig     = M("Payapiconfig");
        $payapiid         = $Payapiconfig->where("`default`=1")->getField("payapiid");
        $Payapi           = M("Payapi");
        $en_payname       = $Payapi->where(["id" => $payapiid])->getField("en_payname");
        $list["disabled"] = $en_payname;

        $Payapiconfig = M("Payapiconfig");
        $payapiidstr  = $Payapiconfig->field("payapiid")
            ->where("disabled=1")
            ->select(false);
        $Payapi             = M("Payapi");
        $listlist           = $Payapi->where("id in (" . $payapiidstr . ")")->select();
        $payapiaccountarray = array();
        foreach ($listlist as $key) {

            $Userpayapizhanghao = M("Userrate");
            $val                = $Userpayapizhanghao->where("userid=" . $userid . " and payapiid=" . $key["id"])->getField("defaultpayapiuserid");
            if (!$val) {
                $Payapiaccount = M("Payapiaccount");
                $val           = $Payapiaccount->where("payapiid=" . $key["id"] . " and defaultpayapiuser=1")->getField("id");
            }
            $payapiaccountarray[$key["en_payname"] . $key["id"]] = $val;
        }

        $obj = array(
            'list'               => $list,
            'payapiaccountarray' => $payapiaccountarray,
        );

        $this->ajaxReturn($obj, "json");
    }

    public function edittongdao()
    {
        $userid     = I("post.userid", 0, 'intval');
        $selecttype = I("post.selecttype");
        $payname    = I("post.payname");

        $Userpayapi    = M("Userpayapi");
        $payapicontent = $Userpayapi->where(["userid" => $userid])->getField("payapicontent");
        if ($selecttype == 1) {
            $payapicontent = str_replace($payname . "|", "", $payapicontent);
        } else {
            $payapicontent = $payapicontent . $payname . "|";
        }
        $data["payapicontent"] = $payapicontent;
        $num                   = $Userpayapi->where(["userid" => $userid])->save($data);
        if ($num) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function editdefaultpayapiuser()
    {
        $userid   = I("post.userid", 0, 'intval');
        $payapiid = I("post.payapiid");
        $val      = I("post.val");

        $Userpayapizhanghao = M("Userrate");
        $list               = $Userpayapizhanghao->where(["userid" => $userid, "payapiid" => $payapiid])->select();
        if (!$list) {
            $data["userid"]              = $userid;
            $data["payapiid"]            = $payapiid;
            $data["defaultpayapiuserid"] = $val;
            $Userpayapizhanghao->add($data);
        } else {
            $data["defaultpayapiuserid"] = $val;
            $Userpayapizhanghao->where(["userid" => $userid, "payapiid" => $payapiid])->save($data);
        }
        exit("ok");
    }

    public function feilv()
    {
        $userid       = I("post.userid", 0, 'intval');
        $Payapiconfig = M("Payapiconfig");
        $payapiidstr  = $Payapiconfig->field("payapiid")
            ->where("disabled=1")
            ->select(false);
        $Payapi             = M("Payapi");
        $listlist           = $Payapi->where("id in (" . $payapiidstr . ")")->select();
        $payapiaccountarray = array();
        foreach ($listlist as $key) {

            $Userpayapizhanghao = M("Userrate");
            $val                = $Userpayapizhanghao->where(["userid"=>$userid ,"payapiid" => $key["id"]])->getField("feilv");
            if (!$val) {
                $Payapiaccount = M("Payapiaccount");
                $val           = $Payapiaccount->where(["payapiid" => $key["id"], "defaultpayapiuser"=>1])->getField("defaultrate");
            }

            $val2 = $Userpayapizhanghao->where(["userid" => $userid,"payapiid" => $key["id"]])->getField("fengding");
            if (!$val2) {
                $Payapiaccount = M("Payapiaccount");
                $val2          = $Payapiaccount->where(["payapiid" => $key["id"], "defaultpayapiuser"=>1])->getField("fengding");
            }

            $payapiaccountarray[$key["en_payname"] . $key["id"]] = $val . "|" . $val2;
        }

        $this->ajaxReturn($payapiaccountarray, "json");
    }

    public function editfeilv()
    {
        $userid             = I("post.userid", 0, 'intval');
        $payapiid           = I("post.payapiid", 0, 'intval');
        $val1               = I("post.feilvval", "") ? I("post.feilvval", "") : 0;
        $val2               = I("post.fengdingval", "") ? I("post.fengdingval", "") : 0;
        $Userpayapizhanghao = M("Userrate");
        $list               = $Userpayapizhanghao->where(["userid" => $userid , "payapiid" => $payapiid])->select();
        if (!$list) {
            $data["userid"]   = $userid;
            $data["payapiid"] = $payapiid;
            $data["feilv"]    = $val1;
            $data["fengding"] = $val2;
            $Userpayapizhanghao->add($data);
        } else {
            $data["feilv"]    = $val1;
            $data["fengding"] = $val2;
            $Userpayapizhanghao->where(["userid" => $userid, "payapiid" => $payapiid])->save($data);
        }
        exit("ok");
    }

    public function tksz()
    {
        $userid           = I("post.userid",0,'intval');
        $User             = M("User");
        $usertype         = $User->where(["id"=>$userid])->getField("usertype");
        $websiteid        = $User->where(["id"=>$userid])->getField("websiteid");
        $useriduserid     = $userid;
        $Payapiconfig     = M("Payapiconfig");
        $disabledpayapiid = $Payapiconfig->field('payapiid')->where("disabled=0")->select(false);
        $Payapi           = M("Payapi");
        $tongdaolist      = $Payapi->where("id not in (" . $disabledpayapiid . ")")->select();
        $datetype         = array("b", "w", "j");
        $Tikuanmoney      = M("Tikuanmoney");
        $array            = array();
        foreach ($tongdaolist as $tongdao) {
            // file_put_contents("loguser.txt",$tongdao["id"]."----", FILE_APPEND);
            for ($i = 0; $i < 2; $i++) {
                // file_put_contents("loguser.txt",$i."----", FILE_APPEND);
                foreach ($datetype as $val) {
                    // file_put_contents("loguser.txt",$val."||".$userid."||".$websiteid."|||||||", FILE_APPEND);
                    $count = $Tikuanmoney->where(["t" => $i, "userid" => $userid, "payapiid" => $tongdao["id"], "websiteid" => $websiteid, "datetype" => $val])->count();
                    // file_put_contents("loguser.txt",$count."*********", FILE_APPEND);
                    if ($count <= 0) {
                        $Tikuanmoney->t         = $i;
                        $Tikuanmoney->datetype  = $val;
                        $Tikuanmoney->userid    = $userid;
                        $Tikuanmoney->websiteid = $websiteid;
                        $Tikuanmoney->payapiid  = $tongdao["id"];
                        $Tikuanmoney->add();
                        $value = "0.00";
                    } else {
                        $value = $Tikuanmoney->where(["t" => $i, "userid" => $userid, "payapiid" => $tongdao["id"], "websiteid" => $websiteid, "datetype" => $val])->getField("money");
                    }
                    $array["form" . $tongdao["id"]]["t" . $i . $val] = $value;
                }
            }
            $array["form" . $tongdao["id"]]["tikuanpayapiid"] = $tongdao["id"];
            $array["form" . $tongdao["id"]]["userid"]         = $useriduserid;
        }

        $Tikuanconfig = M("Tikuanconfig");
        $count        = $Tikuanconfig->where(["websiteid" => $websiteid, "userid" => $userid])->count();
        if ($count <= 0) {
            $data["websiteid"] = $websiteid;
            $data["userid"]    = $userid;
            $Tikuanconfig->add($data);
        }
        $tikuanconfiglist         = $Tikuanconfig->where(["websiteid" => $websiteid ,"userid" => $userid])->find();
        $arraystr                 = array();
        $arraystr["tikuanconfig"] = $tikuanconfiglist;
        $arraystr["tksz"]         = $array;
        $this->ajaxReturn($arraystr, "json");
    }

    public function Edittikuanmoney()
    {
        $userid = I("post.userid");

        $User      = M("User");
        $usertype  = $User->where("id=" . $userid)->getField("usertype");
        $websiteid = $User->where("id=" . $userid)->getField("websiteid");
        /*
         * if($usertype == 2){ //如果用户类型为2 分站管理员
         * $Website = M("Website");
         * $websiteid = $Website->where("userid=".$userid)->getField("id");
         * $useriduserid = $userid;
         * $userid = 0;
         *
         * }else{
         * $websiteid = 0;
         * }
         */

        $payapiid = I("post.tikuanpayapiid");

        $datetype = array(
            "b",
            "w",
            "j",
        );

        $Tikuanmoney = M("Tikuanmoney");

        for ($i = 0; $i < 2; $i++) {
            foreach ($datetype as $val) {
                $Tikuanmoney->money = I("post.t" . $i . $val, 0);
                $Tikuanmoney->where(["t" => $i, "userid" => $userid, "payapiid" => $payapiid , "websiteid" => $websiteid, "datetype" => $val])->save();
            }
        }
        exit("修改成功！");
    }

    /**
     * 用户资金操作
     */
    public function usermoney()
    {
        $userid                          = I("get.userid", 0, 'intval');
        $info                            = M("Member")->where(["id" => $userid])->find();
        $deposit                         = ComplaintsDepositModel::getComplaintsDeposit($userid);
        $info['complaintsDeposit']       = number_format((double) $deposit['complaintsDeposit'], 2);
        $info['complaintsDepositPaused'] = number_format((double) $deposit['complaintsDepositPaused'], 2);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 增加、减少余额
     */
    public function incrMoney()
    {
        $uid         = session('admin_auth')['uid'];
        $verifysms   = 0; //是否可以短信验证
        $sms_is_open = smsStatus();
        if ($sms_is_open) {
            $adminMobileBind = adminMobileBind($uid);
            if ($adminMobileBind) {
                $verifysms = 1;
            }
        }
        //是否可以谷歌安全码验证
        $verifyGoogle = adminGoogleBind($uid);
        if (IS_POST) {
            //开启事物
            M()->startTrans();
            $userid     = I("post.uid", 0, 'intval');
            $cztype     = I("post.cztype");
            $bgmoney    = I("post.bgmoney",0,'float');
            $contentstr = I("post.memo", "");
            $auth_type  = I('post.auth_type', 0, 'intval');
            if ($bgmoney <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "变动金额必须是正数！"]);
            }
            if($verifyGoogle && $verifysms) {
                if(!in_array($auth_type,[0,1])) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            } elseif($verifyGoogle && !$verifysms) {
                if($auth_type != 1) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            } elseif(!$verifyGoogle && $verifysms) {
                if($auth_type != 0) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            }
            if ($verifyGoogle && $auth_type == 1) {
                $res = check_auth_error($uid, 5);
                if(!$res['status']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => $res['msg']]);
                }
                //谷歌安全码验证
                $google_code = I('request.google_code');
                if (!$google_code) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "谷歌安全码不能为空！"]);
                } else {
                    $ga                = new \Org\Util\GoogleAuthenticator();
                    $google_secret_key = M('Admin')->where(['id' => $uid])->getField('google_secret_key');
                    if (!$google_secret_key) {
                        $this->ajaxReturn(['status' => 0, 'msg' => "您未绑定谷歌身份验证器！"]);
                    }
                    $oneCode = $ga->getCode($google_secret_key);
                    if ($google_code !== $oneCode) {
                        $this->ajaxReturn(['status' => 0, 'msg' => "谷歌安全码错误！"]);
                    }
                }
            } elseif ($verifysms && $auth_type == 0) {
                //短信验证码
                $code = I('post.code');
                if (!$code) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "短信验证码不能为空！"]);
                } else {
                    if (session('send.adjustUserMoneySend') != $code || !$this->checkSessionTime('adjustUserMoneySend', $code)) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '验证码错误']);
                    } else {
                        session('send', null);
                    }
                }
            }
            $date = I("post.date");
            if (!$date) {
                $date = date('Y-m-d');
            }
            if (strtotime($date) > time()) {
                $this->ajaxReturn(['status' => 0, 'msg' => '冲正日期不正确']);
            }
            $info = M("Member")->where(["id" => $userid])->lock(true)->find();
            if (empty($info)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
            }
            if (($info['balance'] - $bgmoney) < 0 && $cztype == 4) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上余额不足" . $bgmoney . "元，不能完成减金操作"]);
            }
            if ($cztype == 3) {
                $data["balance"] = array('exp', "balance+" . $bgmoney);
                $gmoney          = $info['balance'] + $bgmoney;
            } elseif ($cztype == 4) {
                $data["balance"]  = array('exp', "balance-" . $bgmoney);
                $where['balance'] = array('egt', $bgmoney);
                $gmoney           = $info['balance'] - $bgmoney;
            }
            $where['id'] = $userid;
            $res1        = M('Member')->where($where)->save($data);
            $arrayField  = array(
                "userid"     => $userid,
                'ymoney'     => $info['balance'],
                "money"      => $bgmoney,
                "gmoney"     => $gmoney,
                "datetime"   => date("Y-m-d H:i:s"),
                "tongdao"    => '',
                "transid"    => "",
                "orderid"    => "",
                "lx"         => $cztype, // 增减类型
                "contentstr" => $contentstr . '【冲正周期:' . $date . '】',
            );
            $res2 = moneychangeadd($arrayField);
            //冲正订单
            $arrayRedo = array(
                'user_id'  => $userid,
                'admin_id' => session('admin_auth')['uid'],
                'money'    => $bgmoney,
                'type'     => $cztype == 3 ? 1 : 2,
                'remark'   => $arrayField['contentstr'],
                'date'     => $date,
                'ctime'    => time(),
            );
            $res3 = M('redo_order')->add($arrayRedo);
            if ($res1 && $res2 && $res3) {
                M()->commit();
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
            } else {
                M()->rollback();
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
            }
        } else {
            $userid = I("request.uid");
            $date   = I("request.date");
            $info   = M("Member")->where(["id" => $userid])->find();
            $uid    = session('admin_auth')['uid'];
            $user   = M('Admin')->where(['id' => $uid])->find();
            $this->assign('mobile', $user['mobile']);
            $this->assign('info', $info);
            $this->assign('date', $date);
            $this->assign('verifysms', $verifysms);
            $this->assign('verifyGoogle', $verifyGoogle);
            $this->assign('auth_type', $verifyGoogle ? 1 : 0);
            $this->display();
        }
    }


    /**
     * 增加、减少余额
     */
    public function incrYckMoney()
    {
        $uid         = session('admin_auth')['uid'];
        $verifysms   = 0; //是否可以短信验证
        $sms_is_open = smsStatus();
        if ($sms_is_open) {
            $adminMobileBind = adminMobileBind($uid);
            if ($adminMobileBind) {
                $verifysms = 1;
            }
        }
        //是否可以谷歌安全码验证
        $verifyGoogle = adminGoogleBind($uid);
        if (IS_POST) {
            //开启事物
            M()->startTrans();
            $userid     = I("post.uid", 0, 'intval');
            $cztype     = I("post.cztype");
            $bgmoney    = I("post.bgmoney",0,'float');
            $contentstr = I("post.memo", "");
            $auth_type  = I('post.auth_type', 0, 'intval');
            if ($bgmoney <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "变动金额必须是正数！"]);
            }
            if($verifyGoogle && $verifysms) {
                if(!in_array($auth_type,[0,1])) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            } elseif($verifyGoogle && !$verifysms) {
                if($auth_type != 1) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            } elseif(!$verifyGoogle && $verifysms) {
                if($auth_type != 0) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            }
            if ($verifyGoogle && $auth_type == 1) {
                //谷歌安全码验证
                $google_code = I('request.google_code');
                if (!$google_code) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "谷歌安全码不能为空！"]);
                } else {
                    $ga                = new \Org\Util\GoogleAuthenticator();
                    $google_secret_key = M('Admin')->where(['id' => $uid])->getField('google_secret_key');
                    if (!$google_secret_key) {
                        $this->ajaxReturn(['status' => 0, 'msg' => "您未绑定谷歌身份验证器！"]);
                    }
                    $oneCode = $ga->getCode($google_secret_key);
                    if ($google_code !== $oneCode) {
                        $this->ajaxReturn(['status' => 0, 'msg' => "谷歌安全码错误！"]);
                    }
                }
            } elseif ($verifysms && $auth_type == 0) {
                //短信验证码
                $code = I('post.code');
                if (!$code) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "短信验证码不能为空！"]);
                } else {
                    if (session('send.adjustUserMoneySend') != $code || !$this->checkSessionTime('adjustUserMoneySend', $code)) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '验证码错误']);
                    } else {
                        session('send', null);
                    }
                }
            }
            $date = I("post.date");
            if (!$date) {
                $date = date('Y-m-d');
            }
            if (strtotime($date) > time()) {
                $this->ajaxReturn(['status' => 0, 'msg' => '冲正日期不正确']);
            }
            $info = M("Member")->where(["id" => $userid])->lock(true)->find();
            if (empty($info)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
            }
            if (($info['yckbalance'] - $bgmoney) < 0 && $cztype == 4) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上余额不足" . $bgmoney . "元，不能完成减金操作"]);
            }
            if ($cztype == 3) {
                $data["yckbalance"] = array('exp', "yckbalance+" . $bgmoney);
                $gmoney          = $info['yckbalance'] + $bgmoney;
            } elseif ($cztype == 4) {
                $data["yckbalance"]  = array('exp', "yckbalance-" . $bgmoney);
                $where['yckbalance'] = array('egt', $bgmoney);
                $gmoney           = $info['yckbalance'] - $bgmoney;
            }
            $where['id'] = $userid;
            $res1        = M('Member')->where($where)->save($data);
            $arrayField  = array(
                "userid"     => $userid,
                'ymoney'     => $info['yckbalance'],
                "money"      => $bgmoney,
                "gmoney"     => $gmoney,
                "datetime"   => date("Y-m-d H:i:s"),
                "tongdao"    => '',
                "transid"    => "",
                "orderid"    => "",
                "lx"         => $cztype, // 增减类型
                "contentstr" => $contentstr . '【冲正周期:' . $date . '】',
            );
            $res2 = moneychangeadd($arrayField);
            //冲正订单
            $arrayRedo = array(
                'user_id'  => $userid,
                'admin_id' => session('admin_auth')['uid'],
                'money'    => $bgmoney,
                'type'     => $cztype == 3 ? 1 : 2,
                'remark'   => $arrayField['contentstr'],
                'date'     => $date,
                'ctime'    => time(),
            );
            $res3 = M('redo_order')->add($arrayRedo);
            if ($res1 && $res2 && $res3) {
                M()->commit();
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
            } else {
                M()->rollback();
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
            }
        } else {
            $userid = I("request.uid");
            $date   = I("request.date");
            $info   = M("Member")->where(["id" => $userid])->find();
            $uid    = session('admin_auth')['uid'];
            $user   = M('Admin')->where(['id' => $uid])->find();
            $this->assign('mobile', $user['mobile']);
            $this->assign('info', $info);
            $this->assign('date', $date);
            $this->assign('verifysms', $verifysms);
            $this->assign('verifyGoogle', $verifyGoogle);
            $this->assign('auth_type', $verifyGoogle ? 1 : 0);
            $this->display();
        }
    }

     /**
     * 增加、减少码商余额
     */
    public function incrCodeMoney()
    {
        $uid         = session('admin_auth')['uid'];
        $verifysms   = 0; //是否可以短信验证
        $sms_is_open = smsStatus();
        if ($sms_is_open) {
            $adminMobileBind = adminMobileBind($uid);
            if ($adminMobileBind) {
                $verifysms = 1;
            }
        }
        //是否可以谷歌安全码验证
        $verifyGoogle = adminGoogleBind($uid);
        if (IS_POST) {
            //开启事物
            M()->startTrans();
            $userid     = I("post.uid", 0, 'intval');
            $cztype     = I("post.cztype");
            $bgmoney    = I("post.bgmoney",0,'float');
            $contentstr = I("post.memo", "");
            $auth_type  = I('post.auth_type', 0, 'intval');
            if ($bgmoney <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "变动金额必须是正数！"]);
            }
            if($verifyGoogle && $verifysms) {
                if(!in_array($auth_type,[0,1])) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            } elseif($verifyGoogle && !$verifysms) {
                if($auth_type != 1) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            } elseif(!$verifyGoogle && $verifysms) {
                if($auth_type != 0) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "参数错误！"]);
                }
            }
            if ($verifyGoogle && $auth_type == 1) {
                //谷歌安全码验证
                $google_code = I('request.google_code');
                if (!$google_code) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "谷歌安全码不能为空！"]);
                } else {
                    $ga                = new \Org\Util\GoogleAuthenticator();
                    $google_secret_key = M('Admin')->where(['id' => $uid])->getField('google_secret_key');
                    if (!$google_secret_key) {
                        $this->ajaxReturn(['status' => 0, 'msg' => "您未绑定谷歌身份验证器！"]);
                    }
                    $oneCode = $ga->getCode($google_secret_key);
                    if ($google_code !== $oneCode) {
                        $this->ajaxReturn(['status' => 0, 'msg' => "谷歌安全码错误！"]);
                    }
                }
            } elseif ($verifysms && $auth_type == 0) {
                //短信验证码
                $code = I('post.code');
                if (!$code) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "短信验证码不能为空！"]);
                } else {
                    if (session('send.adjustUserMoneySend') != $code || !$this->checkSessionTime('adjustUserMoneySend', $code)) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '验证码错误']);
                    } else {
                        session('send', null);
                    }
                }
            }
            $date = I("post.date");
            if (!$date) {
                $date = date('Y-m-d');
            }
            if (strtotime($date) > time()) {
                $this->ajaxReturn(['status' => 0, 'msg' => '冲正日期不正确']);
            }
            $info = M("Member")->where(["id" => $userid])->lock(true)->find();
            if (empty($info)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
            }
            if (($info['codeblockedbalance'] - $bgmoney) < 0 && $cztype == 4) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上余额不足" . $bgmoney . "元，不能完成减金操作"]);
            }
            if ($cztype == 3) {
                $data["codeblockedbalance"] = array('exp', "codeblockedbalance+" . $bgmoney);
                $gmoney          = $info['codeblockedbalance'] + $bgmoney;
            } elseif ($cztype == 4) {
                $data["codeblockedbalance"]  = array('exp', "codeblockedbalance-" . $bgmoney);
                $where['codeblockedbalance'] = array('egt', $bgmoney);
                $gmoney           = $info['codeblockedbalance'] - $bgmoney;
            }
            $where['id'] = $userid;
            $res1        = M('Member')->where($where)->save($data);
            $arrayField  = array(
                "userid"     => $userid,
                'ymoney'     => $info['codeblockedbalance'],
                "money"      => $bgmoney,
                "gmoney"     => $gmoney,
                "datetime"   => date("Y-m-d H:i:s"),
                "tongdao"    => '',
                "transid"    => "",
                "orderid"    => "",
                "lx"         => $cztype, // 增减类型
                "contentstr" => $contentstr . '【冲正周期:' . $date . '】',
            );
            $res2 = moneychangeadd($arrayField);
            //冲正订单
            $arrayRedo = array(
                'user_id'  => $userid,
                'admin_id' => session('admin_auth')['uid'],
                'money'    => $bgmoney,
                'type'     => $cztype == 3 ? 1 : 2,
                'remark'   => $arrayField['contentstr'],
                'date'     => $date,
                'ctime'    => time(),
            );
            $res3 = M('redo_order')->add($arrayRedo);
            if ($res1 && $res2 && $res3) {
                M()->commit();
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
            } else {
                M()->rollback();
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
            }
        } else {
            $userid = I("request.uid");
            $date   = I("request.date");
            $info   = M("Member")->where(["id" => $userid])->find();
            $uid    = session('admin_auth')['uid'];
            $user   = M('Admin')->where(['id' => $uid])->find();
            $this->assign('mobile', $user['mobile']);
            $this->assign('info', $info);
            $this->assign('date', $date);
            $this->assign('verifysms', $verifysms);
            $this->assign('verifyGoogle', $verifyGoogle);
            $this->assign('auth_type', $verifyGoogle ? 1 : 0);
            $this->display();
        }
    }

    /**
     * 冻结、解冻余额
     */
    public function frozenMoney()
    {
        if (IS_POST) {
            //开启事物
            M()->startTrans();
            $userid        = I("post.uid", 0, 'intval');
            $cztype        = I("post.cztype", 0, 'intval');
            $bgmoney       = I("post.bgmoney", 0, 'float');
            $contentstr    = I("post.memo", "");
            $unfreeze_time = I("post.unfreeze_time", "");
            $info          = M("Member")->where(["id" => $userid])->lock(true)->find();
            if (empty($info)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
            }
            if ($bgmoney <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "金额需要大于0"]);
            }
            if (($info['blockedbalance'] - $bgmoney) < 0 && $cztype == 8) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上冻结余额不足" . $bgmoney . "元，不能完成减金操作"]);
            }
            //冻结
            if ($cztype == 7 && ($info['balance'] - $bgmoney) < 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上余额不足" . $bgmoney . "元，不能完成冻结操作"]);
            }
            if ($unfreeze_time != '') {
                $unfreeze_time = strtotime($unfreeze_time);
                if ($unfreeze_time <= time()) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "解冻时间无效"]);
                }
            }
            if ($cztype == 7) {
                $data["balance"]        = array('exp', "balance-" . $bgmoney);
                $data["blockedbalance"] = array('exp', "blockedbalance+" . $bgmoney);
                $where['balance']       = ['egt', $bgmoney];
                $gmoney                 = $info['balance'] + $bgmoney;
            } elseif ($cztype == 8) {
                $data["balance"]         = array('exp', "balance+" . $bgmoney);
                $data["blockedbalance"]  = array('exp', "blockedbalance-" . $bgmoney);
                $where['blockedbalance'] = ['egt', $bgmoney];
                $gmoney                  = $info['balance'] - $bgmoney;
            }
            $where['id'] = $userid;
            $res1        = M('Member')->where($where)->save($data);
            if ($cztype == 7) {
                //加入解冻订单
                $autoUnfreezeArray = array(
                    'user_id'            => $userid,
                    'freeze_money'       => $bgmoney,
                    'unfreeze_time'      => $unfreeze_time,
                    'real_unfreeze_time' => 0,
                    'is_pause'           => 0,
                    'status'             => 0,
                    'create_at'          => time(),
                    'update_at'          => time(),
                );
                $res2 = M('auto_unfrozen_order')->add($autoUnfreezeArray);
            } else {
                $res2 = true;
            }
            $arrayField = array(
                "userid"     => $userid,
                "ymoney"     => $info['balance'],
                "money"      => $bgmoney,
                "gmoney"     => $gmoney,
                "datetime"   => date("Y-m-d H:i:s"),
                "tongdao"    => '',
                "transid"    => "",
                "lx"         => $cztype, // 增减类型
                "contentstr" => $contentstr,
            );
            if ($cztype == 7 && $res2 > 0) {
                $arrayField['transid'] = $res2;
            } else {
                $arrayField['transid'] = '';
            }
            $res3 = moneychangeadd($arrayField);
            if ($res1 && $res2 && $res3) {
                M()->commit();
                $this->ajaxReturn(['status' => 1, 'msg' => "操作成功！"]);
            } else {
                M()->rollback();
                $this->ajaxReturn(['status' => 0, 'msg' => "操作失败！"]);
            }
        } else {
            $userid = I("request.uid");
            $info   = M("Member")->where(["id" => $userid])->find();
            $this->assign('info', $info);
            $this->display();
        }
    }
    /**
     * 手动去管理定时解冻任务
     * author: feng
     * create: 2017/10/21 15:43
     */
    public function frozenTiming()
    {
        //通道

        $where    = array();
        $memberid = I("get.uid", 0, 'intval');
        if ($memberid) {
            $where['userid'] = array('eq', $memberid);
        } else {
            return;
        }
        $this->assign('uid', $memberid);
        $orderid = I("get.orderid");
        if ($orderid) {
            $where['orderid'] = array('eq', $orderid);
        }
        $this->assign('orderid', $orderid);
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime) = explode('|', $createtime);
            $where['createtime']   = ['between', [strtotime($cstime), strtotime($cetime ? $cetime : date('Y-m-d'))]];
        }
        $this->assign('createtime', $createtime);
        $count = M('blockedlog')->where($where)->count();
        $page  = new Page($count, 15);
        $list  = M('blockedlog')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('status asc,id desc')
            ->select();
        $this->assign("list", $list);
        $this->assign("page", $page->show());
        C('TOKEN_ON', false);

        $this->display();
    }

    /**
     * 管理手动冻结资金
     * author: mapeijian
     * create: 2018/06/09 12:22
     */
    public function frozenOrder()
    {
        //通道

        $where    = array();
        $memberid = I("get.uid");
        if ($memberid) {
            $where['user_id'] = array('eq', $memberid);
        } else {
            return;
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime) = explode('|', $createtime);
            $where['create_at']    = ['between', [strtotime($cstime), strtotime($cetime ? $cetime : date('Y-m-d'))]];
        }
        $count = M('autoUnfrozenOrder')->where($where)->count();
        $page  = new Page($count, 15);
        $list  = M('autoUnfrozenOrder')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('status asc,id desc')
            ->select();
        $this->assign("list", $list);
        $this->assign("page", $page->show());
        C('TOKEN_ON', false);

        $this->display();
    }

    /**
     * 解冻
     * author: feng
     * create: 2017/10/21 17:15
     */
    public function frozenHandle()
    {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            if (!$id) {
                $this->ajaxReturn(['status' => 0]);
            }

            $maps['status'] = array('eq', 0);
            $maps["id"]     = $id;
            $blockData      = M('blockedlog')->where($maps)->order('id asc')->find();
            if (!$blockData) {
                $this->ajaxReturn(['status' => 0, 'msg' => '不存在或已解冻']);
            }
            $blockedbalance = M('Member')->where(['id' => $blockData['userid']])->getField("blockedbalance");

            if ($blockedbalance < $blockData["amount"]) {
                $this->ajaxReturn(['status' => 0, 'msg' => '冻结金额不足']);
            }
            $rows                   = array();
            $rows['balance']        = array('exp', "balance+{$blockData['amount']}");
            $rows['blockedbalance'] = array('exp', "blockedbalance-{$blockData['amount']}");
            //开启事务
            $Model = M();
            $Model->startTrans();
            //更新资金
            $upRes = $Model->table('pay_member')->where(['id' => $blockData['userid']])->save($rows);
            //更新状态
            $uplog = $Model->table('pay_blockedlog')->where(array('id' => $blockData['id']))->save(array('status' => 1));
            //增加记录
            $data               = array();
            $data['userid']     = $blockData['userid'];
            $data['money']      = $blockData['amount'];
            $data['datetime']   = date("Y-m-d H:i:s");
            $data['tongdao']    = $blockData['pid'];
            $data['transid']    = $blockData['orderid']; //交易流水号
            $data['orderid']    = $blockData['orderid'];
            $data['lx']         = 8; //解冻
            $data['contentstr'] = "订单金额解冻";
            $change             = $Model->table('pay_moneychange')->add($data);

            //提交事务
            if ($upRes && $uplog && $change) {
                $Model->commit();
                $this->ajaxReturn(['status' => 1]);
            } else {
                $Model->rollback();
            }
            $this->ajaxReturn(['status' => 0]);

        }
    }

    /**
     * 手动冻结金额解冻
     * author: mapeijian
     * create: 2018/06/09 13:45
     */
    public function unfreeze()
    {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            if (!$id) {
                $this->ajaxReturn(['status' => 0]);
            }

            $maps['status'] = array('eq', 0);
            $maps["id"]     = $id;
            $blockData      = M('autoUnfrozenOrder')->where($maps)->find();
            if (!$blockData) {
                $this->ajaxReturn(['status' => 0, 'msg' => '不存在或已解冻']);
            }
            $blockedbalance = M('Member')->where(['id' => $blockData['user_id']])->getField("blockedbalance");

            if ($blockedbalance < $blockData["freeze_money"]) {
                $this->ajaxReturn(['status' => 0, 'msg' => '冻结金额不足']);
            }
            $rows                   = array();
            $rows['balance']        = array('exp', "balance+{$blockData['freeze_money']}");
            $rows['blockedbalance'] = array('exp', "blockedbalance-{$blockData['freeze_money']}");
            //开启事务
            $Model = M();
            $Model->startTrans();
            //更新资金
            $upRes = $Model->table('pay_member')->where(['id' => $blockData['user_id']])->save($rows);
            //更新状态
            $uplog = $Model->table('pay_auto_unfrozen_order')->where(array('id' => $blockData['id'], 'status' => 0))->save(array('status' => 1, 'real_unfreeze_time' => time()));
            //增加记录
            $data               = array();
            $data['userid']     = $blockData['user_id'];
            $data['money']      = $blockData['freeze_money'];
            $data['datetime']   = date("Y-m-d H:i:s");
            $data['tongdao']    = $blockData['pid'];
            $data['transid']    = $blockData['orderid']; //交易流水号
            $data['orderid']    = $blockData['orderid'];
            $data['lx']         = 8; //解冻
            $data['contentstr'] = "手动冻结金额解冻";
            $change             = $Model->table('pay_moneychange')->add($data);

            //提交事务
            if ($upRes && $uplog && $change) {
                $Model->commit();
                $this->ajaxReturn(['status' => 1, 'msg' => '解冻成功']);
            } else {
                $Model->rollback();
            }
            $this->ajaxReturn(['status' => 0, 'msg' => '解冻失败']);
        }
    }

    /**
     * 手动冻结金额自动解冻任务开关
     * author: mapeijian
     * create: 2018/06/09 13:45
     */
    public function autoUnfreezeSwitch()
    {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            if (!$id) {
                $this->ajaxReturn(['status' => 0]);
            }
            $status     = I('post.status', 0, 'intval');
            $maps["id"] = $id;
            $blockData  = M('autoUnfrozenOrder')->where($maps)->find();
            if (!$blockData) {
                $this->ajaxReturn(['status' => 0, 'msg' => '不存在该冻结金额订单！']);
            }
            if ($blockData['status']) {
                $this->ajaxReturn(['status' => 0, 'msg' => '已解冻，不能进行此操作！']);
            }
            if (!$blockData['unfreeze_time']) {
                $this->ajaxReturn(['status' => 0, 'msg' => '改冻结订单未开启自动解冻！']);
            }
            if ($blockData['is_pause'] == $status) {
                if ($status == 0) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '改解冻任务正常运行中，无需重复操作！']);
                } else {
                    $this->ajaxReturn(['status' => 0, 'msg' => '改解冻任务已暂停，无需重复操作！']);
                }
            }
            $maps['status'] = 0;
            $res            = M('autoUnfrozenOrder')->where($maps)->setField('is_pause', $status);
            if ($res) {
                $this->ajaxReturn(['status' => 0, 'msg' => $status == 1 ? '暂停成功' : '开启成功']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败！']);
            }
        }
    }

    /**
     * 批量处理
     * author: feng
     * create: 2017/10/21 18:22
     */
    public function frozenHandles()
    {
        if (IS_POST) {
            $ids = I('post.ids');
            if (!$ids) {
                $this->ajaxReturn(['status' => 0]);
            }

            $idsArr   = explode(",", $ids);
            $sucCount = 0;
            $msg      = "";
            foreach ($idsArr as $k => $id) {
                $maps['status'] = array('eq', 0);
                $maps["id"]     = $id;
                $blockData      = M('blockedlog')->where($maps)->order('id asc')->find();
                if (!$blockData) {
                    continue;
                }
                $blockedbalance = M('member')->where(['id' => $blockData['userid']])->field("blockedbalance");
                if ($blockedbalance < $blockData["amount"]) {
                    $msg = '冻结金额不足';
                    break;
                }
                $rows                   = array();
                $rows['balance']        = array('exp', "balance+{$blockData['amount']}");
                $rows['blockedbalance'] = array('exp', "blockedbalance-{$blockData['amount']}");
                //开启事务
                $Model = M();
                $Model->startTrans();
                //更新资金
                $upRes = $Model->table('pay_member')->where(['id' => $blockData['userid']])->save($rows);
                //更新状态
                $uplog = $Model->table('pay_blockedlog')->where(array('id' => $blockData['id']))->save(array('status' => 1));
                //增加记录
                $data               = array();
                $data['userid']     = $blockData['userid'];
                $data['money']      = $blockData['amount'];
                $data['datetime']   = date("Y-m-d H:i:s");
                $data['tongdao']    = $blockData['pid'];
                $data['transid']    = $blockData['orderid']; //交易流水号
                $data['orderid']    = $blockData['orderid'];
                $data['lx']         = 8; //解冻
                $data['contentstr'] = "订单金额解冻";
                $change             = $Model->table('pay_moneychange')->add($data);

                //提交事务
                if ($upRes && $uplog && $change) {
                    $Model->commit();
                    $sucCount++;
                } else {
                    $Model->rollback();
                }

            }
            $this->ajaxReturn(array("status" => $sucCount == count($idsArr) ? 1 : 0, "count" => $sucCount, "msg" => $msg));

        }
    }

    //切换身份
    public function changeuser()
    {
        $userid = I('get.userid', 0, 'intval');
        $info   = M('Member')->where(['id' => $userid])->find();
        if ($info) {
            $user_auth = [
                'uid'            => $info['id'],
                'username'       => $info['username'],
                'groupid'        => $info['groupid'],
                'password'       => $info['password'],
                'session_random' => $info['session_random'],
            ];
            if ($info['google_secret_key']) {
                $ga      = new \Org\Util\GoogleAuthenticator();
                $oneCode = $ga->getCode($info['google_secret_key']);
                if($info['groupid']==8){
                    session('code_google_auth', $oneCode);
                }else{
                    session('user_google_auth', $oneCode);
                }
            } else {
                if($info['groupid']==8){
                    session('code_google_auth', null);
                }else{
                    session('user_google_auth', null);
                }
            }
            if($info['groupid']==8){
                session('code_auth', $user_auth);
            }else{
                session('user_auth', $user_auth);
            }
            ksort($user_auth); //排序
            $code = http_build_query($user_auth); //url编码并生成query字符串
            $sign = sha1($code);
            if($info['groupid']==8){
                session('code_auth_sign', $sign);
            }else{
                session('user_auth_sign', $sign);
            }
            $module['4'] = C('user');
            $module['8'] = C('code');
            foreach ($this->groupId as $k => $v) {
                if ($k != 4 && $k !=8) {
                    $module[$k] = C('agent');
                }

            }
            header('Location:' . $this->_site . $module[$info['groupid']] . '.html');
        }
    }

    //用户状态切换
    public function editStatus()
    {
        if (IS_POST) {
            $userid   = intval(I('post.uid'));
            $isstatus = I('post.isopen') ? I('post.isopen') : 0;
            $res      = M('Member')->where(['id' => $userid])->save(['status' => $isstatus]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //用户状态切换
    public function AutoPaofen()
    {
        if (IS_POST) {
            $userid   = intval(I('post.uid'));
            $isstatus = I('post.isopen') ? I('post.isopen') : 0;
            $res      = M('Member')->where(['id' => $userid])->save(['auto_paofen' => $isstatus]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    /**
     * 用户认证
     */
    public function authorize()
    {
        $userid = I('get.uid', 0, 'intval');
        if ($userid) {
            $data = M('Member')->where(['id' => $userid])->find();
            //上传图片
            $images = M('Attachment')
                ->where(['userid' => $userid])
                ->limit(6)
                ->field('path')
                ->order('id desc')
                ->select();
            $data['images'] = $images;
            $this->assign('u', $data);
        }
        $this->display();
    }

    //编辑用户级别
    public function editUser()
    {
        $userid = I('get.uid', 0, 'intval');
        if ($userid) {
            $data = M('Member')
                ->where(['id' => $userid])->find();
            $this->assign('u', $data);

            //用户组
            //$groups = M('AuthGroup')->field('id,title')->select();
        }
        /**
         * 升级，用户组不再与用户组关联
         * author: feng
         * create: 2017/10/19 15:03
         */
        $agentCateSel  = [];
        $agentCateList = M('member_agent_cate')->select();
        foreach ($agentCateList as $k => $v) {
            $agentCateSel[$v['id']] = $v['cate_name'];
        }
        $this->assign('agentCateSel', $agentCateSel);
        $this->assign('merchants', C('MERCHANTS'));
        $this->display();
    }
    //保存编辑用户级别
    public function saveUser()
    {
        if (IS_POST) {
            $userid        = I('post.userid', 0, 'intval');

            $u             = I('post.u/a');
            $u['birthday'] = strtotime($u['birthday']);
            if($u['groupid']==8){
                $pre="C";
            }else{
                $pre="U";
            }
            if ($u['password']) {
               if (!$userid) {
                   $salt      = rand(1000, 9999);
                   $u['salt'] = $salt;
               } else {
                   $salt = M('Member')->where(['id' => $userid])->getField('salt');
                   $u['password'] = md5($u['password'] . $salt);
               }
           }

           // $u['password'] = md5($u['password'] . $salt);
            if ($userid) {
                
                if($u['parentid']){
                    $u['parentid']=intval($u['parentid']);
                    $pidinfo=M('Member')->getById($u['parentid']);
                    if(!$pidinfo){
                        $this->ajaxReturn(array("status" => 0, "msg" => '上级不存在'));
                    }
                    $u['path_id']=$pidinfo['path_id'].$pre.$userid.',';
                }else{
                    $u['parentid']=0;
                    $u['path_id']='0,'.$pre.$userid.',';
                }
                $res = M('Member')->where(['id' => $userid])->save($u);
            }else {
               if (!isset($u['password']) || !$u['password']) {
                   $this->ajaxReturn(array("status" => 0, "msg" => '请输入登录密码'));
               }
                $has_user = M('member')->where(['username' => $u['username'], 'email' => $u['email'], '_logic' => 'or'])->find();
                if ($has_user) {
                    if ($has_user['username'] == $u['username']) {
                        $this->ajaxReturn(array("status" => 0, "msg" => '用户名已存在'));
                    }
                    if ($has_user['email'] == $u['email']) {
                        $this->ajaxReturn(array("status" => 0, "msg" => '邮箱已存在'));
                    }
                }

                $siteconfig = M("Websiteconfig")->find();

                foreach ($this->groupId as $k => $v) {
                    if ($u['groupid'] == $k && $u['groupid'] != 4) {
                        $u['verifycode']['regtype'] = $k;
                    }

                }
                $save['parentid'] =$u['parentid'];
                $u                     = generateUser($u, $siteconfig);
                $u['activatedatetime'] = date("Y-m-d H:i:s");
                $u['agent_cate']       = $u['groupid'];
                // 创建用户
                $res = M('Member')->add($u);
                if($save['parentid']){
                    $save['id']=$res;
                    $save['parentid']=intval($save['parentid']);
                    $pidinfo=M('Member')->getById($save['parentid']);
                    if(!$pidinfo){
                        $this->ajaxReturn(array("status" => 0, "msg" => '上级不存在'));
                    }
                    $save['path_id']=$pidinfo['path_id'].$pre.$res.',';
                }else{
                    $save['id']=$res;
                    $save['parentid']=0;
                    $save['path_id']='0,'.$pre.$res.',';
                }
                $res = M('Member')->save($save);
                // 发邮件通知用户密码
                sendPasswordEmail($u['username'], $u['email'], $u['origin_password'], $siteconfig);
            }

            //编辑用户组
            /*if($res){
            M('AuthGroupAccess')->where(['uid'=>$userid])->save(['group_id'=>$u['groupid']]);
            }*/
            if ($res !== false) {
                $this->ajaxReturn(['status' => 1]);
            } else {
                $this->ajaxReturn(['status' => 0]);
            }
        }
    }

    //编辑用户费率
    public function userRateEdit()
    {
        $userid = I('get.uid', 0, 'intval');
        //系统产品列表
        $products = M('Product')
            ->where(['status' => 1, 'isdisplay' => 1])
            ->field('id,name')
            ->select();
        //用户产品列表
        $userprods = M('Userrate')->where(['userid' => $userid])->select();
        if ($userprods) {
            foreach ($userprods as $item) {
                $_tmpData[$item['payapiid']] = $item;
            }
        }
        //重组产品列表
        $list = [];
        if ($products) {
            foreach ($products as $key => $item) {
                $products[$key]['t0feilv']    = $_tmpData[$item['id']]['t0feilv'] ? $_tmpData[$item['id']]['t0feilv'] : '0.0000';
                $products[$key]['t0fengding'] = $_tmpData[$item['id']]['t0fengding'] ? $_tmpData[$item['id']]['t0fengding'] : '0.0000';
                $products[$key]['feilv']      = $_tmpData[$item['id']]['feilv'] ? $_tmpData[$item['id']]['feilv'] : '0.0000';
                $products[$key]['fengding']   = $_tmpData[$item['id']]['fengding'] ? $_tmpData[$item['id']]['fengding'] : '0.0000';
            }
        }
        $this->assign('userid', $userid);
        $this->assign('products', $products);
        $this->display();
    }

    //保存费率
    public function saveUserRate()
    {
        if (IS_POST) {
            $userid = intval(I('post.userid'));
            $rows   = I('post.u/a');
            //print_r($rows);
            $datalist = [];
            foreach ($rows as $key => $item) {
                $rates = M('Userrate')->where(['userid' => $userid, 'payapiid' => $key])->find();
                if ($rates) {
                    $data_insert[] = ['id' => $rates['id'], 'userid' => $userid, 'payapiid' => $key, 'feilv' => $item['feilv'], 'fengding' => $item['fengding'], 't0feilv' => $item['t0feilv'], 't0fengding' => $item['t0fengding']];
                } else {
                    $data_update[] = ['userid' => $userid, 'payapiid' => $key, 'feilv' => $item['feilv'], 'fengding' => $item['fengding'], 't0feilv' => $item['t0feilv'], 't0fengding' => $item['t0fengding']];
                }
            }
            M('Userrate')->addAll($data_insert, [], true);
            M('Userrate')->addAll($data_update, [], true);
            $this->ajaxReturn(['status' => 1]);
        }
    }

    //编辑用户通道
    public function editUserProduct()
    {

        $userid = I('get.uid', 0, 'intval');
        //系统产品列表
        $products = M('Product')
            ->where(['isdisplay' => 1])
            ->field('id,name,status,paytype')
            ->select();
        //用户产品列表
        $userprods = M('Product_user')->where(['userid' => $userid])->select();
        if ($userprods) {
            foreach ($userprods as $key => $item) {
                $_tmpData[$item['pid']] = $item;
            }
        }
        //重组产品列表
        $list = [];
        if ($products) {
            foreach ($products as $key => $item) {
                $products[$key]['status']  = $_tmpData[$item['id']]['status'];
                $products[$key]['channel'] = $_tmpData[$item['id']]['channel'];
                $products[$key]['polling'] = $_tmpData[$item['id']]['polling'];
                //权重
                $weights    = [];
                $weights    = explode('|', $_tmpData[$item['id']]['weight']);
                $_tmpWeight = [];
                if (is_array($weights)) {
                    foreach ($weights as $value) {
                        list($pid, $weight) = explode(':', $value);
                        if ($pid) {
                            $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
                        }
                    }
                } else {
                    list($pid, $weight) = explode(':', $_tmpData[$item['id']]['weight']);
                    if ($pid) {
                        $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
                    }
                }
                $products[$key]['weight'] = $_tmpWeight;
            }
        }
        $this->assign('products', $products);
        $this->display();
    }
    //保存编辑用户通道
    public function saveUserProduct()
    {
        if (IS_POST) {
            $userid = I('post.userid', 0, 'intval');
            $u      = I('post.u/a');
            foreach ($u as $key => $item) {
                $weightStr = '';
                $status    = $item['status'] ? $item['status'] : 0;
                if (is_array($item['w'])) {
                    foreach ($item['w'] as $weigths) {
                        if ($weigths['pid']) {
                            $weightStr .= $weigths['pid'] . ':' . $weigths['weight'] . "|";
                        }
                    }
                }
                $product = M('Product_user')->where(['userid' => $userid, 'pid' => $key])->find();
                if ($product) {
                    $data_insert[] = ['id' => $product['id'], 'userid' => $userid, 'pid' => $key, 'status' => $status, 'polling' => $item['polling'], 'channel' => $item['channel'], 'weight' => trim($weightStr, '|')];
                } else {
                    $data_update[] = ['userid' => $userid, 'pid' => $key, 'status' => $status, 'polling' => $item['polling'], 'channel' => $item['channel'], 'weight' => trim($weightStr, '|')];
                }
            }
            M('Product_user')->addAll($data_insert, [], true);
            M('Product_user')->addAll($data_update, [], true);
            $this->ajaxReturn(['status' => 1]);
        }
    }

    //保证金
    public function userDepositRule()
    {
        $userid = I('get.uid', 0, 'intval');
        $data   = M('ComplaintsDepositRule')->where(['user_id' => $userid])->find();
        if (isset($data['freeze_time'])) {
            $data['freeze_time'] = $data['freeze_time'] / 3600;
        }
        $this->assign('u', $data);
        $this->display();
    }

    //码商保证金
    public function userCodeDepositRule()
    {
        $userid = I('get.uid', 0, 'intval');
        $data   = M('ComplaintsCodedepositRule')->where(['user_id' => $userid])->find();
        if (isset($data['freeze_time'])) {
            $data['freeze_time'] = $data['freeze_time'] / 60;
        }
        $this->assign('u', $data);
        $this->display();
    }

    //保存保证金规则
    public function saveCodeDepositRule()
    {
        if (IS_POST) {
            $userId = I('post.userid', 0, 'intval');
            $id     = I('post.id', 0, 'intval');
            if (!$userId) {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }
            $row = [];
            if ((int) $_POST['u']['status']) {
                $row = I('post.u');
            } else {
                $row['status'] = 0;
            }
            if (isset($row['freeze_time'])) {
                $row['freeze_time'] = $row['freeze_time'] * 60; //单位转换为秒
            }
            if ($id) {
                $res = M('ComplaintsCodedepositRule')->where(['id' => $id, 'user_id' => $userId])->save($row);
            } else {
                $row['user_id'] = $userId;
                $res            = M('ComplaintsCodedepositRule')->add($row);
            }
            if (false !== $res) {
                $this->ajaxReturn(['status' => 1]);
            } else {
                $this->ajaxReturn(['status' => 0]);
            }
        }
    }

    //保存保证金规则
    public function saveDepositRule()
    {
        if (IS_POST) {
            $userId = I('post.userid', 0, 'intval');
            $id     = I('post.id', 0, 'intval');
            if (!$userId) {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }
            $row = [];
            if ((int) $_POST['u']['status']) {
                $row = I('post.u');
            } else {
                $row['status'] = 0;
            }
            if (isset($row['freeze_time'])) {
                $row['freeze_time'] = $row['freeze_time'] * 3600; //单位转换为秒
            }
            if ($id) {
                $res = M('ComplaintsDepositRule')->where(['id' => $id, 'user_id' => $userId])->save($row);
            } else {
                $row['user_id'] = $userId;
                $res            = M('ComplaintsDepositRule')->add($row);
            }
            if (false !== $res) {
                $this->ajaxReturn(['status' => 1]);
            } else {
                $this->ajaxReturn(['status' => 0]);
            }
        }
    }

    //暂停解冻保证金
    public function pauseUnfreezingDeposit()
    {
        $userId = I('post.userid', 0, 'intval');
        if (!empty($userId)) {
            $res = M('ComplaintsDeposit')->where(['user_id' => $userId, 'status' => 0, 'is_pause' => 0])->save(['is_pause' => 1]);
            $msg = '';
            if (empty($res)) {
                $msg = '没有更新';
            }
            $this->ajaxReturn(['status' => $res, 'msg' => $msg]);
        }
    }

    //继续解冻保证金
    public function unpauseUnfreezingDeposit()
    {
        $userId = I('post.userid', 0, 'intval');
        if (!empty($userId)) {
            $res = M('ComplaintsDeposit')->where(['user_id' => $userId, 'status' => 0, 'is_pause' => 1])->save(['is_pause' => 0]);
            $msg = '';
            if (empty($res)) {
                $msg = '没有更新';
            }
            $this->ajaxReturn(['status' => $res, 'msg' => $msg]);
        }
    }

    //提现
    public function userWithdrawal()
    {
        $userid = I('get.uid', 0, 'intval');
        $data   = M('Tikuanconfig')->where(['userid' => $userid])->find();
        $this->assign('u', $data);
        $this->display();
    }
    //保存提现规则
    public function saveWithdrawal()
    {
        if (IS_POST) {
            $userid = I('post.userid', 0, 'intval');
            $id     = I('post.id', 0, 'intval');
            if ((int) $_POST['u']['systemxz']) {
                $rows = I('post.u');
            } else {
                $rows['systemxz'] = 0;
            }
            if ($id) {
                $res = M('Tikuanconfig')->where(['id' => $id, 'userid' => $userid])->save($rows);
            } else {
                $rows['userid'] = $userid;
                $res            = M('Tikuanconfig')->add($rows);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }
    //解冻费率
    public function thawingFunds()
    {
        $configs    = C('PLANNING');
        $allowstart = $configs['allowstart'] ? $configs['allowstart'] : 1;
        $allowend   = $configs['allowend'] ? $configs['allowend'] : 5;
        //计划执行

        $curtime            = strtotime('today');
        $yesterday          = strtotime('yesterday');
        $maps['thawtime']   = array('elt', $curtime + 7200);
        $maps['createtime'] = array('lt', $curtime);
        $maps['status']     = array('eq', 0);
        $data               = M('blockedlog')->where($maps)->limit(600)->order('id asc')->select();

        $i = 0;
        if ($data) {
            foreach ($data as $item) {
                $rows                   = array();
                $rows['balance']        = array('exp', "balance+{$item['amount']}");
                $rows['blockedbalance'] = array('exp', "blockedbalance-{$item['amount']}");

                //开启事务
                $Model = M();
                $Model->startTrans();

                //更新资金
                $upRes = $Model->table('pay_member')->where(['id' => $item['userid']])->save($rows);

                //更新状态
                $uplog = $Model->table('pay_blockedlog')->where(array('id' => $item['id']))->save(array('status' => 1));

                //增加记录
                $data               = array();
                $data['userid']     = $item['userid'];
                $data['money']      = $item['amount'];
                $data['datetime']   = date("Y-m-d H:i:s");
                $data['tongdao']    = $item['pid'];
                $data['transid']    = $item['orderid']; //交易流水号
                $data['orderid']    = $item['orderid'];
                $data['lx']         = 8; //解冻
                $data['contentstr'] = "订单金额解冻";
                $change             = $Model->table('pay_moneychange')->add($data);

                //提交事务
                if ($upRes && $uplog && $change) {
                    $i++;
                    $Model->commit();
                } else {
                    $Model->rollback();
                }
            }
        }
        $this->ajaxReturn(['status' => 'ok', 'msg' => '解冻了' . $i . '条数据']);

    }

    public function saveAddDomain()
    {
        $Member = M('Member');
        if (IS_POST) {
            $domain = I('post.domain', 'trim');
            $id     = I('post.id', '');
            $result = $Member->where(['id' => $id])->save(['domain' => $domain]);
            $this->ajaxReturn(['status' => $result]);
        } else {
            $uid = I('get.userid', '');

            $domain = $Member->where(['id' => $uid])->getField('domain');
            $this->assign('domain', $domain);
            $this->assign('id', $uid);
            $this->display();
        }
    }

    /**
     * 用户代理分类管理
     */
    public function agentCateList()
    {
        $m     = M("member_agent_cate");
        $count = $m->count();
        $page  = new Page($count, 15);
        $list  = $m
            ->order('id desc')
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $page->show());
        $this->display();
    }

    /**
     * 添加代理分类
     */
    public function addAgentCate()
    {
        $this->display();
    }

    /**
     * 编辑代理分类
     */
    public function editAgentCate()
    {
        $id = I("id", 0, "intval");
        if (!$id) {
            return;
        }

        $this->assign("cache", M("member_agent_cate")->where(array("id" => $id))->find());
        $this->display();
    }

    /**
     * 编辑代理分类
     */
    public function saveAgentCate()
    {
        if (IS_POST) {
            $id   = I('post.id', 0, 'intval');
            $rows = I('post.item/a');

            //保存
            if ($id) {

                $res = M('member_agent_cate')->where(['id' => $id])->save($rows);
            } else {
                $rows["ctime"] = time();
                $res           = M('member_agent_cate')->add($rows);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }

    /**
     * 删除代理分类
     */
    public function deleteAgentCate()
    {
        if (IS_POST) {
            $id  = I('post.id', 0, 'intval');
            $res = M('member_agent_cate')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    /**
     * 代理列表
     */
    public function agentList()
    {

        $username    = I('get.username', '');
        $status      = I('get.status', '');
        $authorized  = I('get.authorized', '');
        $parentid    = I('get.parentid', '');
        $regdatetime = I('get.regdatetime', '');
        $groupid     = I('get.groupid', '');

        $where['groupid'] = ['gt', '4'];
        if ($groupid != '') {
            $where['groupid'] = $groupid;
        }

        if (!empty($username) && !is_numeric($username)) {
            $where['username'] = ['like', "%" . $username . "%"];
        } elseif (intval($username) - 10000 > 0) {
            $where['id'] = intval($username) - 10000;
        }
        if ($status != '') {
            $where['status'] = $status;
        }
        if ($authorized != '') {
            $where['authorized'] = $authorized;
        }

        if (!empty($parentid) && !is_numeric($parentid)) {
            $User              = M("Member");
            $pid               = $User->where(['username' => $parentid])->getField("id");
            $where['parentid'] = $pid;
        } elseif ($parentid) {
            $where['parentid'] = $parentid;
        }
        if ($regdatetime) {
            list($starttime, $endtime) = explode('|', $regdatetime);
            $where['regdatetime']      = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        $count = M('Member')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size);
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Member')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        $agentCateSel  = [];
        $agentCateList = M('member_agent_cate')->select();
        foreach ($agentCateList as $k => $v) {
            if ($v['id'] != 4) {
                $agentCateSel[$v['id']] = $v['cate_name'];
            }
        }
        foreach ($list as $k => $v) {
            $list[$k]['groupname'] = $this->groupId[$v['groupid']];
        }
        $this->assign('agentCateSel', $agentCateSel);
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    /**
     * 代理列表
     */
    public function codeList()
    {

        $username    = I('get.username', '');
        $status      = I('get.status', '');
        $authorized  = I('get.authorized', '');
        $parentid    = I('get.parentid', '');
        $regdatetime = I('get.regdatetime', '');
        $groupid     = I('get.groupid', '');

        $where['groupid'] = 8;
        if ($groupid != '') {
            $where['groupid'] = $groupid;
        }

        if (!empty($username) && !is_numeric($username)) {
            $where['username'] = ['like', "%" . $username . "%"];
        } elseif (intval($username) - 10000 > 0) {
            $where['id'] = intval($username) - 10000;
        }
        if ($status != '') {
            $where['status'] = $status;
        }
        if ($authorized != '') {
            $where['authorized'] = $authorized;
        }

        if (!empty($parentid) && !is_numeric($parentid)) {
            $User              = M("Member");
            $pid               = $User->where(['username' => $parentid])->getField("id");
            $where['parentid'] = $pid;
        } elseif ($parentid) {
            $where['parentid'] = $parentid;
        }
        if ($regdatetime) {
            list($starttime, $endtime) = explode('|', $regdatetime);
            $where['regdatetime']      = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        $count = M('Member')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Member')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        $agentCateSel  = [];
        $agentCateList = M('member_agent_cate')->select();
        foreach ($agentCateList as $k => $v) {
            if ($v['id'] != 4) {
                $agentCateSel[$v['id']] = $v['cate_name'];
            }
        }
        foreach ($list as $k => $v) {
            $list[$k]['groupname'] = $this->groupId[$v['groupid']];
        }
        $this->assign('agentCateSel', $agentCateSel);
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    public function loginrecord()
    {
        if ($userid = I('get.userid', '')) {
            $where['userid'] = $userid - 10000;
        }
        $type = I('get.type', '');
        if ($type != '') {
            $where['type'] = $type;
        }

        if ($loginip = I('get.loginip', '')) {
            $where['loginip'] = $loginip;
        }
        $logindatetime = urldecode(I("request.logindatetime"));
        if ($logindatetime) {
            list($cstime, $cetime)  = explode('|', $logindatetime);
            $where['logindatetime'] = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d H:i:s')]];
        }
        $count = M('Loginrecord')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size);
        if (!$rows) {
            $rows = $size;
        }

        $page = new Page($count, $rows);
        $list = M('Loginrecord')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();
        foreach ($list as $k => $v) {
            if ($v['type'] == 0) {
                $list[$k]['userid'] += 10000;
            }
        }
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        $this->display();
    }

    //导出登录记录
    public function exportloginrecord()
    {
        if ($userid = I('get.userid', '')) {
            $where['userid'] = $userid - 10000;
        }
        $type = I('get.type', '');
        if ($type != '') {
            $where['type'] = $type;
        }

        if ($loginip = I('get.loginip', '')) {
            $where['loginip'] = $loginip;
        }
        $logindatetime = urldecode(I("request.logindatetime"));
        if ($logindatetime) {
            list($cstime, $cetime)  = explode('|', $logindatetime);
            $where['logindatetime'] = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d H:i:s')]];
        }

        $title = array('ID', '用户类型', '用户编号', '登录时间', '地点', 'IP');
        $data  = M('Loginrecord')
            ->where($where)
            ->order('id desc')
            ->select();
        foreach ($data as $item) {

            switch ($item['type']) {
                case 0:
                    $type   = '商户';
                    $userid = $item['userid'] + 10000;
                    break;
                case 1:
                    $type   = '后台管理员';
                    $userid = $item['userid'];
                    break;
            }
            $list[] = array(
                'id'            => $item['id'],
                'type'          => $type,
                'userid'        => $userid,
                'logindatetime' => $item['logindatetime'],
                'loginip'       => $item['loginip'],
                'loginaddress'  => $item['loginaddress'],
            );
        }
        exportCsv($list, $title);
    }

    //用户充值开关
    public function editCharge()
    {
        if (IS_POST) {
            $userid   = I('post.uid', 0, 'intval');
            $isstatus = I('post.isopen') ? I('post.isopen') : 0;
            $res      = M('Member')->where(['id' => $userid])->save(['open_charge' => $isstatus]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //用户上号权限开关
    public function editCanSh()
    {
        if (IS_POST) {
            $userid   = intval(I('post.uid'));
            $isstatus = I('post.isopen') ? I('post.isopen') : 0;
            $res      = M('Member')->where(['id' => $userid])->save(['can_sh' => $isstatus]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //用户操作金额权限开关
    public function editCanTakeMoney()
    {
        if (IS_POST) {
            $userid   = intval(I('post.uid'));
            $isstatus = I('post.isopen') ? I('post.isopen') : 0;
            $res      = M('Member')->where(['id' => $userid])->save(['can_take_money' => $isstatus]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //用户预存款模式开关
    public function editHasYck()
    {
        if (IS_POST) {
            $userid   = intval(I('post.uid'));
            $isstatus = I('post.isopen') ? I('post.isopen') : 0;
            $res      = M('Member')->where(['id' => $userid])->save(['has_yck' => $isstatus]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    /**
     * 发送冲正交易验证码信息
     */
    public function adjustUserMoney()
    {
        $mobile = I('request.mobile');
        $res    = $this->send('adjustUserMoneySend', $mobile, '冲正交易');
        $this->ajaxReturn(['status' => $res['code']]);
    }

    /**
     * 根据渠道ID获取渠道账号列表
     */
    public function getChannelAccount()
    {

        $uid             = I('request.uid', 0, 'intval');
        $productUserList = M('ProductUser')->where(['userid' => $uid, 'status' => 1])->select();
        //因为有些渠道是轮询的，要将所有渠道id处理出来
        $channelIds = [];
        foreach ($productUserList as $k => $v) {
            if ($v['polling']) {
                $channls = explode('|', $v['weight']);
                foreach ($channls as $k1 => $v1) {
                    $channelIds[] = rtrim($v1, ':');
                }
            } else {
                $channelIds[] = $v['channel'];
            }
        }
        //查询所有的子账号
        $channelAccountList = M('channelAccount')->field('id,channel_id,title,add_user_name,add_user_id')->where(['channel_id' => ['in', $channelIds], 'status' => 1])->select();
        //查询所有的通道
        $channelList = M('channel')->field('id,title')->where(['id' => ['in', $channelIds], 'status' => 1])->select();

        //查询已自定的子账号
        $userChannelAccountInfo = M('UserChannelAccount')->field('status,account_ids')->where(['userid' => $uid])->find();
        $accountIds             = $userChannelAccountInfo['account_ids'];
        $status                 = $userChannelAccountInfo['status'];
        if ($accountIds) {
            $accountIds = explode(',', $accountIds);
            foreach ($channelAccountList as $k => $v) {
                if (in_array($v['id'], $accountIds)) {
                    $channelAccountList[$k]['checked'] = true;
                } else {
                    $channelAccountList[$k]['checked'] = false;
                }
            }
        }

        //获取可以用的子账号和通道
        $list = [];
        foreach ($channelList as $k => $v) {
            foreach ($channelAccountList as $k1 => $v1) {
                if ($v1['channel_id'] == $v['id']) {
                    $list[$v['title']][] = $v1;
                }
            }
        }
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('id', $id);
        $this->assign('uid', $uid);
        $this->assign('channel', $channel);
        $this->assign('polling', $polling);
        $this->assign('pid', $pid);
        $this->display();
    }

    public function saveChannelAccout()
    {
        $uid                 = I('request.userid', 0,'intval');
        $account             = I('request.account');
        $status              = I('request.status',0,'intval');
        $count               = M('UserChannelAccount')->where(['userid' => $uid])->count();
        $data['account_ids'] = implode(',', $account);
        $data['status']      = $status;
        if ($count) {
            $res = M('UserChannelAccount')->where(['userid' => $uid])->save($data);
        } else {
            $data['userid'] = $uid;
            $res            = M('UserChannelAccount')->add($data);
        }
        $this->ajaxReturn(['status' => $res]);
    }

    /**
     * 解绑谷歌验证器
     */
    public function unbindGoogle()
    {
        if (IS_POST) {
            $id  = I('post.uid', 0, 'intval');
            $info = M('Member')->where(['id' => $id])->find();
            if(empty($info)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
            }
            if($info['google_secret_key'] == '') {
                $this->ajaxReturn(['status' => 0, 'msg' => '用户未绑定谷歌验证器']);
            }
            $res = M('Member')->where(['id' => $id])->setField('google_secret_key', '');
            if($res) {
                $this->ajaxReturn(['status' => 1, 'msg' => '解绑成功']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '解绑失败']);
            }
        }
    }
}
