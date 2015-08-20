<?php

   class Commom{
	//中文版域名
      public $en_url='http://wn.lyt.cn/app/message.php';
	//英文版域名
	  public $ch_url='http://pc.lyt.cn/app/message.php';
       public static $db;
	   public $cfg_dbhost;
	   public $cfg_dbuser;
	   public $cfg_dbpwd;
	   public $cfg_dbname;
	   public $cfg_db_language;
       public  function __construct(){
		   if(!isset($this->db)){
			//引入配置
			   $dir=$_SERVER['DOCUMENT_ROOT'].'/data/common.inc.php';
			   include ($dir); 
			   $this->cfg_dbhost=$cfg_dbhost;
			   $this->cfg_dbuser=$cfg_dbuser;
			   $this->cfg_dbpwd=$cfg_dbpwd;
			   $this->cfg_dbname=$cfg_dbname;
			   $this->cfg_db_language=$cfg_db_language;
			   
			 //链接  
			  $this->connect( $cfg_dbhost, $cfg_dbuser, $cfg_dbpwd);  
		   }
	       mysql_select_db($cfg_dbname,$this->db);
		   $char="set names $cfg_db_language";
           mysql_query($char);	   
        }
		public function connect($h,$u,$p)
		{
		  $this->db = @mysql_connect($h,$u,$p);
		  return $this->db or 0;
	    }
		
		public function query($sql)
		{
          $rs = mysql_query($sql,$this->db);
           return $rs;
        }
    //获取所有 -array
		public function getall($sql)
		{
				$rs = $this->query($sql);
				$list = array();
				while($row = @mysql_fetch_assoc($rs)) {
					$list[] = $row;
				}
				return $list;
		}
		
		//获取所有 -object
		public function getall_object($sql)
		{
			$rs = $this->query($sql);
			$list = array();
			while($row = @mysql_fetch_object($rs)) {
				$list[] = $row;
			}
			return $list;
		}
	//如果只查询一条	
	    public function getone($sql) 
		{
			$rs = $this->query($sql);
			$row=mysql_fetch_assoc($rs);
			return $row;
        }
		
   //删除
	  public function del($sql)
	  {
         $del = $this->query($sql);
         return $del;
      }
	  
	//添加 修改
    public function autoExecute($table,$arr,$mode='insert',$where = ' where 1 limit 1') 
	{
        if(!is_array($arr)) {
            return false;
        }
        if($mode == 'update') {
            $sql = 'update ' . $table .' set ';
            foreach($arr as $k=>$v) {
                $sql .= $k . "='" . $v ."',";
            }
            $sql = rtrim($sql,',');
            $sql .= $where;
            return $this->query($sql);
        }
        $sql = 'insert into ' . $table . ' (' . implode(',',array_keys($arr)) . ')';
        $sql .= " values ('";
        $sql .= implode("','",array_values($arr));
        $sql .= "')";
		
        return $this->query($sql);
    }
        //输出函数 传进来一个 数组 返回一个包含array('code'=>'1/2', "message"=>"success/fail",'body'=>'传进来的数组')
        function json_put($array=array()){
            $result=array(
                'code'=>'1', "message"=>"success",'body'=>''
            );
            if($array){
                $result['body']=$array;               
            }else{
                $result['code']=0;
                $result['message']='fail';
            }
           echo (json_encode($result));exit;
        }
        //提示函数    传进来一个提示消息，一个状态码 返回一个数组
        function json_msg($msg='',$code='2'){
            $result=array(
                'code'=>'1', "message"=>"success",'body'=>''
            ); 
            $result['code']= $code;
            $result['message']=mb_convert_encoding($msg,'UTF-8',"auto");
            echo (json_encode($result));exit;
        }
		
	   //转换用户mid 
		public function parse_mid($uniqid_mid){
		   $sql="select mid from npcms_member where uniqid_mid='".$uniqid_mid."' limit 1";
		   $arr=$this->getall($sql);
		   if($arr){
		     return $arr[0]['mid'];
		   }else{
		    $this->json_msg('请先登陆','0');
		   }
		}
    //产生一个 随机唯一id
     function getRandOnlyId() {
        //新时间截定义,基于世界未日2012-12-21的时间戳。
        $endtime=1356019200;//2012-12-21时间戳
        $curtime=time();//当前时间戳
        $newtime=$curtime-$endtime;//新时间戳
        //$rand=rand(0,99);//两位随机
        $strtime=rand(0,99).substr(microtime(),2,6);
        $all=$strtime.$newtime;
        $onlyid=base_convert($all,10,36);//把10进制转为36进制的唯一ID
        return $onlyid;
    }
	 public  function postCurl($POST,$url=''){
            $ch=curl_init();
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$POST);
            $output=curl_exec($ch);
			//$rinfo=curl_getinfo($ch);  //查看响应头
			//print_r($rinfo);
            curl_close($ch);
            return $output;
    }
	public function Upload($uploaddir,$ori_img='ori_img')
    {
        $tmp_name =$_FILES[$ori_img]['tmp_name'];  // 文件上传后得临时文件名
        $name     =$_FILES[$ori_img]['name'];     // 被上传文件的名称
        $size     =$_FILES[$ori_img]['size'];    //  被上传文件的大小
        $type     =$_FILES[$ori_img]['type'];   // 被上传文件的类型
        $dir      = $uploaddir.date("Ym");
        @chmod($dir,0777);//赋予权限
        @is_dir($dir) or mkdir($dir,0777);
		 list($usec,$sec) =explode(" ",microtime());
		 $strtime=$sec.substr($usec,2);
         $date = date("YmdHis").$strtime;
		 $filetype=explode('.',$name);
		 $endtype= end($filetype);
        if(move_uploaded_file($tmp_name,$dir."/".$date.".".$endtype)){
		   return $dir."/".$date.".".$endtype;
		}else{
			exit('上传失败');
		}
    }
	public function clear_all_html($str){
		$str = strip_tags($str);
		$str = str_ireplace("&ldquo;","",$str);
		$str = str_ireplace("&rdquo;","",$str);
		$str = str_ireplace("&nbsp;",'',$str);
		$str = str_ireplace("&quot;","",$str);
		$str = str_ireplace("&mdash;","—",$str);
		$str = str_ireplace("&ndash;","–",$str);
		$str = str_ireplace("&lsquo;","",$str);
		$str = str_ireplace("&rsquo;","",$str);
		$str = str_ireplace("&sbquo;","",$str);
		$str = str_ireplace("&bdquo;","",$str);
		$str = str_ireplace("&deg;",'°',$str);
		$str = str_ireplace("&prime;",'′',$str);
		return $str;
	}
}