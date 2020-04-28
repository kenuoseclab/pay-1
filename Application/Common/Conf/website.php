<?php 
		return array(
			'WEB_TITLE' => '聚合支付',
			'DOMAIN' => $_SERVER['HTTP_HOST'],
			'MODULE_ALLOW_LIST'   => array('Home','User','Admin','Install', 'Weixin','Pay','Cashier','Agent','Payment','Code'),
			'URL_MODULE_MAP'  => array('admin'=>'admin', 'agent'=>'user', 'user'=>'user', 'code'=>'code'),
			'LOGINNAME' => 'Login',
			'HOUTAINAME' => 'admin',
		);
?>