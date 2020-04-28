<?php

namespace Admin\Controller;

use Think\Model;
use Vendor\HttpDownload;
use Vendor\Zip;

class UpdateController extends BaseController
{
    protected $server;
    protected $key;
    protected $domain;
    public function __construct()
    {
        parent::__construct();
        $this->server = C('UPDATE_URL');
        $config = M('Websiteconfig')->find();
        $this->key = $config['serverkey'];
        $this->domain = substr($_SERVER['HTTP_HOST'],strpos($_SERVER['HTTP_HOST'],'.')+1);
    }
    public function update()
    {

        $host =
        $url = $this->server.'?a=check&v='.C('SOFT_VERSION').'&k='.$this->key.'&d='.$this->domain.'&time='
            .time();
        $version = file_get_contents($url);
        $html = '';
        if($version>C('SOFT_VERSION')){
            $html = '检查到新版本 '.$version.'，<a  href="javascript:;" onclick="update();">立即升级</a>';
        }else{
            $html = "当前已经是最新版本！";
        }
        $this->assign('html',$html);
        $this->assign('nav', array('setting', 'update', ''));//导航
        $this->display();
    }

    public function updating()
    {
        set_time_limit(0);
        $url = $this->server.'?a=update&v='.C('SOFT_VERSION').'&k='.$this->key.'&d='.$this->domain.'&time='.time();
        $filename = file_get_contents($url);
        if ($filename) {
            if (!class_exists('ZipArchive')){
                $this->ajaxReturn(['message'=>'您的服务器不支持php zip扩展，请配置好此扩展再来升级.']);
            }
            if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/Application')){
                $this->ajaxReturn(['message'=>'Application，设置好再升级']);
            }
            if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/Public')){
                $this->ajaxReturn(['message'=>'Public，设置好再升级']);
            }
            //升级包下载
            $filepath = $this->server.'?a=down&f='.$filename.'&v='.C('SOFT_VERSION').'&k='.$this->key.'&d='
            .$this->domain.'&time='.time();
            $filename = 'update_'.time().'.zip';
            $locationZipPath= "./Runtime/update/";
            //$locationZipPath=iconv('utf-8', 'gbk', $locationZipPath);
            if(!is_dir($locationZipPath)){
                mkdir($locationZipPath);
            }
            $this->get_file($filepath,$filename,$locationZipPath);
            $cacheUpdateDirName = './Runtime/update/caches_upgrade/';
            if(!is_dir($cacheUpdateDirName)) {
                mkdir($cacheUpdateDirName);
            }
            $zip = new \ZipArchive();
            $rs = $zip->open(RUNTIME_PATH.'update/'.$filename);
            if($rs !== TRUE) {
                $this->ajaxReturn(array('message' =>'解压失败_2! Error Code:'. $rs.'!'));
            }
            $zip->extractTo($cacheUpdateDirName);
            $zip->close();
            $this->recurse_copy($cacheUpdateDirName,getcwd());
            //数据库升级
            $sqlstatus = true;
            if (file_exists($cacheUpdateDirName.'/update.sql')) {
                $sql = file_get_contents($cacheUpdateDirName.'/update.sql');
                $sqldata = explode(";",$sql);
                $prefix = C('DB_PREFIX');
                $Model = D('Updatelog');
                foreach ($sqldata as $item){
                    if($item){
                        $sqlstatus = $Model->run($item);
                    }
                }
            }
            if (!$sqlstatus) {
                $this->ajaxReturn(array('message' => '数据库升级失败！请手动导入update.sql文件执行数据库升级！'));
            }
            //删除升级包
            $this->deletedir($locationZipPath);
            @unlink(getcwd().'/update.sql');
            //@unlink($locationZipPath);
            $this->ajaxReturn(['message'=>'恭喜，升级成功！']);
        } else {
            exit('2');//参数错误
        }
    }

    private function getRoutefile($url,$recu=0){
        if (!$url){
            exit('空的url请求'.$recu);
        }
        if (function_exists('curl_init')){
            $ch = curl_init();
            $header = array('Accept-Charset: utf-8');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  //优化
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            $temp = curl_exec($ch);
            $headers = curl_getinfo($ch);
            $haderUrl = '';
            if($headers['http_code'] == 302){
                $haderUrl=$headers['redirect_url'];
                if (!$haderUrl){
                    $haderUrl=$headers['url'];
                }
                if (!$haderUrl){
                    echo 'header有空请求，请查看<br>';
                    var_export($headers);
                    exit();
                }
                $haderUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                return self::getRoutefile($haderUrl,1);
            }
            $errorno=curl_errno($ch);
            curl_close($ch);
            if ($errorno) {
                if ($errorno==3){
                    echo '请求地址是：'.$url.',或者'.$haderUrl.'<br>';
                }
                exit(json_encode(array('message' => 'curl发生错误：错误代码'.$errorno.'，如果错误代码是6，您的服务器可能无法连接我们升级服务器'),320));
            }else {
                return $temp;
            }
        }else {
            $str=file_get_contents($url);
            return $str;
        }
    }

    private function recurse_copy($src,$dst) {  // 原目录，复制到的目录
        $now=time();
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    if (file_exists($dst . DIRECTORY_SEPARATOR . $file)){
                        if (!is_writeable($dst . DIRECTORY_SEPARATOR . $file)){
                            exit(json_encode(['message' => $dst . DIRECTORY_SEPARATOR . $file.'不可写'],320));
                        }
                        @unlink($dst . DIRECTORY_SEPARATOR . $file);
                    }
                    if (file_exists($dst . DIRECTORY_SEPARATOR . $file)){
                        @unlink($dst . DIRECTORY_SEPARATOR . $file);
                    }
                    $copyrt=copy($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file);
                    if (!$copyrt){
                        exit(json_encode(['message' =>  'copy '.$dst . DIRECTORY_SEPARATOR . $file.' failed<br>'],320));
                    }
                }
            }
        }
        closedir($dir);
    }
    private function deletedir($dirname){
        $result = false;
        if(! is_dir($dirname)){
            echo " $dirname is not a dir!";
            exit(0);
        }
        $handle = opendir($dirname); //打开目录
        while(($file = readdir($handle)) !== false) {
            if($file != '.' && $file != '..'){ //排除"."和"."
                $dir = $dirname.DIRECTORY_SEPARATOR.$file;
                //$dir是目录时递归调用deletedir,是文件则直接删除
                is_dir($dir) ? self::deletedir($dir) : unlink($dir);
            }
        }
        closedir($handle);
        $result = rmdir($dirname) ? true : false;
        return $result;
    }

    private function get_file($url,$name,$folder = './')
    {
        set_time_limit((24 * 60) * 60);
        // 设置超时时间
        $destination_folder = $folder . '/';
        // 文件下载保存目录，默认为当前文件目录
        if (!is_dir($destination_folder)) {
            // 判断目录是否存在
            $this->mkdirs($destination_folder);
        }
        $newfname = $destination_folder.$name;
        // 取得文件的名称
        $file = fopen($url, 'rb');
        // 远程下载文件，二进制模式
        if ($file) {
            // 如果下载成功
            $newf = fopen($newfname, 'wb');
            // 远在文件文件
            if ($newf) {
                // 如果文件保存成功
                while (!feof($file)) {
                    // 判断附件写入是否完整
                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                }
            }
        }
        if ($file) {
            fclose($file);
        }
        if ($newf) {
            fclose($newf);
        }
        return true;
    }

    private function mkdirs($path, $mode = '0777')
    {
        if (!is_dir($path)) {
            // 判断目录是否存在
            self::mkdirs(dirname($path), $mode);
            // 循环建立目录
            mkdir($path, $mode);
        }
        return true;
    }
}