<?php

namespace Pay\Logic;

class RiskcontrolLogic extends \Think\Model
{
    protected $autoCheckFields = false;

    protected $m_UserRiskcontrolConfig;

    protected $pay_amount = 0.00;

    protected $config_info = [];

    public function __construct()
    {

        parent::__construct();

    }

    /***************************************外部可调用接口*****************************************/
    //检验基本风控
    public function monitoringData()
    {
        //判断交易时间
        $trading_time_judge = $this->tradingTime();
        if ($trading_time_judge !== true) {
            return $trading_time_judge;
        }

        //判断交易金额范围
        $scope_of_amount_judge = $this->scopeOfAmount();

        if ($scope_of_amount_judge !== true) {
            return $scope_of_amount_judge;
        }

        return true;
    }

    //设置config_info配置属性
    public function setConfigInfo($config_info = [])
    {
        $this->config_info = $config_info;
    }

    /*****************************************内部调用方法***************************************/

    //验证交易时间
    protected function tradingTime()
    {
        $start_time = $this->config_info['start_time'];
        $end_time   = $this->config_info['end_time'];
        if ($start_time != '0' && $end_time != '0') {
            $hours = date('H');
            if ($hours < $start_time || $hours > $end_time) {
                return '交易时间段[' . $start_time . '点-' . $end_time . '点]';
            }
        }
        return true;
    }

    //验证交易金额范围
    protected function scopeOfAmount()
    {
        $min_money = $this->config_info['min_money'];
        $max_money = $this->config_info['max_money'];
        if ($min_money != 0.00 && $max_money != 0.00) {
            //判断用户交易的金额是否在交易范围
            if ($this->pay_amount < $min_money || $this->pay_amount > $max_money) {
                return '单笔交易金额[' . $min_money . '/' . $max_money . ']';
            }
        } else if ($min_money == 0.00 && $max_money != 0.00) {
            //判断用户交易的金额是否在交易范围
            if ($this->pay_amount > $max_money) {
                return '单笔交易最大金额[' . $max_money . ']';
            }
        } else if ($min_money != 0.00 && $max_money == 0.00) {
            //判断用户交易的金额是否在交易范围
            if ($this->pay_amount < $min_money) {
                return '单笔交易最小金额[' . $min_money . ']';
            }
        }
        return true;
    }

    //交易总量判断
    protected function theTotalVolume(callable $callback)
    {
        if ($this->config_info['all_money'] != 0.00) {

            //当天交易量为零，判断交易量
            $now_date         = date('Ymd');
            $last_paying_time = date('Ymd', $this->config_info['last_paying_time']);
            if ($now_date <= $last_paying_time) {
                //当天实际还没结束，计算当前交易金额，并判断交易总额
                $paying_money = $this->config_info['paying_money'] + $this->pay_amount;

                if ($this->config_info['all_money'] < $paying_money) {
                    return '当天总交易金额超额!';
                }
            } else {
                call_user_func($callback);
            }

        }
        return true;
    }

    //单位时间的操作
    protected function unitTimeOperate(callable $callback)
    {   

        if ($this->config_info['unit_interval']) {

            //计算时间单位
            switch ($this->config_info['time_unit']) {
                case 's': //秒
                    $unit_interval += $this->config_info['unit_interval'];
                    break;
                case 'i': //分
                    $unit_interval += $this->config_info['unit_interval'] * 60;
                    break;
                case 'h': //时
                    $unit_interval += $this->config_info['unit_interval'] * 3600;
                    break;
                case 'd': //天
                    $unit_interval += $this->config_info['unit_interval'] * 86400;
                    break;
            }

            //用现在的时间减去单位第一次交易的时间，求出时间差
            $time_lag = time() - $this->config_info['unit_frist_paying_time'];

            //如果时间差在单位时间内判断请求数量
            if ($time_lag <= $unit_interval) {

                //判断时间间隔内交易次数
                $unit_pay_number = $this->config_info['unit_paying_number'] + 1;
                if ($unit_pay_number > $this->config_info['unit_number'] && $this->config_info['unit_number'] != 0) {
                    return '单位时间最大交易笔数/' . $this->config_info['unit_number'];
                }

                if ($this->config_info['unit_all_money'] != '0.00') {
                    //判断是否第一次交易且交易金额不能大于单位时间交易金额
                    if ($this->config_info['unit_frist_paying_time'] <= 0 && $this->pay_amount > $this->config_info['unit_all_money']) {
                        return '已超过单位时间总交易金额/' . $this->config_info['unit_all_money'];
                    }

                    //判断单位时间的交易金额
                    $unit_paying_amount = $this->config_info['unit_paying_amount'] + $this->pay_amount;
                    if ($unit_paying_amount > $this->config_info['unit_all_money']) {
                        return '单位时间总交易金额/' . $this->config_info['unit_all_money'];
                    }
                }
                //验证单位时间内是否允许相同金额的订单
                if($this->config_info['unit_same_amount'] == '1'){
                    $paytime=time()-$unit_interval;
                    $orderWhere['pay_applydate'] = array('gt',$paytime);
                    $orderWhere['pay_status'] = 0;
                    $orderWhere['pay_amount'] = $this->pay_amount;
                    $orderWhere['account_id'] = $this->config_info['account_id'];
                    $res= M('Order')->where($orderWhere)->find();
                    //var_dump($res);exit;
                    if($res){
                        return '单位时间内有重复金额订单/' . $this->pay_amount;
                    }

                }

            } else {
        
                //如果支付间隔不在单位时间内，将商户风控数据重置
                call_user_func($callback);
            
            }
        }

        return true;
    }

}
