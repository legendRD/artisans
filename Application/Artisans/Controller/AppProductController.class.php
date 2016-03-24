<?php
namespace Artisans\Controller;
class AppProductController extends CommonController {
      
      private $_scrypt_pwd      = '';
      private $_callback_by_url = 'http://localhost/api/deal/index';
      
      //产品列表
      public function productList() {
            $postData	= I('request.');
		$user_id	= $postData['user_id'];
		if(!$user_id) {
			$this->returnJsonData(300);
		}
		
		$where['IsShelves'] = 1 ;
		$where['IsDelete'] = 0 ;
		$where['ProductType'] = 0 ;
		$arr = M()->table("prd_productinfo")->order('CreaterTime desc')->where($where)->field("LogoImgUrl,LogoImgCdnUrl,ProductName,Profit,UseTime,BearService,ServiceNum,Evaluation,ProductId")->select();
		unset($where);
		
		$where['CraftsmanId'] = $user_id ;
		$ids = M()->table("prd_product_craftsman")->where($where)->field("state,ProductId")->select();
		$imgurl = C('TMPL_PARSE_STRING')['__IMG_URL__'] ;
		foreach ($arr as $key => $value) {
		      $arr[$key]['LogoImgUrl'] = $imgurl.$value['LogoImgUrl'];
			$arr[$key]['LogoImgCdnUrl'] = $imgurl.$value['LogoImgCdnUrl'];
			$arr[$key]['state'] = '4';
			$arr[$key]['sort'] = 3; 
			$arr[$key]['status'] = '无资质';          //4 无资质
			foreach ($ids as $v) {
			      $arr[$key]['state'] = $v['state'];
				if($arr[$key]['state'] == 1) {      //1 可以接单
					$arr[$key]['status'] = '可以接单';
					$arr[$key]['sort'] = 1; 
				}else if($arr[$key]['state'] == 0) {     //0 认证中
					$arr[$key]['status'] = '认证中';
					$arr[$key]['sort'] = 2; 
				}else if($arr[$key]['state'] == 2) {      //2 被驳回
					$arr[$key]['status'] = '被驳回';
					$arr[$key]['sort'] = 4; 
				}else if($arr[$key]['state'] == 3) {      //3 暂停接单
					$arr[$key]['status'] = '暂停接单';
					$arr[$key]['sort'] = 5; 
				}
				if(count($arr)>1) {
				      foreach($arr as $key=>$val) {
				            $temp_arr[] = $val['sort'];
				            $sort_type  = SORT_ASC;
				            array_multisort($temp_arr, $sort_type, $arr);
				      }
				      $temp_arr = array();
				}
				
				foreach($arr as $key=>$value) {
				      unset($arr[$key]['sort']);
				}
				
				if($arr) {
				      $this->returnJsonData(200,$arr);
				}else{
				      $this->returnJsonData(200,array());
				}
			}
		}
      }
      
      //产品详情
      public function productShow() {
                  $postData	 = I('request.');
			$ProductId	 = $postData['ProductId'];
			$CraftsmanId = $postData['CraftsmanId'];
			if(!$ProductId || !$CraftsmanId) {
				$this->returnJsonData(300);
			}
			$where['ProductId'] = $ProductId;
			$imgurl = C('TMPL_PARSE_STRING')['__IMG_URL__'] ;
			$arr = M()->table("prd_productinfo")->where($where)->field("ProductId,ProductName,DetailImgUrl,DetailImgCdnUrl,Profit,ProductDescription,RequireDescription,CostDescription")->find();
			$where['CraftsmanId'] = $CraftsmanId;
			$ids = M()->table("prd_product_craftsman")->where($where)->field("State")->find();
			$arr['DetailImgUrl'] = $imgurl.$arr['DetailImgUrl'];
			$arr['DetailImgCdnUrl'] = $imgurl.$arr['DetailImgCdnUrl'];
			$arr['State'] = $ids['State'];
			$this->returnJsonData(200,$arr);
      }
      
      //XXX与产品之间的状态操作
      public function updateState() {
                  $postData = I('request.');
			$CraftsmanId = $postData['CraftsmanId'];
			$ProductId = $postData['ProductId'];
			$State = $postData['State'];
			$Reason = $postData['Reason'];
			if(!($ProductId || $State || $CraftsmanId)){
				$this->returnJsonData(300);
			}else{
				if($State == '3'){
					if(!$Reason){
						$this->returnJsonData(300);
					}
				}
			}
			
			$where['CraftsmanId'] = $CraftsmanId;
			$where['ProductId'] = $ProductId;
			
			if($State == '1') {
			      //申请接单
			      $flog = M('prd_product_craftsman')->where($where)->find();
			      if($flog && $flog['State']!=2) {
			            $this->returnJsonData(300);
			      }
			      $data['CreaterTime'] = date('Y-m-d H:i:s',time());
				$data['State'] = '0';
				if($flog['State'] == 2) {
				      $where['CraftsmanId'] = $CraftsmanId;
					$where['ProductId']   = $ProductId;
					$id	= M('prd_product_craftsman')->where($where)->save($data);
				}else{
				      $data['CraftsmanId'] = $CraftsmanId;
					$data['ProductId'] = $ProductId;
					$id	= M('prd_product_craftsman')->add($data);
				}
				$this->returnJsonData(200);
			}elseif($State == '3') {
			      //暂停接单
				$data['CreaterTime'] = date('Y-m-d H:i:s',time());
				$data['State'] = $State;
				$id	= M('prd_product_craftsman')->where($where)->save($data);
				$data['CraftsmanId'] = $CraftsmanId;
				$data['ProductId'] = $ProductId;
				$data['Reason'] = $Reason;
				$ids	= M('crt_productoffreason')->add($data);
				$this->returnJsonData(200);
			}elseif($State == '2') {
			      //开始接单
				$data['CreaterTime'] = date('Y-m-d H:i:s',time());
				$data['State'] = '1';
				$id	= M('prd_product_craftsman')->where($where)->save($data);
				$this->returnJsonData(200);
			}
      }
      
