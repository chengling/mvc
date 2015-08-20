<?php
abstract class Controller
{

	public $data = array (); //用于接收传过来的内容 

	public static $magic_quotes = NULL;

	public function __construct()
	{
		self::$magic_quotes = (version_compare(PHP_VERSION, '5.4') < 0 and get_magic_quotes_gpc());
		// 清洁所有请求变量
		$_GET = self::sanitize($_GET);
		$_POST = self::sanitize($_POST);
		$_COOKIE = self::sanitize($_COOKIE);
	}

	public function before()
	{
	}

	public function after()
	{
	}

	public static function sanitize($value)
	{
		if (is_array($value) or is_object($value))
		{
			foreach ($value as $key => $val)
			{
				$value[$key] = self::sanitize($val);
			}
		}
		elseif (is_string($value))
		{
			if (self::$magic_quotes === TRUE)
			{
				$value = stripslashes($value);
			}
			if (strpos($value, "\r") !== FALSE)
			{
				$value = str_replace(array ("\r\n","\r"), "\n", $value);
			}
		}
		return $value;
	}

	public function __set($k, $v)
	{
		$this->$k = $v;
	}

	public function __call($method, $arg)
	{
		echo $method . '方法--错误指定页面';
		print_r($arg); //错误指定页面
	}

	public static function __callStatic($method, $arg)
	{
		print_r($arg); //错误指定页面 静态的好像不太可能 先写这儿了
	}

	public function assign($key, $value)
	{
		$this->data[$key] = $value;
	}
	//引入模板
	public function display($template, $ext = '.php')
	{
		$template_path = __ROOT__ . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . $template . $ext;
		if (file_exists($template_path))
		{
			foreach ($this->data as $k => $v)
			{
				$this->__set($k, $v);
			}
			include $template_path;
		}
	}
	//产生一个 随机唯一id
	public function getRandOnlyId()
	{
		//新时间截定义,基于世界未日2012-12-21的时间戳。
		$endtime = 1356019200; //2012-12-21时间戳
		$curtime = time(); //当前时间戳
		$newtime = $curtime - $endtime; //新时间戳
		//$rand=rand(0,99);//两位随机
		$strtime = rand(0, 99) . substr(microtime(), 2, 6);
		$all = $strtime . $newtime;
		$onlyid = base_convert($all, 10, 36); //把10进制转为36进制的唯一ID
		return $onlyid;
	}
	//输出函数 传进来一个 数组 返回一个包含array('code'=>'1/2', "message"=>"success/fail",'body'=>'传进来的数组')
	function json_put($array = array())
	{
		$result = array ('code' => '1',"message" => "success",'body' => '');
		if ($array)
		{
			$result['body'] = $array;
		}
		else
		{
			$result['code'] = 0;
			$result['message'] = 'fail';
		}
		echo (json_encode($result));
		exit();
	}
	//提示函数    传进来一个提示消息，一个状态码 返回一个数组
	function json_msg($msg = '', $code = '2')
	{
		$result = array ('code' => '1',"message" => "success",'body' => '');
		$result['code'] = $code;
		$result['message'] = mb_convert_encoding($msg, 'UTF-8', "auto");
		echo (json_encode($result));
		exit();
	}

	public function model($model = null, $base_path = null)
	{
		static $_cache = array ();
		if (array_key_exists($model, $_cache))
		{
			return $_cache[$model];
		}
		$obj = new $model();
		$_cache[$model] = $obj;
		return $obj;
	}
}