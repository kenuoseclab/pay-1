<?php 

function autoload($className)
{
	$filePath = __DIR__ . '/src/' . str_replace('\\', '/', $className) . '.php';
	if(file_exists($filePath)){
		include $filePath;
	}
}

spl_autoload_register('autoload');

 ?>