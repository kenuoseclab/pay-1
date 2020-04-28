<?php
namespace Org\Util;
class Ztaliwap
{
/**
     * md5 加密
     */
    public static function getSign($data, $merKey)
    {
        try {
            if (null == $data) {
                return "123456";
            }
            // 先干掉sign字段
            $keys = array_keys($data);
            $index = array_search("sign", $keys);
            if ($index !== FALSE) {
                array_splice($data, $index, 1);
            }
            // 对数组排序
            ksort($data);
            // 生成待签名字符串
            $srcData = "";
            foreach ($data as $key => $val) {
                if ($val === null || $val === "") {
                    // 值为空的跳过，不参与加密
                    continue;
                }
                $srcData .= "$key=$val" . "&";
            }
            $srcData = substr($srcData, 0, strlen($srcData) - 1);
            // echo "\n";
            // echo $srcData;
            // echo "\n";
            // 生成签名字符串
            $sign = self::createSign($srcData, $merKey);
            return $sign;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function createSign($data = "", $key = "")
    {
        return md5($data . $key);
    }
  }
?>