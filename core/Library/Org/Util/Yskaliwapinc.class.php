<?php
header('Content-Type:text/html;charset=utf8');
date_default_timezone_set('Asia/Shanghai');

function get_sign( $data, $key  )
{
	ksort( $data );
	$str = '';
	foreach( $data as $k => $v )
	{
		if( $k != 'sign' && $k != 'remark' )
		{
			$str .= ( $k.'='.$v.'&');
		}
		
	}
	$str .= 'key='.$key;

	return ( md5(  $str ) );
}

function cpost( $url, $post_data )
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($post_data))
	);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	$output = curl_exec($ch);
	curl_close($ch);
	return ($output);
	
}
?>
