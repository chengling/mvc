<?php
class Factory
{

	protected static $ins = null;

	public static function getInstance()
	{
		if (! self::$ins instanceof self)
		{
			self::$ins = new self();
		}
		return self::$ins;
	}

	protected function set_router($uri)
	{
		$partern = "/\/([a-z]+)\/([a-z0-9]+)?/i";
		preg_match($partern, $uri, $out);
		$controller = isset($out[1]) ? $out[1] : 'Index';
		$action = isset($out[2]) ? $out[2] : 'index';
		return array ('controller' => $controller,'action' => $action);
	}

	protected function cache()
	{
		///文件缓存
		if (defined('FILE_CACHE') && FILE_CACHE)
		{
			$cachefilename = __cache__ . md5(implode(',', $_REQUEST));
			if (is_file($cachefilename) && (time() - filemtime($cachefilename) < 60))
			{
				echo unserialize(file_get_contents($cachefilename));
				exit();
			}
			else
			{
				register_shutdown_function(array ('Factory','last')); //程序执行完后执行   
			}
		}
	}

	public function run($url)
	{
		ob_start(); //打开缓冲区   
		$this->cache();
		$arr = self::$ins->set_router($url);
		$ProductType = ucfirst($arr['controller']) . 'Controller';
		if (class_exists($ProductType))
		{
			$class = new ReflectionClass($ProductType);
			if ($class->isAbstract())
			{
				die('不能实例化一个抽象类');
			}
			// 从指定的参数创建一个新的类实例
			$controller = $class->newInstance();
			//先执行before
			$controller->before();
			//也可以如下方式执行   获取方法  -->invoke(实例化的对象) 执行
			$response = $class->getMethod($arr['action'].'Action')->invoke($controller);
			//最后执行after
			$response = $class->getMethod('after')->invoke($controller);
		}
		else
		{
			//throw new Exception('你要找的'.$ProductType.'类'.$arr['action'].'方法不存在', 1);
			die('你要找的' . $ProductType . '类' . $arr['action'] . '方法不存在');
		}
	}

	public static function last() // echo  '脚本执行完了'. PHP_EOL;
	{
		//得到缓冲区的内容并且赋值给$info
		$info = serialize(ob_get_contents());
		$cachefilename = __cache__ . md5(implode(',', $_REQUEST));
		$file = fopen($cachefilename, 'w'); ///打开文件 指定缓存目录 
		fwrite($file, $info); //写入信息到info.txt  
		fclose($file); //关闭文件info.txt   
		//ob_end_clean();
		ob_end_flush();
		flush();
	}
	///加载单个文件
	static public function load($name)
	{
		/////想把搜索到的值全部放到这里 以后就不用循环，array_search好像不太好拼接
		static $vessel = array ();
		$dir = self::totaldir();
		foreach ($dir as $value)
		{
			//加载扩展
			if (is_file($value . $name . '.php'))
			{
				$vessel[] = $value . $name . '.php';
				return include_once $value . $name . '.php';
			}
			//控制器
			if (is_file($value . ucfirst($name) . '.php'))
			{
				$vessel[] = $value . ucfirst($name) . '.php';
				return include_once $value . $name . '.php';
			}
		}
		die('搜索的文件不存在');
	}
	// 注册自动装载机
	static public function autoLoad()
	{
		spl_autoload_register(array (__CLASS__,'load'));
	}
	///yii  好像也是这么干的
	static public function totaldir()
	{
		$dir = array ();
		$dir[] = __ROOT__ . '/system/'; //主控制文件
		$dir[] = __ROOT__ . '/system/Data/Db/'; //主控制文件
		$dir[] = __ROOT__ . '/application/controllers/'; //控制器文件类
		$dir[] = __ROOT__ . '/application/models/'; //配置文件类
		$dir[] = __ROOT__ . '/application/config/'; //配置文件类
		$dir[] = __ROOT__ . '/application/function'; //扩展函数类
		$dir[] = __ROOT__ . '/application/extend/'; //扩展工具类 分页、上传类
		return $dir;
	}
}
     
    