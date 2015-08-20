<?php
class DB extends PDO
{

	protected static $_dbh = null; //静态属性,所有数据库实例共用,避免重复连接数据库

	protected $_dbType = 'mysql';

	protected $_pconnect = true; //是否使用长连接

	protected $_host = 'localhost';

	protected $_port = 3306;

	protected $_user = 'root';

	protected $_pass = '123456';

	protected $_dbName = 'bool'; //数据库名

	protected $_sql = false; //最后一条sql语句

	protected $_where = '';

	protected $_order = '';

	protected $_limit = '';

	protected $_field = '*';

	protected $_clear = 0; //状态，0表示查询条件干净，1表示查询条件污染

	protected $_trans = 0; //事务指令数 

	/**
			 * 初始化类
			 * @param array $conf 数据库配置
			 */
	public function __construct()
	{
		class_exists('PDO') or die("PDO: class not exists.");
		$ln = Factory::load('database');
		$conf = $ln['alternate'];
		$this->_host = $conf['host'];
		$this->_port = $conf['port'];
		$this->_user = $conf['user'];
		$this->_pass = $conf['passwd'];
		$this->_dbName = $conf['dbname'];
		$this->_pconnect = $conf['persistent'];
		//连接数据库
		if (is_null(self::$_dbh))
		{
			$this->_connect();
		}
	}

	/**
			 * 连接数据库的方法
			 */
	protected function _connect()
	{
		$dsn = $this->_dbType . ':host=' . $this->_host . ';port=' . $this->_port . ';dbname=' . $this->_dbName;
		$options = $this->_pconnect ? array (PDO::ATTR_PERSISTENT => true) : array ();
		try
		{
			$dbh = new PDO($dsn, $this->_user, $this->_pass, $options);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //设置如果sql语句执行错误则抛出异常，事务会自动回滚
			$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //禁用prepared statements的仿真效果(防SQL注入)
		}
		catch (PDOException $e)
		{
			die('Connection failed: ' . $e->getMessage());
		}
		$dbh->exec('SET NAMES utf8');
		self::$_dbh = $dbh;
	}

	public function table($tablename)
	{
		$this->tbName = $tablename;
		return $this;
	}

	/**
		 * 设置排序
		 * @param mixed $option 排序条件数组 例:array('sort'=>'desc')
		 * @return $this
		 */
	public function order($option)
	{
		if ($this->_clear > 0) $this->_clear();
		$this->_order = ' order by ';
		if (is_string($option))
		{
			$this->_order .= $option;
		}
		elseif (is_array($option))
		{
			foreach ($option as $k => $v)
			{
				$order = $this->_addChar($k) . ' ' . $v;
				$this->_order .= isset($mark) ? ',' . $order : $order;
				$mark = 1;
			}
		}
		return $this;
	}

	/**
		 * 设置查询行数及页数
		 * @param int $page pageSize不为空时为页数，否则为行数
		 * @param int $pageSize 为空则函数设定取出行数，不为空则设定取出行数及页数
		 * @return $this
		 */
	public function limit($page, $pageSize = null)
	{
		if ($this->_clear > 0) $this->_clear();
		if ($pageSize === null)
		{
			$this->_limit = "limit " . $page;
		}
		else
		{
			$pageval = intval(($page - 1) * $pageSize);
			$this->_limit = "limit " . $pageval . "," . $pageSize;
		}
		return $this;
	}

	/**
		 * 设置查询字段
		 * @param mixed $field 字段数组
		 * @return $this
		 */
	public function field($field)
	{
		if ($this->_clear > 0) $this->_clear();
		if (is_string($field))
		{
			$field = explode(',', $field);
		}
		$nField = array_map(array ($this,'_addChar'), $field);
		$this->_field = implode(',', $nField);
		return $this;
	}

