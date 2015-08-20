<?php 
return  array (
	           'default' =>array(
                           'dsn'=>'mysql:dbname=banjia;host=127.0.0.1',
                           'user'=>'root',
                           'password'=>'root',
                            'charset'=>'utf8',
                            'persistent'=>false  //持久性链接
		       ),
			  'alternate' => array(
                           'host'=>'127.0.0.1',
                           'port'=>'3306',
                           'user'=>'root',
                           'passwd'=>'root',
                            'charset'=>'utf8',
                            'dbname'=>'banjia', 
                            'persistent'=>false  //持久性链接
		      )
          );
		  
		 
