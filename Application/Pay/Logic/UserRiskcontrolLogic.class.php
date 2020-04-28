<?php

namespace Pay\Logic;

class UserRiskcontrolLogic extends RiskcontrolLogic
{
    protected $m_UserRiskcontrolConfig;

    protected $member_info = false;

    public function __construct($pay_amount = '0.00', $user_id = 0)
    {
        parent::__construct();
        $this->pay_amount              = $pay_amount; //交易金额
        $this->m_UserRiskcontrolConfig = D('UserRiskcontrolConfig');
        $this->m_Member                = M('Member');

        /********************查询用户信息***********************/
        $this->member_info = $this->m_Member
            ->field([
                'id',
                'unit_paying_number',
                'unit_frist_paying_time',
                'unit_paying_amount',
                'paying_money',
                'last_paying_time',
            ])->where(['id' => $user_id])
            ->find();
        if (!$this->member_info) {
            return '无此商户号！';
        }

        /*******************生成基本风控配置********************/
        $this->config_info = $this->m_UserRiskcontrolConfig->findConfigInfo($user_id);
        if ($this->config_info) {
            // $this->config_info = array_merge($this->member_info,$this->config_info);
            $this->config_info['unit_frist_paying_time'] = $this->member_info['unit_frist_paying_time'];
            $this->config_info['unit_paying_number']     = $this->member_info['unit_paying_number'];
            $this->config_info['unit_paying_amount']     = $this->member_info['unit_paying_amount'];
            $this->config_info['last_paying_time']       = $this->member_info['last_paying_time'];
            $this->config_info['paying_money']           = $this->member_info['paying_money'];
        }

    }

    //监测数据
    public function monitoringData()
    {
        if ($this->config_info) {

            //---------------------基本风控规则-----------------
            $base_judge = parent::monitoringData();
            if ($base_judge !== true) {
                return $base_judge;
            }

            //---------------------防控域名--------------------
            $domain_judge = $this->controlDomain();
            if ($domain_judge !== true) {
                return $domain_judge;
            }

            //--------------------判断交易总量-----------------
            $the_total_volume_judge = $this->theTotalVolume(function () {

                //如果是新一天，交易量清零,防止定时任务不执行
                $where = ['id' => $this->member_info['id']];
                $data  = ['paying_money' => 0.00];
                $res   = $this->m_Member->where($where)->save($data);

            });
            if ($the_total_volume_judge !== true) {
                return $the_total_volume_judge;
            }

            //----------------单位时间判断交易操作-----------------
            $unit_timeoperate_judge = $this->unitTimeOperate(function () {
                //如果支付间隔不在单位时间内，将商户风控数据重置
            
                $data = [
                    'unit_paying_number'     => 0,
                    'unit_paying_amount'     => 0,
                    'unit_frist_paying_time' => time(),
                ];
                $where  = ['id' => $this->member_info['id']];
                $reuslt = $this->m_Member->where($where)->save($data);
            });
            if ($unit_timeoperate_judge !== true) {
                return $unit_timeoperate_judge;
            }

        }
        return true;
    }

    //防封域名
    protected function controlDomain()
    {
        $domain = trim($this->config_info['domain']);
        if ($domain) {
            $domain_item = explode("\r\n", $domain);
            $http_item   = parse_url($_SERVER['HTTP_REFERER']);
            $host        = $http_item['host'];
            foreach ($domain_item as $k => $v) {
                if ($host == $v) {
                    return true;
                }
            }
            return '请求域名错误！';
        }
        return true;
    }


}