      public function capacityShow() {
		$postData = I('request.');
		$user_id  = $postData['user_id'];
		if(!$user_id) {
			$this->returnJsonData(300);
		}
		$beginTime 		   	  = date('Y-m-d', time());
		$endTime   		   	  = date('Y-m-d', strtotime("+13 day"));
		$where['Capacity'] 	  = array(array('egt', $beginTime), array('elt', $endTime));
		$where['CraftsmanId'] = $user_id;
		//查询14天的XX
		$capacity  			  = M('crt_capacity')->where($where)->field("Capcaity, TimeId")->select();
		unset($where);
		$where['IsDelete']	  = 0;
		//所有的可预约时间点
		$servicetime 	      = M('prd_servicetime')->where($where)->field("TimeId, StartTime")->select();
		$data = array();
		//循环遍历14天的XX
		for($i=0;$i<14;$i++) {
			$data[$i]['date'] = date('Y-m-d', strtotime("+".$i." day"));
			foreach($capacity as $value) {
				//判断当天是否存在XX
				if(date('Y-m-d', strtotime("+".$i." day")) == $value['Capcaity']) {
						foreach ($servicetime as $v) {
							//获取当天的具体时间点
							if($value['TimeId'] == $v['TimeId']) {
								$time .= $v['StartTime'].',';
							}
						}
				}
			}
			$data[$i]['content'] = array();
			//将时间点连城时间段
			if($time) {
				$newTime = substr($time, 0, strlen($time)-1);
				$arr     = explode(',', $newTime);
				sort($arr);
				$p = 0;
				foreach ($arr as $k => $v) {
					if($arr[$k+1] == $arr[$k] + 1) {
						$num .= $arr[$k].','.$arr[$k+1].',';
					}else{
						if($num) {
							$newNum  = substr($num, 0, strlen($num)-1);
							$content = explode(',', $newNum);
							$len 	 = cont($content);
							$begin   = $content[0];
							$end     = $content[$len-1]+1;

							$data[$i]['content'][$p]['service'] = '';
							$data[$i]['content'][$p++]['time']  = $begin.'-'.$end.':00';
							$num     = '';
						}
					}
				}
				unset($arr);
				$time = '';
			}
		}
	}
	$array = array('user_id'=>$user_id, 'data'=>$data);
	$this->returnJsonData(200, $array);            
      }
      
      public function capacityByCityProIdDate() {
            
      }
      
      public function capacityByuserIdDate() {
            
      }
      
      //获取时间点
      public function getTime() {
            
      }
      
      //编辑XX
      public function updateCapacity() {
            
      }
      
      //保存XX设置
      public function setCapacity() {
            
      }
      
      //我的订单列表
      public function orderList() {
            
      }
      
      //订单详情页
      public function orderShow() {
            
      }
      
      //更改订单状态
      public function updateOrderState() {
            
      }
      
      /**
	* 回调调取XX接口
	* @access   private
	* @param	int	order_id 内部单号
	* @param	int	status   服务流程id
	* @param	float	lng	   经度
	* @param	float	lat      纬度
	*/
	private function _callback_by($param) {
	      
	}
	
	//传送客户端手机号
	public function getPhone() {
	      
	}
	
	//返回数据
	public function returnJsonData($num, $date = array(), $msg = '') {
	      switch($num) {
	            case 300:
	                  $hash['status'] = 300;
	                  $hash['msg']    = 'noparam';
	                  break;
	            case 500:
				$hash['status'] = 500;
				$hash['msg'] = 'fail';
				break;
			case 200:
			      $hash['status'] = 200;
				$hash['msg'] = 'success';
				$hash['data'] = $data;
				break;
			case 1008:
				$hash['status'] = 1008;
				$hash['msg'] = '存在订单，不能修改';
				$hash['data'] = $data;
				break;
	      }
	      $this->_echoInfo($hash,$_GET['parse']);	//显示信息
		echo json_encode($hash);
		exit();
	}
	
	private function _echoInfo($info, $parse='') {
	      if($parse == 'echo_info'){
			pp($info);
		}
	}
	
	//获取订单信息接口
	public function getOrderInfo() {
	      
	}
	
	//打电话记录日志接口
	public function CallPhoneLog() {
	      
	}
	
	//获取订单流程
	poublic function getOrderProcedure() {
	      
	}
	
	/**
	* 更新订单流程
	* @access   public
	* @param	string  OrderId       订单ID
	* @param	int	  CraftsmanId   XXXid
	* @param	float	  lng	          经度
	* @param	float	  lat           纬度
	*/
	public function UdateOrderProcedure() {
	      
	}
	
	public function getCompleteOrderInfo() {
	      
	}
	
	
}
