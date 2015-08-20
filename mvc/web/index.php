<?php
    header('Content-type: text/html; charset=utf-8');
    error_reporting(E_ALL);//E_ALL ^ E_NOTICE  显示除去 E_NOTICE 之外的所有错误信息 
	
    define('__URL__', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
	
    define('__ROOT__', dirname(__DIR__));
    define('__cache__',__ROOT__.'/application/cache/');
    //set_exception_handler(array("Factory","last_fun")); 设置一个用户定义的异常处理函数   
    include_once("../system/core.php"); 
	
    define('FILE_CACHE',false); //开启文件缓存
  
    Factory::autoLoad();  
    //开始执行
    Factory::getInstance()->run($_SERVER['REQUEST_URI']);

















