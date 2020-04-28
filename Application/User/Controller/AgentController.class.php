<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace User\Controller;

use Think\Page;

/** 商家代理控制器
 * Class DailiController
 * @package User\Controller
 */
class AgentController extends UserController
{

    public function __construct()
    {
        parent::__construct();
        if($this->fans['groupid'] == 4) {
            $this->error('没有权限！');
        }
    }
    /**
     * 邀请码
     */
    public function invitecode()
    {
        if(!$this->siteconfig['invitecode']) {
            $this->error('邀请码功能已关闭');
        }
        $invitecode = I("get.invitecode");
        $syusername = I("get.syusername");
        $status     = I("get.status");
        if (!empty($invitecode)) {
            $where['invitecode'] = ["like", "%" . $invitecode . "%"];
        }
        if (!empty($syusername)) {
            $syusernameid          = M("Member")->where(['username' => $syusername])->getField("id");
            $where['syusernameid'] = $syusernameid;
        }
        $regdatetime = urldecode(I("request.regdatetime"));
        if ($regdatetime) {
            list($cstime, $cetime) = explode('|', $regdatetime);
            $where['fbdatetime']   = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        if (!empty($status)) {
            $where['status'] = $status;
        }
        $where['fmusernameid'] = $this->fans['uid'];
        $count                 = M('Invitecode')->where($where)->count();
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $page                  = new Page($count, $rows);
        $list                  = M('Invitecode')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();
        foreach ($list as $k => $v) {
            $list[$k]['groupname'] = $this->groupId[$v['regtype']];
        }

        $this->assign("list", $list);
        $this->assign('page', $page->show());
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    /**
     * 添加邀请码
     */
    public function addInvite()
    {
        if(!$this->siteconfig['invitecode']) {
            $this->error('邀请码功能已关闭');
        }
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
        if(!$this->siteconfig['invitecode']) {
            $this->error('邀请码功能已关闭');
        }
        $invitecodestr = random_str(C('INVITECODE')); //生成邀请码的长度在Application/Commom/Conf/config.php中修改
        $Invitecode    = M("Invitecode");
        $id            = $Invitecode->where(['invitecode' => $invitecodestr])->getField("id");
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
            if(!$this->siteconfig['invitecode']) {
                $this->ajaxReturn(['status' => 0, 'msg' => '邀请码功能已关闭']);
            }
            $invitecode = I('post.invitecode');
            $yxdatetime = I('post.yxdatetime');
            $regtype    = I('post.regtype');
            $Invitecode = M("Invitecode");

            //只能添加比自己等级低的商户
            if($regtype >= $this->fans['groupid']) {
                $this->error('没有权限');
            }

            $_formdata  = array(
                'invitecode'     => $invitecode,
                'yxdatetime'     => strtotime($yxdatetime),
                'regtype'        => $regtype,
                'fmusernameid'   => $this->fans['uid'],
                'inviteconfigzt' => 1,
                'fbdatetime'     => time(),
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
            $res = M('Invitecode')->where(['id' => $id , 'fmusernameid' => $this->fans['uid'], 'is_admin' => 0])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    /**
     * 下级会员
     */
    public function member()
    {
        $where['groupid'] = ['neq', 1];
        $username         = I("get.username");
        $status           = I("get.status");
        $authorized       = I("get.authorized");
        $regdatetime      = I('get.regdatetime');
      
      	$boto_all_id = M("Member")->where(['parentid'=>$this->fans['uid']])->field('id,groupid')->select();
        // echo M("Member")->getLastSql();

        // 循环查询下级用户
        $down_all_id = [];
        foreach ($boto_all_id as $k => $v) {
            array_push($down_all_id, $v['id']);
            if($v['groupid']==6 or $v['groupid']==5){
                $next_all_id = M("member")->where(['parentid'=>$v['id']])->field('id,groupid')->select();
                foreach ($next_all_id as $ke => $va) {
                    array_push($down_all_id, $va['id']);
                    if($va['groupid']==5){
                        $last_all_id = M("member")->where(['parentid'=>$va['id']])->field('id')->select();
                        foreach ($last_all_id as $key => $value) {
                            array_push($down_all_id, $value['id']);
                        }
                    }
                }
            }
        }

        $down_all_id = $down_all_id ? $down_all_id : [0];

        $where['id'] = ['in',$down_all_id];      
      
        if (!empty($username) && !is_numeric($username)) {
            $where['username'] = ['like', "%" . $username . "%"];
        } elseif (intval($username) - 10000 > 0) {
            //$where['id'] = intval($username) - 10000;
            $where['id'] = [['in',$down_all_id],[(intval($username)-10000)]];
        }
        if (!empty($status)) {
            $where['status'] = $status;
        }
        if (!empty($authorized)) {
            $where['authorized'] = $authorized;
        }
        //$where['parentid'] = $this->fans['uid'];
        if ($regdatetime) {
            list($starttime, $endtime) = explode('|', $regdatetime);
            $where['regdatetime']      = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        //$where['parentid'] = $this->fans['uid'];
        $count             = M('Member')->where($where)->count();
        $page              = new Page($count, 15);
        $list              = M('Member')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();
        foreach($list as $k=>$v){
            $list[$k]['totalagbalance']=M('MemberAgbalance')->where(['uid'=>$v['id']])->sum('agbalance');
        }
        //echo M('member')->getLastSql();
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    //导出用户
    public function exportuser()
    {
        $username   = I("get.username");
        $status     = I("get.status");
        $authorized = I("get.authorized");
        $groupid    = I("get.groupid");

        if (is_numeric($username)) {
            $map['id'] = array('eq', intval($username) - 10000);
        } else {
            $map['username'] = array('like', '%' . $username . '%');
        }
        if ($status) {
            $map['status'] = array('eq', $status);
        }
        if ($authorized) {
            $map['authorized'] = array("eq", $authorized);
        }
        $map['parentid'] = array('eq', session('user_auth.uid'));
        $regdatetime     = urldecode(I("request.regdatetime"));
        if ($regdatetime) {
            list($cstime, $cetime) = explode('|', $regdatetime);
            $map['regdatetime']    = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }

        $map['groupid'] = $groupid ? array('eq', $groupid) : array('neq', 0);

        $title = array('用户名', '商户号', '用户类型', '上级用户名', '状态', '认证', '可用余额', '冻结余额', '注册时间');
        $data  = M('Member')
            ->where($map)
            ->select();
        foreach ($data as $item) {
            switch ($item['groupid']) {
                case 4:
                    $usertypestr = '商户';
                    break;
                case 5:
                    $usertypestr = '代理商';
                    break;
            }
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

    //用户状态切换
    public function editStatus()
    {
        if (IS_POST) {
            $userid   = intval(I('post.uid'));
            $member = M('Member')->where(['id'=>$userid])->find();
            if(empty($member)) {
                $this->error('用户不存在！');
            }
            if($member['parentid'] != $this->fans['uid']) {
                $this->error('您没有权限查切换该用户状态！');
            }

            $isstatus = I('post.isopen') ? I('post.isopen') : 0;
            $res      = M('Member')->where(['id' => $userid])->save(['status' => $isstatus]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    /**
     * 下级费率设置
     */
    public function userRateEdit()
    {
        //需要加载代理所有开放
        //$this->fans['uid'];
        $userid = I('get.uid', 0, 'intval');
        $member = M('Member')->where(['id'=>$userid])->find();
        if(empty($member)) {
            $this->error('用户不存在！');
        }
        if($member['parentid'] != $this->fans['uid']) {
            $this->error('您没有权限查对该用户进行费率设置！');
        }

        //系统产品列表
        $products = M('Product')
            ->join('LEFT JOIN __PRODUCT_USER__ ON __PRODUCT_USER__.pid = __PRODUCT__.id')
            ->where(['pay_product.status' => 1, 'pay_product.isdisplay' => 1, 'pay_product_user.userid' => $userid, 'pay_product_user.status' => 1])
            ->field('pay_product.id,pay_product.name,pay_product_user.status')
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
                $products[$key]['t0feilv']    = $_tmpData[$item['id']]['t0feilv'] ? $_tmpData[$item['id']]['t0feilv'] : '0.000';
                $products[$key]['t0fengding'] = $_tmpData[$item['id']]['t0fengding'] ? $_tmpData[$item['id']]['t0fengding'] : '0.000';
                $products[$key]['feilv']    = $_tmpData[$item['id']]['feilv'] ? $_tmpData[$item['id']]['feilv'] : '0.000';
                $products[$key]['fengding'] = $_tmpData[$item['id']]['fengding'] ? $_tmpData[$item['id']]['fengding'] : '0.000';
            }
        }

        $this->assign('products', $products);
        $this->display();
    }
    //保存费率
    public function saveUserRate()
    {
        if (IS_POST) {
            $userid = intval(I('post.userid'));
            $member = M('Member')->where(['id'=>$userid])->find();
            if(empty($member)) {
                $this->error('用户不存在！');
            }
            // if($member['parentid'] != $this->fans['uid']) {
            //     $this->error('您没有权限查对该用户进行费率设置！');
            // }
            $rows   = I('post.u/a');
            $datalist = [];
            foreach ($rows as $key => $item) {
                $agent_rate = M('Userrate')->where(['userid' => $this->fans['uid'], 'payapiid' => $key])->find();
                if($item['feilv'] < $agent_rate['feilv']) {
                    $this->ajaxReturn(['status' => 0, 'msg'=> 'T+1费率不能低于代理成本！']);
                }
                if($item['t0feilv'] < $agent_rate['t0feilv']) {
                    $this->ajaxReturn(['status' => 0, 'msg'=> 'T+0费率不能低于代理成本！']);
                }
                $rates = M('Userrate')->where(['userid' => $userid, 'payapiid' => $key])->find();
                if ($rates) {
                    $datalist[] = ['id' => $rates['id'], 'userid' => $userid, 'payapiid' => $key, 'feilv' => $item['feilv'], 'fengding' => $item['fengding'], 't0feilv' => $item['t0feilv'], 't0fengding' => $item['t0fengding']];
                } else {
                    $datalist[] = ['userid' => $userid, 'payapiid' => $key, 'feilv' => $item['feilv'], 'fengding' => $item['fengding'], 't0feilv' => $item['t0feilv'], 't0fengding' => $item['t0fengding']];
                }
            }
            M('Userrate')->addAll($datalist, [], true);
            $this->ajaxReturn(['status' => 1]);
        }
    }

    public function checkUserrate()
    {
        if (IS_POST) {
            $pid  = I('post.pid', 0, 'intval');
            $rate = I('post.feilv');
            $t = I('post.t', 1);
            if ($pid) {
                $field = $t == 0? 't0feilv' : 'feilv';
                $selffeilv = M('Userrate')->where(['userid' => $this->fans['uid'], 'payapiid' => $pid])->getField($field);
                if (($selffeilv * 1000) >= ($rate * 1000)) {
                    $this->ajaxReturn(['status' => 1]);
                }
            }
        }
    }
    //下级流水
    public function childord()
    {
        $userid = I('get.userid', 0, 'intval');
        if(!$userid) {
            $this->error('缺少参数！');
        }
        $member = M('Member')->where(['id'=>$userid])->find();
        if(empty($member)) {
            $this->error('用户不存在！');
        }
        if($member['parentid'] != $this->fans['uid']) {
            $this->error('您没有权限查看该用户信息！');
        }
        $userid = $userid + 10000;
        $data   = array();

        $where = array('pay_memberid' => $userid);
        //商户号
        $memberid = I("request.memberid");
        if ($memberid) {
            $where['pay_memberid'] = $memberid;
        }
        //提交时间
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $where['pay_applydate'] = $poundageMap['datetime'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        //成功时间
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime)    = explode('|', $successtime);
            $where['pay_successdate'] = $poundageMap['datetime'] = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
        }
        //查询下级数据
        $where['pay_status'] = ['in', '1,2'];
        $statistic = M('Order')->field(['sum(`pay_amount`) pay_amount, sum(`pay_poundage`) pay_poundage, sum(`pay_actualamount`) pay_actualamount'])->where($where)->find();
        //代理分润
        $poundageMap['tcuserid'] = $userid - 10000;
        $poundageMap['userid'] = $this->fans['uid'];
        $poundageMap['lx'] = 9;
        $pay_poundage = M('moneychange')->where($poundageMap)->sum('money');
        $this->assign('pay_amount', number_format($statistic['pay_amount'], 2));
        $this->assign('pay_poundage', number_format($pay_poundage, 2));
        $this->assign('pay_actualamount', number_format($statistic['pay_actualamount'], 2));

        //分页
        $count = M('Order')->where($where)->count();
        $Page  = new Page($count, 10);
        $data  = M('Order')->join('LEFT JOIN __MEMBER__ ON __MEMBER__.id+10000 = __ORDER__.pay_memberid')->where($where)->field('pay_order.*, pay_member.username')->limit($Page->firstRow . ',' . $Page->listRows)->order(['id' => 'desc'])->select();
        $show  = $Page->show();
        $this->assign('list', $data);
        $this->assign('page', $show);
        $this->display();
    }

    public function addUser()
    {
        $this->display();
    }

    /**
     * 生成用户
     */
    public function saveUser()
    {
        $u             = I('post.u/a');
        $u['username'] = trim($u['username']);
        $u['email'] = trim($u['email']);
        $u['birthday'] = strtotime($u['birthday']);
        if($u['groupid']==8){
            $pre="C";
        }else{
            $pre="U";
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
        $current_user = session('user_auth');
        $siteconfig   = M("Websiteconfig")->find();
      
      	$u['verifycode']['regtype'] = trim($u['groupid']);
      
        $u            = generateUser($u, $siteconfig);

        $s['activatedatetime'] = date("Y-m-d H:i:s");
        $u['parentid']         = $current_user['uid'];
        // $u['groupid'] = $current_user['groupid'];

        // 创建用户
        $res = M('Member')->add($u);
        $save['id']=$res;
        $save['path_id']=$this->fans['path_id'].$pre.$res.',';
        M('Member')->save($save);
        // 发邮件通知用户密码
        sendPasswordEmail($u['username'], $u['email'], $u['origin_password'], $siteconfig);

        $this->ajaxReturn(['status' => $res]);
    }

    /**
     * 下级商户订单
     */
    public function order()
    {
        $where['groupid'] = ['neq', 1];
        $createtime      = urldecode(I('get.createtime'));
        $successtime = urldecode(I("request.successtime"));
        $memberid = I("request.memberid");
        $body = I("request.body");
        $orderid = I("request.orderid");
        if ($memberid) {
            $where['pay_memberid'] = array('eq', $memberid);
        }
        $this->assign('memberid', $memberid);
        if ($orderid) {
            $where['out_trade_id'] = $orderid;
        }
        $this->assign('orderid', $orderid);
        if ($createtime) {
            list($starttime, $endtime) = explode('|', $createtime);
            $where['pay_applydate']      = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        $this->assign('createtime', $createtime);
        if ($successtime) {
            list($starttime, $endtime) = explode('|', $successtime);
            $where['pay_successdate']     = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        $this->assign('successtime', $successtime);
        if ($body) {
            $where['pay_productname'] = array('eq', $body);
        }
        $this->assign('body', $body);
        /*
        $status = I("request.status",0,'intval');
        if ($status) {
            $where['pay_status'] = array('eq',$status);
        }
        */
      	
      	// 自己添加，分账状态
        $fenzhang = I("request.fenzhang");
        if ($fenzhang != "") {
            if ($fenzhang == '1') {
                $where['pay_fenzhang'] = array('like', "分账成功%");
            } else {
                $where['pay_fenzhang'] = array('like', "分账失败%");
            }
        }
        $this->assign('fenzhang', $fenzhang);
      
      
        $where['pay_status'] = array('in','1,2');
        $pay_memberid = [];
        $user_id = M('Member')->where(['parentid'=>$this->fans['uid']])->field('id,groupid')->select();
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        if($user_id) {
            //foreach($user_id as $k => $v) {
            //    array_push($pay_memberid, $v+10000);
            //}
          
          	foreach($user_id as $k => $v) {
                array_push($pay_memberid, $v['id']+10000);
                if($v['groupid']==6 or $v['groupid']==5){
                    $next_all_id = M('member')->where(['parentid'=>$v['id']])->field('id,groupid')->select();
                    foreach ($next_all_id as $ke => $va) {
                        array_push($pay_memberid, $va['id']+10000);
                        if($va['groupid']==5){
                            $last_all_id = M('member')->where(['parentid'=>$va['id']])->field('id')->select();
                            foreach ($last_all_id as $key => $value) {
                                array_push($pay_memberid, $value['id']+10000);
                            }
                        }
                    }
                }
            }
          
          
            if(!$createtime and !$successtime) {
                //今日成功交易总额
                $todayBegin = date('Y-m-d').' 00:00:00';
                $todyEnd = date('Y-m-d').' 23:59:59';
                $stat['todaysum'] = M('Order')->where(['pay_memberid'=>['in', $pay_memberid],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->sum('pay_amount');
                //今日成功笔数
                $stat['todaysuccesscount'] = M('Order')->where(['pay_memberid'=>['in', $pay_memberid],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->count();
                //总成功交易总额
                $totalMap['pay_memberid'] = ['in', $pay_memberid];
                $totalMap['pay_status'] = ['in', '1,2'];
                $stat['totalsum'] = M('Order')->where($totalMap)->sum('pay_amount');
                //总成功笔数
                $stat['totalsuccesscount'] = M('Order')->where($totalMap)->count();
                foreach($stat as $k => $v) {
                    $stat[$k] = $v+0;
                }
                $this->assign('stat', $stat);
            }
            if($memberid) {
                if(in_array($memberid, $pay_memberid)) {
                    $where['pay_memberid'] = $memberid;
                } else {
                    $where['pay_memberid'] = 1;
                }
            } else {
                $where['pay_memberid'] = ['in', $pay_memberid];
            }
            //如果指定时间范围则按搜索条件做统计
            if ($createtime || $successtime) {
                $sumMap = $where;
                $field                    = ['sum(`pay_amount`) pay_amount', 'sum(`pay_actualamount`) pay_actualamount', 'count(`id`) success_count'];
                $sum                      = M('Order')->field($field)->where($sumMap)->find();
                foreach ($sum as $k => $v) {
                    $sum[$k] += 0;
                }
                $this->assign('sum', $sum);
            }
            //分页
            $count = M('Order')->where($where)->count();
            $Page  = new Page($count, $rows);
            $data  = M('Order')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order(['id' => 'desc'])->select();
        } else {
            $stat['todaysum'] = $stat['todaysuccesscount'] = $stat['totalsum'] = $stat['totalsuccesscount'] = 0;
            $count = 0;
            $Page  = new Page($count, $rows);
            $data = [];
        }
        $show  = $Page->show();
        $this->assign('list', $data);
        $this->assign('page', $show);
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    /**
     * 导出交易订单
     * */
    public function exportorder()
    {

        $where['groupid'] = ['neq', 1];
        $createtime      = urldecode(I('get.createtime'));
        $successtime = urldecode(I("request.successtime"));
        $memberid = I("request.memberid");
        $body = I("request.body", '', 'strip_tags');
        $orderid = I("request.orderid");
        if ($memberid) {
            $where['pay_memberid'] = array('eq', $memberid);
        }
        if ($orderid) {
            $where['out_trade_id'] = $orderid;
        }
        if ($createtime) {
            list($starttime, $endtime) = explode('|', $createtime);
            $where['pay_applydate']      = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        if ($successtime) {
            list($starttime, $endtime) = explode('|', $successtime);
            $where['pay_successdate']     = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        if ($body) {
            $where['pay_productname'] = array('eq', $body);
        }
        $status = I("request.status",0,'intval');
        if ($status) {
            $where['pay_status'] = array('eq',$status);
        }
        $where['pay_status'] = array('in','1,2');
        $pay_memberid = [];
        $user_id = M('Member')->where(['parentid'=>$this->fans['uid']])->getField('id', true);
        if($user_id) {
            foreach($user_id as $k => $v) {
                array_push($pay_memberid, $v+10000);
            }
            if($memberid) {
                if(in_array($memberid, $pay_memberid)) {
                    $where['pay_memberid'] = $memberid;
                } else {
                    $where['pay_memberid'] = 1;
                }
            } else {
                $where['pay_memberid'] = ['in', $pay_memberid];
            }
            $data  = M('Order')->where($where)->order(['id' => 'desc'])->select();
        } else {
            $data = [];
        }
        $title = array('订单号','商户编号','交易金额','手续费','实际金额','提交时间','成功时间','通道','状态');
        foreach ($data as $item){

            switch ($item['pay_status']){
                case 0:
                    $status = '未处理';
                    break;
                case 1:
                    $status = '成功，未返回';
                    break;
                case 2:
                    $status = '成功，已返回';
                    break;
            }
            $list[] = array(
                'pay_orderid'=>$item['out_trade_id'] ? $item['out_trade_id']:$item['pay_orderid'],
                'pay_memberid'=>$item['pay_memberid'],
                'pay_amount'=>$item['pay_amount'],
                'pay_poundage'=>$item['pay_poundage'],
                'pay_actualamount'=>$item['pay_actualamount'],
                'pay_applydate'=>date('Y-m-d H:i:s',$item['pay_applydate']),
                'pay_successdate'=>date('Y-m-d H:i:s',$item['pay_successdate']),
                'pay_zh_tongdao'=>$item['pay_zh_tongdao'],
                'pay_status'=>$status,
            );
        }
        $numberField = ['pay_amount', 'pay_poundage', 'pay_actualamount'];
        exportexcel($list, $title, $numberField);
    }
  
  
  	// 自己添加
    public function appoint()
    {
        $uid             = I('request.userid');
        die('非法进入！');
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
      
        //$channelIds = $channelIds ? $channelIds : [0];

        //查询所有的子账号
        $channelAccountList = M('channelAccount')->field('id,channel_id,title,add_user_id')->where(['channel_id' => ['in', $channelIds], 'status' => 1,'add_user_id'=>$this->fans['uid']])->select();
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
        $uid                 = I('request.userid');
        $account             = I('request.account');
        $status              = I('request.status',0);
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

    //下级代理提现申请
    public function tixian(){
        //通道
        $banklist = M("Product")->field('id,name,code')->select();
        $this->assign("banklist", $banklist);

        $where    = array();
        $memberid = I("get.memberid");
        if ((intval($memberid) - 10000) > 0) {
            $where['userid'] = array('eq', $memberid - 10000);
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['payapiid'] = array('eq', $tongdao);
        }
        $T = I("request.T");
        if ($T != "") {
            $where['t'] = array('eq', $T);
        }
        $status = I("request.status", 0, 'intval');
        if ($status) {
            $where['status'] = array('eq', $status);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime) = explode('|', $createtime);
            $where['sqdatetime']   = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d')]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime) = explode('|', $successtime);
            $where['cldatetime']   = ['between', [$sstime, $setime ? $setime : date('Y-m-d')]];
        }
        $where['cuserid']=$this->fans['uid'];
        //统计总结算信息
        $totalMap           = $where;
        $totalMap['status'] = 2;
        //结算金额
        $stat['total'] = round(M('tklist')->where($totalMap)->sum('money'), 2);
        //待结算
        $totalMap['status'] = ['in', '0,1'];
        $stat['total_wait'] = round(M('tklist')->where($totalMap)->sum('money'), 2);
        //完成笔数
        $totalMap['status']          = 2;
        $stat['total_success_count'] = M('tklist')->where($totalMap)->count();
        //总驳回笔数
        $totalMap['status']       = 3;
        $stat['alltotal_fail_count'] = M('tklist')->where($totalMap)->count();
       //平台手续费利润
        $totalMap['status']   = 2;
        $stat['total_profit'] = M('tklist')->where($totalMap)->sum('sxfmoney');

        //统计今日结算信息
        $map['cuserid'] = $this->fans['uid'];
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        //今日结算总金额
        $map['cldatetime']   = array('between', array(date('Y-m-d H:i:s', $beginToday), date('Y-m-d H:i:s', $endToday)));
        $map['status']       = 2;
        $stat['totay_total'] = round(M('tklist')->where($map)->sum('money'), 2);
        //今日待结算
        unset($map['cldatetime']);
        $map['sqdatetime']  = array('between', array(date('Y-m-d H:i:s', $beginToday), date('Y-m-d H:i:s', $endToday)));
        $map['status']      = ['in', '0,1'];
        $stat['totay_wait'] = round(M('tklist')->where($map)->sum('money'), 2);
        //今日完成笔数
        unset($map['sqdatetime']);
        $map['cldatetime']           = array('between', array(date('Y-m-d H:i:s', $beginToday), date('Y-m-d H:i:s', $endToday)));
        $map['status']               = 2;
        $stat['totay_success_count'] = M('tklist')->where($map)->count();
        //今日驳回笔数
       unset($map['sqdatetime']);
        $map['cldatetime']           = array('between', array(date('Y-m-d H:i:s', $beginToday), date('Y-m-d H:i:s', $endToday)));
        $map['status']            = 3;
        $stat['totay_fail_count'] = M('tklist')->where($map)->count();
      
      //今日平台手续费利润
        unset($map['sqdatetime']);
        $map['cldatetime']    = array('between', array(date('Y-m-d H:i:s', $beginToday), date('Y-m-d H:i:s', $endToday)));
        $map['status']        = 2;
        $stat['totay_profit'] = M('tklist')->where($map)->sum('sxfmoney');
      
        //统计本月结算信息
        $monthBegin = date('Y-m-01') . ' 00:00:00';
        //本月结算总金额
        $map['cldatetime']   = array('egt', date('Y-m-d H:i:s', $monthBegin));
        $map['status']       = 2;
        $stat['month_total'] = round(M('tklist')->where($map)->sum('money'), 2);
        //本月待结算
        unset($map['cldatetime']);
        $map['sqdatetime']  = array('egt', date('Y-m-d H:i:s', $monthBegin));
        $map['status']      = ['in', '0,1'];
        $stat['month_wait'] = round(M('tklist')->where($map)->sum('money'), 2);
        //本月完成笔数
        unset($map['sqdatetime']);
        $map['cldatetime']           = array('egt', date('Y-m-d H:i:s', $monthBegin));
        $map['status']               = 2;
        $stat['month_success_count'] = M('tklist')->where($map)->count();
        //本月驳回笔数
        unset($map['cldatetime']);
        $map['sqdatetime']        = array('egt', date('Y-m-d H:i:s', $monthBegin));
        $map['status']            = 3;
        $stat['month_fail_count'] = M('tklist')->where($map)->count();
        //本月平台手续费利润
        unset($map['sqdatetime']);
        $map['cldatetime']    = array('egt', $monthBegin);
        $map['status']        = 2;
        $stat['month_profit'] = M('tklist')->where($map)->sum('sxfmoney');
        foreach ($stat as $k => $v) {
            $stat[$k] += 0;
        }
        $this->assign('stat', $stat);
        $count = M('Tklist')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Tklist')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign("page", $page->show());
        C('TOKEN_ON', false);
        $this->display();
    }


    /**
     * 订单列表
     */
    public function AgOrder()
    {
        //银行
        $tongdaolist = M("Channel")->field('id,code,title')->select();
        $this->assign("tongdaolist", $tongdaolist);

        //通道
        $banklist = M("Product")->field('id,name,code')->select();
        $this->assign("banklist", $banklist);

        $where    = array();
        $where['O.owner_id'] = $this->fans['uid'];
        $memberid = I("request.memberid");
        if ($memberid) {
            $where['O.pay_memberid'] = array('eq', $memberid);
            $todaysumMap['pay_memberid'] =  $monthsumMap['pay_memberid'] = $nopaidsumMap['pay_memberid'] =  $monthNopaidsumMap['pay_memberid'] = array('eq', $memberid);
            $profitMap['userid'] = $profitSumMap['userid']= $memberid-10000;
        }
        $this->assign('memberid', $memberid);
        $orderid = I("request.orderid");
        if ($orderid) {
            $where['O.out_trade_id'] = $orderid;
        }
        $this->assign('orderid', $orderid);
        $ddlx = I("request.ddlx", "");
        if ($ddlx != "") {
            $where['O.ddlx'] = array('eq', $ddlx);
        }
        $this->assign('ddlx', $ddlx);
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['O.channel_id'] = array('eq', $tongdao);
        }
        $this->assign('tongdao', $tongdao);
        $bank = I("request.bank", '', 'strip_tags');
        if ($bank) {
            $where['O.pay_bankcode'] = array('eq', $bank);
        }
        $this->assign('bank', $bank);
        $payOrderid = I('get.payorderid', '');

        // exit;
        if ($payOrderid) {
            $where['O.pay_orderid'] = array('eq', $payOrderid);
            $profitMap['transid'] = $payOrderid;
        }
        $this->assign('payOrderid',$payOrderid);
        $body = I("request.body", '', 'strip_tags');
        if ($body) {
            $where['O.pay_productname'] = array('eq', $body);
        }
        $this->assign('body', $body);
        $status = I("request.status");
        if ($status != "") {
            if ($status == '1or2') {
                $where['O.pay_status'] = array('between', array('1', '2'));
            } else {
                $where['O.pay_status'] = array('eq', $status);
            }
        }
        $this->assign('status', $status);

        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $where['O.pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
            $profitMap['datetime'] = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d H:i:s')]];
        }
        $this->assign('createtime', $createtime);
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime)    = explode('|', $successtime);
            $where['O.pay_successdate'] = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
            $profitMap['datetime'] = ['between', [$sstime, $setime ? $setime : date('Y-m-d H:i:s')]];
        }
        $this->assign('successtime', $successtime);
        $count = M('Order')->alias('as O')->where($where)->count();

        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }

        $page = new Page($count, $rows);
        $list = M('Order')->alias('as O')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        //查询支付成功的订单的手续费，入金费，总额总和
        $countWhere               = $where;
        $countWhere['O.pay_status'] = ['between', [1, 2]];
        $field                    = ['sum(`pay_amount`) pay_amount','sum(`cost`) cost', 'sum(`pay_poundage`) pay_poundage', 'sum(`pay_actualamount`) pay_actualamount', 'count(`id`) success_count'];
        $sum                      = M('Order')->alias('as O')->field($field)->where($countWhere)->find();
        $countWhere['O.pay_status'] = 0;
        //失败笔数
        $sum['fail_count'] =  M('Order')->alias('as O')->where($countWhere)->count();
        //投诉保证金冻结金额
        $map = $where;
        $map['C.status'] = 0;
        $sum['complaints_deposit_freezed'] = M('complaints_deposit')->alias('as C')->join('LEFT JOIN __ORDER__ AS O ON C.pay_orderid=O.pay_orderid')
            ->where($map)
            ->sum('freeze_money');
        $sum['complaints_deposit_freezed'] += 0;
        $map['C.status'] = 1;
        $sum['complaints_deposit_unfreezed'] = M('complaints_deposit')->alias('as C')->join('LEFT JOIN __ORDER__ AS O ON C.pay_orderid=O.pay_orderid')
            ->where($map)
            ->sum('freeze_money');
        $sum['complaints_deposit_unfreezed'] += 0;
        $profitMap['lx'] = 9;
        $sum['memberprofit'] = M('moneychange')->where($profitMap)->sum('money');
       
        $sum['pay_poundage'] = $sum['pay_poundage'] - $sum['cost'] - $sum['memberprofit'];//原始
        foreach ($sum as $k => $v) {
            $sum[$k] += 0;
           $sum[$k] = number_format($sum[$k],2,'.','');
        }
        //统计订单信息
        $is_month = true;
        //下单时间
        if ($createtime) {
            $cstartTime = strtotime($cstime);
            $cendTime   = strtotime($cetime) ? strtotime($cetime) : time();
            $is_month   = $cendTime - $cstartTime > self::TMT ? true : false;
        }
        //支付时间
        if ($successtime) {
            $pstartTime = strtotime($sstime);
            $pendTime   = strtotime($setime) ? strtotime($setime) : time();
            $is_month   = $pendTime - $pstartTime > self::TMT ? true : false;
        }

        $time       = $successtime ? 'pay_successdate' : 'pay_applydate';
        $dateFormat = $is_month ? '%Y年-%m月' : '%Y年-%m月-%d日';
        $field      = "FROM_UNIXTIME(" . $time . ",'" . $dateFormat . "') AS date,SUM(pay_amount) AS amount,SUM(pay_poundage) AS rate,SUM(pay_actualamount) AS total";
        $_mdata     = M('Order')->alias('as O')->field($field)->where($where)->group('date')->select();
        $mdata      = [];
        foreach ($_mdata as $item) {
            $mdata['amount'][] = $item['amount'] ? $item['amount'] : 0;
            $mdata['mdate'][]  = "'" . $item['date'] . "'";
            $mdata['total'][]  = $item['total'] ? $item['total'] : 0;
            $mdata['rate'][]   = $item['rate'] ? $item['rate'] : 0;
        }
        if ($status == '1or2' || $status == 1 || $status == 2) {
            //今日成功交易总额
            $todayBegin = date('Y-m-d').' 00:00:00';
            $todyEnd = date('Y-m-d').' 23:59:59';
            $todaysumMap['pay_successdate'] = ['between', [strtotime($todayBegin), strtotime($todyEnd)]];
            $todaysumMap['pay_status'] = ['in', '1,2'];
            $todaysumMap['owner_id'] = $this->fans['uid'];
            $stat['todaysum'] = M('Order')->where($todaysumMap)->sum('pay_amount');

            //平台收入
            $pay_poundage = M('Order')->where($todaysumMap)->sum('pay_poundage');
            $profitSumMap['datetime'] = ['between', [$todayBegin, $todyEnd]];
            $profitSumMap['lx'] = 9;
            $profitSum = M('moneychange')->where($profitSumMap)->sum('money');
            $order_cost = M('Order')->where($todaysumMap)->sum('cost');
            $stat['platform'] = $pay_poundage - $order_cost - $profitSum;
            //代理收入
            $stat['agentIncome'] = $profitSum;

            //本月成功交易总额
            $monthBegin = date('Y-m-01').' 00:00:00';
            $monthsumMap['pay_successdate'] = ['egt', strtotime($monthBegin)];
            $monthsumMap['pay_status'] = ['in', '1,2'];
            $monthsumMap['owner_id'] = $this->fans['uid'];
            $stat['monthsum'] = M('Order')->where($monthsumMap)->sum('pay_amount');

            //本月平台收入
            $pay_poundage = M('Order')->where($monthsumMap)->sum('pay_poundage');
            $profitSumMap['datetime'] = ['egt', $monthBegin];
            $profitSumMap['lx'] = 9;
            $profitSum = M('moneychange')->where($profitSumMap)->sum('money');
            $order_cost = M('Order')->where($monthsumMap)->sum('cost');
            $stat['monthPlatform'] = $pay_poundage - $order_cost - $profitSum;
            //代理收入
            $stat['monthAgentIncome'] = $profitSum;

            if($status == 1) {
                $nopaidsumMap['pay_applydate'] = ['between', [strtotime($todayBegin), strtotime($todyEnd)]];
                $nopaidsumMap['pay_status'] = 1;
                $nopaidsumMap['owner_id'] = $this->fans['uid'];
                //今日异常订单总额
                $stat['todaynopaidsum'] = M('Order')->where($nopaidsumMap)->sum('pay_amount');
                //今日异常订单笔数
                $stat['todaynopaidcount'] = M('Order')->where($nopaidsumMap)->count();

                $monthNopaidsumMap['pay_applydate'] = ['egt', strtotime($todayBegin)];
                $monthNopaidsumMap['pay_status'] = 1;
                 $monthNopaidsumMap['owner_id'] = $this->fans['uid'];
                //本月异常订单总额
                $stat['monthNopaidsum'] = M('Order')->where($monthNopaidsumMap)->sum('pay_amount');
                //本月异常订单笔数
                $stat['monthNopaidcount'] = M('Order')->where($monthNopaidsumMap)->count();
            }
        } elseif($status == 0) {
            //今日未支付订单总额
            $todayBegin = date('Y-m-d').' 00:00:00';
            $todyEnd = date('Y-m-d').' 23:59:59';
            $monthBegin = date('Y-m-01').' 00:00:00';
            $stat['todaynopaidsum'] = M('Order')->where(['pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>0,'owner_id'=>$this->fans['uid']])->sum('pay_amount');
            $stat['monthNopaidsum'] = M('Order')->where(['pay_applydate'=>['egt', strtotime($monthBegin)], 'pay_status'=>0,'owner_id'=>$this->fans['uid']])->sum('pay_amount');
            $nopaidMap = $where;
            $nopaidMap['pay_status'] = 0;
            $stat['totalnopaidsum'] = M('Order')->alias('as O')->where($nopaidMap)->sum('pay_amount');
        }
        foreach($stat as $k => $v) {
            $stat[$k] = $v+0;
           $stat[$k] = number_format($stat[$k],2,'.','');
        }
        $this->assign('stat', $stat);
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign("mdata", $mdata);
        $this->assign('stamount',$sum['pay_amount']);
        $this->assign('page', $page->show());
        $this->assign('strate', $sum['pay_poundage']);
        $this->assign('strealmoney', $sum['pay_actualamount']);
        $this->assign('success_count', $sum['success_count']);
        $this->assign('fail_count', $sum['fail_count']);
        $this->assign('memberprofit', $sum['memberprofit']);
        $this->assign('complaints_deposit_freezed', $sum['complaints_deposit_freezed']);
        $this->assign('complaints_deposit_unfreezed', $sum['complaints_deposit_unfreezed']);
        C('TOKEN_ON', false);
        $this->display();
    }

    //设置订单为已支付
    public function setOrderPaid() {

        if(IS_POST) {
            $orderid = I('request.orderid');
            $auth_type = I('request.auth_type',0,'intval');
            if(!$orderid) {
                $this->ajaxReturn(['status' => 0, 'msg' => "缺少订单ID！"]);
            }
            $order = M('Order')->where(['id'=>$orderid])->find();
            if($order['status'] != 0 && $order['status'] != 3) {
                $this->ajaxReturn(['status' => 0, 'msg' => "该订单状态为已支付！"]);
            }
            $payModel = D('Pay');
            $res = $payModel->completeOrder($order['pay_orderid'], '', 0);
            if ($res) {
                $this->ajaxReturn(['status' => 1, 'msg' => "设置成功！"]);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => "设置失败"]);
            }
        } else {
            $orderid = I('request.orderid');
            if(!$orderid) {
                $this->error('缺少参数');
            }
            $order = M('Order')->where(['id'=>$orderid])->find();
            if(empty($order)) {
                $this->error('订单不存在');
            }
            if($order['status'] != 0 && $order['status'] != 3) {
                $this->error("该订单状态为已支付！");
            }
            $this->assign('order', $order);
            $this->display();
        }
    }

     /**
     * 查看订单
     */
    public function Ordershow()
    {
        $id = I("get.oid", 0, 'intval');
        if ($id) {
            $order = M('Order')
                ->join('LEFT JOIN __MEMBER__ ON (__MEMBER__.id + 10000) = __ORDER__.pay_memberid')
                ->field('pay_member.id as userid,pay_member.username,pay_member.realname,pay_order.*')
                ->where(['pay_order.id' => $id])
                ->find();
        }
        $this->assign('order', $order);
        $this->display();
    }

    /**
     * 冻结订单
     * author: feng
     * create: 2018/6/27 22:55
     */
    public function doForzen(){
        $orderId= I('orderid/d',0);
        if(!$orderId)
            $this->error("订单ID有误");
        $order=M("order")->where(['id'=>$orderId])->find();
        if($order["pay_status"]<1){
            $this->error("该订单没有支付成功，不能冻结");
        }
        if($order["lock_status"]>0){
            $this->error("该订单已冻结");
        }
        $userId=(int)$order['pay_memberid']-10000;

        M()->startTrans();
        $order=M("order")->where(array("id"=>$orderId,"pay_status"=>['in','1,2'],"lock_status"=>['LT',1]))->lock(true)->find();

        //需要检测是否已解冻，如果未解冻直接修改自动解冻状态，如果解冻，直接扣余额
        $maps['status'] = array('eq',0);
        $maps['orderid']=array('eq',$order['pay_orderid']);
        $blockedLog = M('blockedlog')->where($maps)->find();
        if($blockedLog){
            $res=M('blockedlog')->where(array('id'=>$blockedLog['id']))->save(array('status'=>1));

        }else{
            $res        = M('member')->where(array('id' => $userId,'balance'=>array("EGT",$order['pay_actualamount'])))->save([
                'balance' => array('exp', "balance-".$order['pay_actualamount']),
                'blockedbalance' => array('exp', "blockedbalance+".$order['pay_actualamount']),
            ]);
        }

       $orderRe =M("order")->where(array("id"=>$orderId,"pay_status"=>['in','1,2'],"lock_status"=>['LT',1]))->save(['lock_status'=>1]);
        if($res!==false&&$orderRe!==false){
            M()->commit();
            $this->success('冻结成功');
        }else{
            M()->rollback();
            $this->error('冻结失败'.$res.'='.$orderRe);
        }


    }
    /**解冻
     * author: feng
     * create: 2018/6/28 0:06
     */
    public function thawOrder(){
        $orderId= I('orderid/d',0);
        if(!$orderId)
            $this->error("订单ID有误");
        $order=M("order")->where(['id'=>$orderId])->find();
        if($order["pay_status"]<1){
            $this->error("该订单没有支付成功，不能解冻");
        }
        if($order["lock_status"]!=1){
            $this->error("该订单没有冻结");
        }
        $userId=$order['pay_memberid']-10000;
        M()->startTrans();
        $order=M("order")->where(array("id"=>$orderId,"pay_status"=>['in','1,2'],"lock_status"=>['eq',1]))->lock(true)->find();
        //需要检测是否已解冻，如果未解冻直接修改自动解冻状态，如果解冻，直接扣余额
         $res        = M('member')->where(array('id' => $userId,'blockedbalance'=>array('EGT',$order['pay_actualamount'])))->save([
                'balance' => array('exp', "balance+".$order['pay_actualamount']),
                'blockedbalance' => array('exp', "blockedbalance-".$order['pay_actualamount']),
            ]);
         //记录日志
         $orderRe=M("order")->where(array("id"=>$orderId,"pay_status"=>['in','1,2'],"lock_status"=>['eq',1]))->save(array("lock_status"=>2));
        if($res!==false&&$orderRe!==false){
            M()->commit();
            $this->success('解冻成功');
        }else{
            M()->rollback();
            $this->error('解冻失败');
        }
    }

    /**
     * 对账单
     */
    public function duizhangdan()
    {
        if($this->fans[groupid] != 7) {
            $this->error('您没有权限访问该页面!');
        }
        $date = urldecode(I("request.date", ''));
        if(!$date) {//默认今日
            $date = date('Y-m-d');
        }
        $this->assign('date', $date);
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        $this->assign('memberid', $memberid);
        if($date>date('Y-m-d') || strtotime($date)<$this->fans['regdatetime']) {
            $this->error('日期错误');
        }

        $time = M('Member')->where(['id'=>$this->fans['uid']])->getField('regdatetime');
        $time = strtotime(date('Y-m-d', $time));
        $timestamp = strtotime($date);
        $count      = ceil(($timestamp-$time)/86400)+1;
        $p = I('get.p', 1, 'intval');
        $page       = new Page($count, 10);
        $xh = $count < 10 ? $count : 10;
        $start_time = $date;
        $offset = ($p-1) * $page->listRows-1;
        if($offset>0) {
            $max_date = strtotime("$start_time -$offset day") - 1;
        } else {
            $max_date = strtotime($date);
        }
        $list = array();
        for($i=0; $i<$xh; $i++) {
            $start_time = $max_date-$i*86400;
            if($start_time<$time) {
                break;
            }
            $begin = date('Y-m-d',$start_time).' 00:00:00';
            $end = date('Y-m-d H:i:s',strtotime(date('Y-m-d',$start_time))+86400-1);
            $list[$i] = $this->getDayReconciliation($begin, $end);
        }
        $this->assign('list', $list);
        $this->assign('page', $page->show());
        $this->assign('time',date('Y-m-d',$time));
        C('TOKEN_ON', false);
        $this->display();
    }

    //获取某天对账单
    private function getDayReconciliation($begin, $end) {

        $date = date('Y-m-d', strtotime($begin));
        $data = M('agreconciliation')->where(['userid'=>$this->fans['uid'],'date'=>$date])->find();
        if(empty($data)) {
            $insertFlag = true;
        } else {
            $insertFlag = false;
        }
        if(empty($data) || (!empty($data) && diffBetweenTwoDays(date('Y-m-d'),$date)<=3)) {//3天内账单实时更新数据
            $data['date'] = $date;
            $data['order_total_count'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_applydate' => ['between', [strtotime($begin), strtotime($end)]]])->count();
            //成功订单数
            $data['order_success_count'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_applydate' => ['between', [strtotime($begin), strtotime($end)]], 'pay_status' => ['in', '1,2']])->count();
            //未支付订单数
            $data['order_fail_count'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_applydate' => ['between', [strtotime($begin), strtotime($end)]], 'pay_status' => 0])->count();
            //订单总额
            $data['order_total_amount'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_applydate' => ['between', [strtotime($begin), strtotime($end)]]])->sum('pay_amount');
            //订单实付总额
            $data['order_success_amount'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_successdate' => ['between', [strtotime($begin), strtotime($end)], 'pay_status' => ['in', '1,2']]])->sum('pay_actualamount');
            if($insertFlag) {
                $data['userid'] = $this->fans['uid'];
                $data['ctime'] = time();
                M('agreconciliation')->add($data);
            } else {
                M('agreconciliation')->where(['id'=>$data['id']])->save($data);
            }
        }
        unset($data['userid']);
        unset($data['ctime']);
        unset($data['id']);
        foreach($data as $k => $v) {
            if($k != 'date') {
                $data[$k] += 0;
                if($k == 'order_total_amount' || $k == 'order_success_amount') {
                    $data[$k] = number_format($data[$k], 2, '.', ',');
                }
            }
        }
        return $data;
    }


    //代理结算
    public function AgeditStatus()
    {
        $id = I("request.id", 0, 'intval');
        if (IS_POST) {
            $status  = I("post.status", 0, 'intval');
            $userid  = I('post.userid', 0, 'intval');
            $tkmoney = I('post.tkmoney');
            if (!$id) {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
            }
            $map['id'] = $id;
            //开启事务
            M()->startTrans();
            $Tklist    = M("Tklist");
            $map['id'] = $id;
            $withdraw  = $Tklist->where($map)->lock(true)->find();
            if (empty($withdraw)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '提款申请不存在']);
            }
            $data           = [];
            $data["status"] = $status;

            //判断状态
            switch ($status) {
                case '2':
                    $data["cldatetime"] = date("Y-m-d H:i:s");
                    break;
                case '3':
                    if ($withdraw['status'] == 1) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请处理中，不能驳回']);
                    } elseif ($withdraw['status'] == 2) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请已打款，不能驳回']);
                    } elseif ($withdraw['status'] == 3) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请已驳回，不能驳回']);
                    }
                    $map['status'] = 0;
                    //驳回操作
                    //1,将金额返回给商户
                    $Member     = M('Member');
                    $memberInfo = $Member->where(['id' => $userid])->lock(true)->find();
                    $res        = $Member->where(['id' => $userid])->save(['balance' => array('exp', "balance+{$tkmoney}")]);
                    if (!$res) {
                        M()->rollback();
                        $this->ajaxReturn(['status' => 0]);
                    }
                    //2,记录流水订单号
                    $arrayField = array(
                        "userid"     => $userid,
                        "ymoney"     => $memberInfo['balance'],
                        "money"      => $tkmoney,
                        "gmoney"     => $memberInfo['balance'] + $tkmoney,
                        "datetime"   => date("Y-m-d H:i:s"),
                        "tongdao"    => 0,
                        "transid"    => $id,
                        "orderid"    => $id,
                        "lx"         => 11,
                        'contentstr' => '结算驳回',
                    );
                    $res = M('Moneychange')->add($arrayField);
                    if (!$res) {
                        M()->rollback();
                        $this->ajaxReturn(['status' => 0]);
                    }
                    //结算驳回退回手续费
                    if ($withdraw['tk_charge_type']) {
                        if($withdraw['cuserid']==0){
                            $res = $Member->where(['id' => $withdraw['userid']])->save(['balance' => array('exp', "balance+{$withdraw['sxfmoney']}")]);
                            if (!$res) {
                                M()->rollback();
                                $fail++;
                                continue;
                            }
                            $chargeField = array(
                                "userid"     => $withdraw['userid'],
                                "ymoney"     => $memberInfo['balance'] + $withdraw['tkmoney'],
                                "money"      => $withdraw['sxfmoney'],
                                "gmoney"     => $memberInfo['balance'] + $withdraw['tkmoney'] + $withdraw['sxfmoney'],
                                "datetime"   => date("Y-m-d H:i:s"),
                                "tongdao"    => 0,
                                "transid"    => $v,
                                "orderid"    => $v,
                                "lx"         => 17,
                                'contentstr' => '手动结算驳回退回手续费',
                            );
                            $res = M('Moneychange')->add($chargeField);
                            if (!$res) {
                                M()->rollback();
                                $fail++;
                                continue;
                            }
                        }else{
                            $res = $Member->here(['uid' => $withdraw['userid'],'ag_uid'=>$withdraw['cuserid']])->save(['agbalance' => array('exp', "agbalance+{$withdraw['sxfmoney']}")]);
                            if (!$res) {
                                M()->rollback();
                                $fail++;
                                continue;
                            }
                            $chargeField = array(
                                "userid"     => $withdraw['userid'],
                                "ymoney"     => $memberInfo['agbalance'] + $withdraw['tkmoney'],
                                "money"      => $withdraw['sxfmoney'],
                                "gmoney"     => $memberInfo['agbalance'] + $withdraw['tkmoney'] + $withdraw['sxfmoney'],
                                "datetime"   => date("Y-m-d H:i:s"),
                                "tongdao"    => 0,
                                "transid"    => $v,
                                "orderid"    => $v,
                                "lx"         => 17,
                                'contentstr' => '手动结算驳回退回手续费',
                            );
                            $res = M('Moneychange')->add($chargeField);
                            if (!$res) {
                                M()->rollback();
                                $fail++;
                                continue;
                            }
                        }
                    }
                    $data["cldatetime"] = date("Y-m-d H:i:s");
                    $data["memo"]       = I('post.memo');
                    break;
                default:
                    # code...
                    break;
            }
            //修改结算的数据
            $res = $Tklist->where($map)->save($data);
            if ($res) {
                M()->commit();
                $this->ajaxReturn(['status' => $res]);
            }

            M()->rollback();
            $this->ajaxReturn(['status' => 0]);

        } else {
            $info = M('Tklist')->where(['id' => $id])->find();
            $this->assign('info', $info);
            $this->display();
        }
    }


     /**
     *  批量委托提现
     */
    public function editwtAllStatus()
    {

        $ids    = I('post.id', '');
        $ids    = explode(',', trim($ids, ','));
        $status = I('post.status', '0');

        $Tklist  = M("Tklist");
        $success = $fail = 0;
        if ($status == 3) {
//一键驳回
            foreach ($ids as $k => $v) {
                try {
                    M()->startTrans();
                    if (intval($v)) {
                        $withdraw = $Tklist->where(['id' => $v])->find();
                        if (empty($withdraw)) {
                            M()->rollback();
                            $fail++;
                            continue;
                        }
                        if ($withdraw['status'] == 1) {
//提款申请处理中，不能驳回
                            M()->rollback();
                            $fail++;
                            continue;
                        } elseif ($withdraw['status'] == 2) {
//提款申请已打款，不能驳回
                            M()->rollback();
                            $fail++;
                            continue;
                        } elseif ($withdraw['status'] == 3) {
//提款申请已驳回，不能驳回
                            M()->rollback();
                            $fail++;
                            continue;
                        }
                        $map['status'] = 0;
                        //驳回操作
                        //1,将金额返回给商户
                        if($withdraw['cuserid']==0){
                            $Member     = M('Member');
                            $memberInfo = $Member->where(['id' => $withdraw['userid']])->lock(true)->find();
                            $res        = $Member->where(['id' => $withdraw['userid']])->save(['balance' => array('exp', "balance+{$withdraw['tkmoney']}")]);
                            if (!$res) {
                                M()->rollback();
                                $fail++;
                                continue;
                            }
                            //2,记录流水订单号
                            $arrayField = array(
                                "userid"     => $withdraw['userid'],
                                "ymoney"     => $memberInfo['balance'],
                                "money"      => $withdraw['tkmoney'],
                                "gmoney"     => $memberInfo['balance'] + $withdraw['tkmoney'],
                                "datetime"   => date("Y-m-d H:i:s"),
                                "tongdao"    => 0,
                                "transid"    => $v,
                                "orderid"    => $v,
                                "lx"         => 11,
                                'contentstr' => '结算驳回',
                            );
                            $res = M('Moneychange')->add($arrayField);
                            if (!$res) {
                                M()->rollback();
                                $fail++;
                                continue;
                            }
                        }else{
                            $Member     = M('MemberAgbalance');
                            $memberInfo = $Member->where(['uid' => $withdraw['userid'],'ag_uid'=>$withdraw['cuserid']])->lock(true)->find();
                            $res = $Member->where(['uid' => $withdraw['userid'],'ag_uid'=>$withdraw['cuserid']])->save(['agbalance' => array('exp', "agbalance+{$withdraw['tkmoney']}")]);
                            if (!$res) {
                                M()->rollback();
                                $fail++;
                                continue;
                            }
                            //2,记录流水订单号
                            $arrayField = array(
                                "userid"     => $withdraw['userid'],
                                "ymoney"     => $memberInfo['agbalance'],
                                "money"      => $withdraw['tkmoney'],
                                "gmoney"     => $memberInfo['agbalance'] + $withdraw['tkmoney'],
                                "datetime"   => date("Y-m-d H:i:s"),
                                "tongdao"    => 0,
                                "transid"    => $v,
                                "orderid"    => $v,
                                "lx"         => 11,
                                'contentstr' => '结算驳回',
                            );
                            $res = M('Moneychange')->add($arrayField);
                            if (!$res) {
                                M()->rollback();
                                $fail++;
                                continue;
                            }
                        }
                        
                        
                        //结算驳回退回手续费
                        if ($withdraw['tk_charge_type']) {
                            if($withdraw['cuserid']==0){
                                $res = $Member->where(['id' => $withdraw['userid']])->save(['balance' => array('exp', "balance+{$withdraw['sxfmoney']}")]);
                                if (!$res) {
                                    M()->rollback();
                                    $fail++;
                                    continue;
                                }
                                $chargeField = array(
                                    "userid"     => $withdraw['userid'],
                                    "ymoney"     => $memberInfo['balance'] + $withdraw['tkmoney'],
                                    "money"      => $withdraw['sxfmoney'],
                                    "gmoney"     => $memberInfo['balance'] + $withdraw['tkmoney'] + $withdraw['sxfmoney'],
                                    "datetime"   => date("Y-m-d H:i:s"),
                                    "tongdao"    => 0,
                                    "transid"    => $v,
                                    "orderid"    => $v,
                                    "lx"         => 17,
                                    'contentstr' => '手动结算驳回退回手续费',
                                );
                                $res = M('Moneychange')->add($chargeField);
                                if (!$res) {
                                    M()->rollback();
                                    $fail++;
                                    continue;
                                }
                            }else{
                                $res = $Member->here(['uid' => $withdraw['userid'],'ag_uid'=>$withdraw['cuserid']])->save(['agbalance' => array('exp', "agbalance+{$withdraw['sxfmoney']}")]);
                                if (!$res) {
                                    M()->rollback();
                                    $fail++;
                                    continue;
                                }
                                $chargeField = array(
                                    "userid"     => $withdraw['userid'],
                                    "ymoney"     => $memberInfo['agbalance'] + $withdraw['tkmoney'],
                                    "money"      => $withdraw['sxfmoney'],
                                    "gmoney"     => $memberInfo['agbalance'] + $withdraw['tkmoney'] + $withdraw['sxfmoney'],
                                    "datetime"   => date("Y-m-d H:i:s"),
                                    "tongdao"    => 0,
                                    "transid"    => $v,
                                    "orderid"    => $v,
                                    "lx"         => 17,
                                    'contentstr' => '手动结算驳回退回手续费',
                                );
                                $res = M('Moneychange')->add($chargeField);
                                if (!$res) {
                                    M()->rollback();
                                    $fail++;
                                    continue;
                                }
                            }
                            
                        }
                        $data['status']     = 3;
                        $data["cldatetime"] = date("Y-m-d H:i:s");
                        $res                = $Tklist->where(['id' => $v, 'status' => 0])->save($data);
                        if ($res === false) {
                            M()->rollback();
                            $fail++;
                            continue;
                        } else {
                            M()->commit();
                            $success++;
                        }
                    } else {
                        M()->rollback();
                        $fail++;
                        continue;
                    }
                } catch (\Exception $e) {
                    M()->rollback();
                    $fail++;
                    continue;
                }
            }
            if ($success > 0) {
                $this->ajaxReturn(['status' => 1, 'msg' => '成功驳回：' . $success . '，失败：' . $fail]);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '驳回失败!']);
            }
        } else {
            foreach ($ids as $k => $v) {
                try {
                    M()->startTrans();
                    if (intval($v)) {
                        $withdraw = $Tklist->where(['id' => $v])->find();
                        if (empty($withdraw)) {
                            M()->rollback();
                            $fail++;
                            continue;
                        }
                        $data = [
                            "status"     => $status,
                            'cldatetime' => date("Y-m-d H:i:s"),
                        ];

                        $res = $Tklist->where(['id' => $v])->save($data);
                        if ($res === false) {
                            M()->rollback();
                            $fail++;
                            continue;
                        } else {
                            M()->commit();
                            $success++;
                        }
                    } else {
                        M()->rollback();
                        $fail++;
                        continue;
                    }
                } catch (\Exception $e) {
                    M()->rollback();
                    $fail++;
                    continue;
                }
            }
            if ($success > 0) {
                $this->ajaxReturn(['status' => 1, 'msg' => '成功完成：' . $success . '，失败：' . $fail]);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '完成操作失败!']);
            }
        }
    }


    /**
     * 导出提款记录
     */
    public function exportagorder()
    {
        $where    = array();
        $where['cuserid']=$this->fans['uid'];
        $memberid = I("get.memberid");
        if ($memberid) {
            $where['userid'] = array('eq', $memberid - 10000);
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['payapiid'] = array('eq', $tongdao);
        }
        $T = I("request.T");
        if ($T != "") {
            $where['t'] = array('eq', $T);
        }
        $status = I("request.status", 0, 'intval');
        if ($status) {
            $where['status'] = array('eq', $status);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime) = explode('|', $createtime);
            $where['sqdatetime']   = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d')]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime) = explode('|', $successtime);
            $where['cldatetime']   = ['between', [$sstime, $setime ? $setime : date('Y-m-d')]];
        }

        $title = array('类型', '商户编号', '结算金额', '手续费', '到账金额', '银行名称', '支行名称', '银行卡号', '开户名', '所属省', '所属市', '申请时间', '处理时间', '状态', "备注");
        $data  = M('Tklist')->where($where)->select();
        foreach ($data as $item) {
            switch ($item['status']) {
                case 0:
                    $status = '未处理';
                    break;
                case 1:
                    $status = '处理中';
                    break;
                case 2:
                    $status = '已打款';
                    break;
                case 3:
                    $status = "已驳回";
                    break;
            }
            switch ($item['t']) {
                case 0:
                    $tstr = 'T + 0';
                    break;
                case 1:
                    $tstr = 'T + 1';
                    break;
            }

            $list[] = array(
                't'            => $tstr,
                'memberid'     => $item['userid'] + 10000,
                'tkmoney'      => $item['tkmoney'],
                'sxfmoney'     => $item['sxfmoney'],
                'money'        => $item['money'],
                'bankname'     => $item['bankname'],
                'bankzhiname'  => $item['bankzhiname'],
                'banknumber'   => $item['banknumber'],
                'bankfullname' => $item['bankfullname'],
                'sheng'        => $item['sheng'],
                'shi'          => $item['shi'],
                'sqdatetime'   => $item['sqdatetime'],
                'cldatetime'   => $item['cldatetime'],
                'status'       => $status,
                "memo"         => $item["memo"],
            );
        }
        $numberField = ['tkmoney', 'sxfmoney', 'money'];
        exportexcel($list, $title, $numberField);
    }



    /**
     * 用户资金操作
     */
    public function usermoney()
    {
        $userid                          = I("get.userid", 0, 'intval');
        $info                            = M("Member")->where(["id" => $userid])->find();
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 增加、减少余额
     */
    public function incrYckMoney()
    {
        $uid         = session('admin_auth')['uid'];

        //是否可以谷歌安全码验证
        if (IS_POST) {
            //开启事物
            if($this->fans['can_take_money']!=1){
                $this->ajaxReturn(['status' => 0, 'msg' => "没有权限，请联系管理！"]);
            }
            M()->startTrans();
            $userid     = I("post.uid", 0, 'intval');
            $cztype     = I("post.cztype");
            $bgmoney    = I("post.bgmoney",0,'float');
            $contentstr = I("post.memo", "");
            $auth_type  = I('post.auth_type', 0, 'intval');
            if ($bgmoney <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "变动金额必须是正数！"]);
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
                if($this->fans['yckbalance']<$bgmoney){
                    $this->ajaxReturn(['status' => 0, 'msg' => '您账户预存款不足']);
                }
                $data2['yckbalance']=array('exp', "yckbalance-" . $bgmoney);
                $gmoney2          = $this->fans['yckbalance'] - $bgmoney;
            } elseif ($cztype == 4) {
                $data["yckbalance"]  = array('exp', "yckbalance-" . $bgmoney);
                $where['yckbalance'] = array('egt', $bgmoney);
                $gmoney           = $info['yckbalance'] - $bgmoney;
                $data2["yckbalance"] = array('exp', "yckbalance+" . $bgmoney);
                $gmoney2          = $this->fans['yckbalance'] + $bgmoney;
            }
            $where['id'] = $userid;
            $where2['id'] = $this->fans['uid'];
            $res1        = M('Member')->where($where)->save($data);
            $res12        = M('Member')->where($where2)->save($data2);
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
            $arrayField2  = array(
                "userid"     => $where2['id'],
                'ymoney'     => $this->fans['yckbalance'],
                "money"      => $bgmoney,
                "gmoney"     => $gmoney2,
                "datetime"   => date("Y-m-d H:i:s"),
                "tongdao"    => '',
                "transid"    => "",
                "orderid"    => "",
                "lx"         => $cztype, // 增减类型
                "contentstr" => $contentstr . '【操作下级预存款变动:' . $date . '】',
            );
            $res2 = moneychangeadd($arrayField);
            $res22 = moneychangeadd($arrayField2);
            //冲正订单
            $arrayRedo = array(
                'user_id'  => $userid,
                'admin_id' => $this->fans['uid'],
                'money'    => $bgmoney,
                'type'     => $cztype == 3 ? 1 : 2,
                'remark'   => $arrayField['contentstr'],
                'date'     => $date,
                'ctime'    => time(),
            );
            $res3 = M('RedoOrder')->add($arrayRedo);
            if ($res1 && $res12 && $res2 && $res22 && $res3) {
                M()->commit();
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
            } else {
                M()->rollback();
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败'.$res1.'-'.$res12.'-'.$res2.'-'.$res22.'-'.$res3]);
            }
        } else {
            $userid = I("request.uid");
            $date   = I("request.date");
            $info   = M("Member")->where(["id" => $userid])->find();
            $this->assign('mobile', $user['mobile']);
            $this->assign('info', $info);
            $this->assign('date', $date);
            $this->display();
        }
    }







  
}