	/**
			 * @param mixed $option 组合条件的二维数组，例：$option['field1'] = array(1,'=>','or')
			 * @return $this
			 */
	public function where($option)
	{
		if ($this->_clear > 0) $this->_clear();
		$this->_where = ' where ';
		$logic = 'and';
		if (is_string($option))
		{
			$this->_where .= $option;
		}
		elseif (is_array($option))
		{
			foreach ($option as $k => $v)
			{
				if (is_array($v))
				{
					$relative = isset($v[1]) ? $v[1] : '=';
					$logic = isset($v[2]) ? $v[2] : 'and';
					$condition = ' (' . $this->_addChar($k) . ' ' . $relative . ' ' . $v[0] . ') ';
				}
				else
				{
					$logic = 'and';
					$condition = ' (' . $this->_addChar($k) . '=' . $v . ') ';
				}
				$this->_where .= isset($mark) ? $logic . $condition : $condition;
				$mark = 1;
			}
		}
		return $this;
	}

	/**
     * 查询函数
     * @param string $tbName 操作的数据表名
     * @return array 结果集
     */
	public function select($tbName = null)
	{
		if (! $tbName) $tbName = $this->tbName;
		$sql = "select " . trim($this->_field) . " from " . $tbName . " " . trim($this->_where) . " " . trim($this->_order) . " " . trim($this->_limit);
		$this->_clear = 1;
		$this->_clear();
		$pdostmt = self::$_dbh->prepare($sql); //prepare或者query 返回一个PDOStatement
		$pdostmt->execute();
		return $result = $pdostmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
			 * 清理标记函数
			 */
	protected function _clear()
	{
		$this->_where = '';
		$this->_order = '';
		$this->_limit = '';
		$this->_field = '*';
		$this->_clear = 0;
	}

	/** 
			* 字段和表名添加 `符号
			*/
	protected function _addChar($value)
	{
		if ('*' == $value || false !== strpos($value, '(') || false !== strpos($value, '.') || false !== strpos($value, '`'))
		{
			//如果包含* 或者 使用了sql方法 则不作处理 
		}
		elseif (false === strpos($value, '`'))
		{
			$value = '`' . trim($value) . '`';
		}
		return $value;
	}
	/////找出某张表下的所有字段
	protected function _tbFields($tbName)
	{
		$sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME="' . $tbName . '" AND TABLE_SCHEMA="' . $this->_dbName . '"';
		$stmt = self::$_dbh->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$ret = array ();
		foreach ($result as $key => $value)
		{
			$ret[$value['COLUMN_NAME']] = 1;
		}
		return $ret;
	}

	/** 
			* 过滤并格式化数据表字段
			* @param string $tbName 数据表名 
			* @param array $data POST提交数据 
			* @return array $newdata 
			*/
	protected function _dataFormat($data, $tbName = null)
	{
		if (! is_array($data)) return array ();
		if ($tbName === null) $tbName = $this->tbName;
		$table_column = $this->_tbFields($tbName); /////找出某张表下的所有字段
		$ret = array ();
		foreach ($data as $key => $val)
		{
			if (! is_scalar($val)) continue; //值不是标量则跳过
			if (array_key_exists($key, $table_column))
			{
				//字段和表名添加 `符号
				$key = $this->_addChar($key);
				if (is_int($val))
				{
					$val = intval($val);
				}
				elseif (is_float($val))
				{
					$val = floatval($val);
				}
				elseif (preg_match('/^\(\w*(\+|\-|\*|\/)?\w*\)$/i', $val))
				{
					// 支持在字段的值里面直接使用其它字段 ,例如 (score+1) (name) 必须包含括号
					$val = $val;
				}
				elseif (is_string($val))
				{
					$val = '"' . addslashes($val) . '"';
				}
				$ret[$key] = $val;
			}
		}
		return $ret;
	}

	/**
			 * 更新函数
			 * @param string $tbName 操作的数据表名
			 * @param array $data 参数数组
			 * @return int 受影响的行数
			 */
	public function update(array $data)
	{
		//安全考虑,阻止全表更新
		if (! trim($this->_where)) return false;
		$data = $this->_dataFormat($data);
		$valArr = '';
		foreach ($data as $k => $v)
		{
			$valArr[] = $k . '=' . $v;
		}
		$valStr = implode(',', $valArr);
		$sql = "update " . trim($this->tbName) . " set " . trim($valStr) . " " . trim($this->_where);
		$stmt = self::$_dbh->prepare($sql);
		$stmt->execute();
		return $stmt->rowCount();
	}
	//返回新增的id号  (必须是自增长id)
	public function insert($data)
	{
		$datasql = $this->_dataFormat($data);
		$sql = "insert into " . $this->tbName . "(" . implode(',', array_keys($datasql)) . ") values(" . implode(',', array_values($datasql)) . ")";
		$stmt = self::$_dbh->prepare($sql);
		$stmt->execute();
		return self::$_dbh->lastinsertid();
	}

	/**
			 * 删除方法
			 * @param string $tbName 操作的数据表名
			 * @return int 受影响的行数
			 */
	public function delete($tbName = null)
	{
		if ($tbName === null) $tbName = $this->tbName;
		//安全考虑,阻止全表删除
		if (! trim($this->_where)) return false;
		$sql = "delete from " . $tbName . " " . $this->_where;
		$this->_clear = 1;
		$this->_clear();
		return self::$_dbh->exec($sql);
	}

	/** 
			* 执行sql语句，自动判断进行查询或者执行操作 
			* @param string $sql SQL指令 
			* @return mixed 
			*/
	public function query($sql = '')
	{
		$queryIps = 'INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|SELECT .* INTO|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK';
		if (preg_match('/^\s*"?(' . $queryIps . ')\s+/i', $sql))
		{
			return self::$_dbh->exec($sql);
		}
		else
		{
			//查询操作
			$pdostmt = self::$_dbh->prepare($sql); //prepare或者query 返回一个PDOStatement
			$pdostmt->execute();
			return $result = $pdostmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}

	/** 单条查询操作
			参数1说明：
			PDO::FETCH_BOTH        也是默认的，两者都有（索引，关联）
			PDO::FETCH_ASSOC      关联数组
			PDO::FETCH_NUM        索引
			PDO::FETCH_OBJ        对象
			PDO::FETCH_LAZY       对象 会附带queryString查询SQL语句
			PDO::FETCH_BOUND      如果设置了bindColumn，则使用该参数
			*/
	public function fetch($sql = null, $fetch_type = PDO::FETCH_ASSOC)
	{
		$pdostmt = self::$_dbh->prepare($sql); //prepare或者query 返回一个PDOStatement
		$pdostmt->execute();
		return $result = $pdostmt->fetch($fetch_type);
	}

	/**
			参数1说明：
			PDO::FETCH_BOTH        也是默认的，两者都有（索引，关联）
			PDO::FETCH_ASSOC       关联数组
			PDO::FETCH_NUM         索引
			PDO::FETCH_OBJ         对象
			PDO::FETCH_COLUMN      指定列 参数2可以指定要获取的列
			PDO::FETCH_CLASS       指定自己定义的类
			PDO::FETCH_FUNC        自定义类 处理返回的数据
			PDO_FETCH_BOUND        如果你需要设置bindColumn，则使用该参数
			参数2说明：
			给定要处理这个结果的类或函数
			*/
	public function fetchAll($sql = null, $fetch_type = PDO::FETCH_ASSOC, $handle = '')
	{
		$pdostmt = self::$_dbh->prepare($sql); //prepare或者query 返回一个PDOStatement
		$pdostmt->execute();
		if (empty($handle))
		{
			return $pdostmt->fetchAll($fetch_type);
		}
		return $pdostmt->fetchAll($fetch_type, $handle);
	}

	/**
				* 启动事务 
				* @return void 
				*/
	public function startTrans()
	{
		//数据rollback 支持 
		if ($this->_trans == 0) self::$_dbh->beginTransaction();
		$this->_trans++;
		return;
	}

	/** 
				* 用于非自动提交状态下面的查询提交 
				* @return boolen 
				*/
	public function commit()
	{
		$result = true;
		if ($this->_trans > 0)
		{
			$result = self::$_dbh->commit();
			$this->_trans = 0;
		}
		return $result;
	}

	/** 
				* 事务回滚 
				* @return boolen 
				*/
	public function rollback()
	{
		$result = true;
		if ($this->_trans > 0)
		{
			$result = self::$_dbh->rollback();
			$this->_trans = 0;
		}
		return $result;
	}

	/**
				* 关闭连接
				* PHP 在脚本结束时会自动关闭连接。
				*/
	public function close()
	{
		if (! is_null(self::$_dbh)) self::$_dbh = null;
	}
}	  