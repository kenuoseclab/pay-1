<?php
namespace Org\Util\Kxdf;
/**
 * RSA算法类
 * 签名及密文编码：base64字符串/十六进制字符串/二进制字符串流
 * 填充方式: PKCS1Padding（加解密）/NOPadding（解密）
 *
 * Notice:Only accepts a single block. Block size is equal to the RSA key size!
 * 如密钥长度为1024 bit，则加密时数据需小于128字节，加上PKCS1Padding本身的11字节信息，所以明文需小于117字节
 */
class RSA {
        
    /**
	 * 自定义错误处理
	 */
	private function _error($msg) {
		die ( 'RSA Error:' . $msg ); // TODO
	}
	/**
	 * 构造函数
	 *
	 * @param
	 *        	string 公钥文件（验签和加密时传入）
	 * @param
	 *        	string 私钥文件（签名和解密时传入）
	 */
	public function __construct() {


		$this->_getGCPublicKey (  './cert/kx/rsa_public.pem' );
		$this->_getPrivateKey ( './cert/kx/rsa_private_key.pem' );
	}

	/**
	 * 生成签名
	 *
	 * @param
	 *        	string 签名材料
	 * @param
	 *        	string 签名编码（base64/hex/bin）
	 * @return 签名值
	 */
	function sign($data, $padding = OPENSSL_PKCS1_PADDING) {
		$ret = false;
		if (! $this->_checkPadding ( $padding, 'en' ))
			$this->_error ( 'padding error' );
		if (openssl_private_encrypt ( $data, $result, $this->priKey, $padding )) {
			$ret = $this->_encode ( $result, "base64" );
		}
		return $ret;
	}

	/**
	 * 验证签名
	 *
	 * @param
	 *        	string 签名材料
	 * @param
	 *        	string 签名值
	 * @param
	 *        	string 签名编码（base64/hex/bin）
	 * @return bool
	 */
	public function verify($data, $sign, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING) {
		$digest = sha1 ( $data, true );
		$sign = $this->_decode ( $sign, $code );
		if (openssl_public_decrypt ( $sign, $result, $this->gcPubKey, $padding )) {
			if ($digest == $result) {
				return true;
			} else {
				return false;
			}
		}
	}
function getBytes($string) {
    $bytes = array();
    for ($i = 0; $i < strlen($string); $i++) {
        $bytes[] = ord($string[$i]);
    }
    return $bytes;
}
 function toStr($bytes) { 
        $str = ''; 
        foreach($bytes as $ch) { 
            $str .= chr($ch); 
        } 
  
           return $str; 
    } 
	/**
	 * 加密
	 *
	 * @param
	 *        	string 明文
	 * @param
	 *        	string 密文编码（base64/hex/bin）
	 * @param
	 *        	int 填充方式（貌似php有bug，所以目前仅支持OPENSSL_PKCS1_PADDING）
	 * @return string 密文
	 */
	public function encrypt($data, $padding = OPENSSL_PKCS1_PADDING) {
		if (! $this->_checkPadding ( $padding, 'en' ))
			$this->_error ( 'padding error' );
		$len = "117";
		$strArray = str_split ($this->toStr($this->getBytes($data)), $len );
		$ret = false;
		foreach ( $strArray as $cip ) {
			if (openssl_public_encrypt ( $cip, $result, $this->gcPubKey, $padding )) {
				$ret .= $result;
			}
		}
		return $ret;
	}

	/**
	 * 解密
	 *
	 * @param
	 *        	string 密文
	 * @param
	 *        	string 密文编码（base64/hex/bin）
	 * @param
	 *        	int 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING）
	 * @param
	 *        	bool 是否翻转明文（When passing Microsoft CryptoAPI-generated RSA cyphertext, revert the bytes in the block）
	 * @return string 明文
	 */
	public function decrypt($data,  $padding = OPENSSL_PKCS1_PADDING) {
		$ret = false;
		if (! $this->_checkPadding ( $padding, 'de' ))
			$this->_error ( 'padding error' );
		if ($data != false) {
			$len = "128";
			$strArray = str_split ( $data, $len );
			foreach ( $strArray as $cip ) {
				if (openssl_private_decrypt ( $cip, $result, $this->priKey, $padding )) {
					$ret .= $result;
				}
			}
		}
		return $ret;
	}

	/**
	 * 检测填充类型
	 * 加密只支持PKCS1_PADDING
	 * 解密支持PKCS1_PADDING和NO_PADDING
	 *
	 * @param
	 *        	int 填充模式
	 * @param
	 *        	string 加密en/解密de
	 * @return bool
	 */
	private function _checkPadding($padding, $type) {
		if ($type == 'en') {
			switch ($padding) {
				case OPENSSL_PKCS1_PADDING :
					$ret = true;
					break;
				default :
					$ret = false;
			}
		} else {
			switch ($padding) {
				case OPENSSL_PKCS1_PADDING :
				case OPENSSL_NO_PADDING :
					$ret = true;
					break;
				default :
					$ret = false;
			}
		}
		return $ret;
	}
	private function _encode($data, $code) {
		switch (strtolower ( $code )) {
			case 'base64' :
				$data = base64_encode ( $data );
				break;
			case 'hex' :
				$data = bin2hex ( $data );
				break;
			case 'bin' :
			default :
		}
		return $data;
	}
	private function _decode($data, $code) {
		switch (strtolower ( $code )) {
			case 'base64' :
				$data = base64_decode ( $data );
				break;
			case 'hex' :
				$data = $this->_hex2bin ( $data );
				break;
			case 'bin' :
			default :
		}
		return $data;
	}
	private function _getPublicKey($file) {
		$key_content = $this->_readFile ( $file );
		if ($key_content) {
			$this->pubKey = openssl_get_publickey ( $key_content );
		}
	}
        private $gcPubKey='1111';
	private function _getGCPublicKey($file) {
		$key_content = $this->_readFile ( $file );
		if ($key_content) {
			$this->gcPubKey = openssl_get_publickey ( $key_content );
		}
	}
	private function _getPrivateKey($file) {
		$key_content = $this->_readFile ( $file );
		if ($key_content) {
			$this->priKey = openssl_get_privatekey ( $key_content );
		}
	}
	private function _readFile($file) {
		$ret = false;
		if (! file_exists ( $file )) {
			$this->_error ( "The file {$file} is not exists" );
		} else {
			$ret = file_get_contents ( $file );
		}
		return $ret;
	}
	private function _hex2bin($hex = false) {
		$ret = $hex !== false && preg_match ( '/^[0-9a-fA-F]+$/i', $hex ) ? pack ( "H*", $hex ) : false;
		return $ret;
	}
}
