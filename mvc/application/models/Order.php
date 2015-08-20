<?php 
    //可以每一张表一个类 统一继承DB类。。。。。
    class Order extends DB{
	 
	    public function select_mem($post)
             {  
			 $sql = "select id from angel_customer where email='".$post['txtEmail']."' limit 1";
			 $res = $this->query($sql);  
                         
			 if(empty($res)) {
			 //插入
				$data = array(
					'lastname'=>$post['txtLastName'],
					'firstname'=>$post['txtFirstName'],
					'phone'=>$post['txtPhone'],
					'mobile'=>$post['txtCellPhone'],
					'email'=>$post['txtEmail'],
					'password'=>md5('123456') 
					);
					
				   $status= $this->insert('angel_customer',$data);
				   if($status>0){
					  $sql = "select max(`id`) from angel_customer";
					  $res = $this->query($sql); 
					   return $res;
				   }
			 } 
			 return $res;
		}
	
	
            public function getOrderCommonInfo($condition = array(), $field = '*') {
                return $this->table('order_common')->where($condition)->find();
            }   

                
    }