<?php

/*
 * 此文件用于验证短信API接口
 * 请确保文件为utf-8编码，并替换相应参数为您自己的信息后执行
 * 建议执行前执行EnvTest.php验证PHP环境
 *
 * 2017/11/19
 */

require_once 'SmsApi.php';

use Aliyun\DySDKLite\Sms\SmsApi;

// 调用示例：
set_time_limit(0);
header('Content-Type: text/plain; charset=utf-8');  // 仅用于输出清晰，非必需

$sms = new SmsApi("LTAIs03iFkgX5lK9", "0iECLLovRRNVHoZiuWP5yVCSpByHu6"); // 请参阅 https://ak-console.aliyun.com/ 获取AK信息

$response = $sms->sendSms(
    "聚合软件", // 短信签名
    "SMS_111795375", // 短信模板编号
    "18318636051", // 短信接收者
    Array (  // 短信模板中字段的值
        "code"=>"1234",
        // "product"=>"dsd"
    ),
    "123"   // 流水号,选填
);
echo "发送短信(sendSms)接口返回的结果:\n";
print_r($response);

// sleep(2);

// $response = $sms->queryDetails(
//     "12345678901",  // 手机号码
//     "20170718", // 发送时间
//     10, // 分页大小
//     1 // 当前页码
//     // "abcd" // bizId 短信发送流水号，选填
// );
// echo "查询短信发送情况(queryDetails)接口返回的结果:\n";
// print_r($response);