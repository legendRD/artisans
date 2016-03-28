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
            	$postData = I('request.');
         	$CityId = $postData['city_id'];
		$ProductId = $postData['product_id'];
		$Capacity = $postData['date'];
		if(!$CityId || !$ProductId || !$Capacity) {
			$this->returnJsonData(300);
		}
		//根据产品ID查看拥有此产品的XXX
		$data = M('prd_product_craftsman')->where(array('ProductId'=>$ProductId))->field("CraftsmanId")->select();
		$craftsman = array();
		foreach ($data as $key => $value) {
			$craftsman[$key] = $value['CraftsmanId'];
		}
		unset($where);
		$where['CraftsmanId'] = array('in',$craftsman);
		$where['Capacity'] = $Capacity;
		$capacity = M('crt_capacity')->where($where)->field("CapacityId,Capacity,TimeId")->select();
		unset($where);
		$where['IsDelete'] = 0;
		//所有的可约时间点
		$servicetime = M('prd_servicetime')->where($where)->order('StartTime')->field("TimeId,StartTime")->select();
		$data = array();
		foreach($servicetime as $key => $value) {
			if($value['TimeId'] == $v['TimeId']) {
				$data[$k]['state'] = true;
				break;
			}
			$data[$k]['time'] = $v['StartTime'];
			$data[$k]['time_id'] = $v['TimeId'];
		}
		if($data) {
			$this->returnJsonData(200,$data);
		}else{
			$this->returnJsonData(200,array());
		}
      }
      
      public function capacityByuserIdDate() {
            		$postData = I('request.');
			$Capacity = $postData['date'];
			$CraftsmanId = $postData['user_id'];
			$where['IsDelete'] = 0 ;
			//所有的可约时间点
			$servicetime = M('prd_servicetime')->where($where)->order('StartTime')->field("TimeId,StartTime")->select();
			unset($where);
			$where['CraftsmanId'] = $CraftsmanId;
			$where['Capacity'] = $Capacity;
			$capacity = M('crt_capacity')->where($where)->field("CapacityId,Capacity,TimeId")->select();
			$data = array();
			foreach ($servicetime as $k => $v) {
				$data[$k]['state'] = false;
				foreach ($capacity as $key => $value) {
					if($value['TimeId'] == $v['TimeId']){
						$data[$k]['state'] = true;
						break;
					}
				}
				$data[$k]['time'] = $v['StartTime'];
				$data[$k]['time_id'] = $v['TimeId'];
			}
			if($data){
				$this->returnJsonData(200,$data);
			}else{
				$this->returnJsonData(200,array());
			}
      }
      
      //获取时间点
      public function getTime() {
            		$servicetime = M('prd_servicetime')->where(" IsDelete=0 ")->field("TimeId,StartTime")->order('StartTime')->select();
			$data = array();
			foreach ($servicetime as $key => $value) {
				$data[$key]['time_id'] = $value['TimeId'];
				$data[$key]['time'] = $value['StartTime'];
			}
			if($data){
				$this->returnJsonData(200,$data);
			}else{
				$this->returnJsonData(200,array());
			}
      }
      
      //编辑
      public function updateCapacity() {
            		$postData = I('request.');
			$user_id = $postData['user_id'];
			if(!$user_id){
				$this->returnJsonData(300);
			}
			$beginTime = date('Y-m-d',time());
			$endTime = date('Y-m-d',strtotime("+13 day"));
			$where['Capacity'] = array(array('egt',$beginTime),array('elt',$endTime));
			$where['CraftsmanId'] = $user_id;
			//查询14天的产能
			$capacity = M('crt_capacity')->where($where)->field("CapacityId,Capacity,TimeId")->select();
			unset($where);
			$where['IsDelete'] = 0 ;
			//所有的可约时间点
			$servicetime = M('prd_servicetime')->where($where)->order('StartTime')->field("TimeId,StartTime")->select();
			$data = array();
			//循环遍历14天的产能
			for($i = 0;$i < 14;$i++){
				$data[$i]['date'] = date('Y-m-d',strtotime("+".$i." day"));
				$data[$i]['content'] = array();
				foreach ($servicetime as $k => $v) {
					$data[$i]['content'][$k]['time'] = $v['StartTime'];
					$data[$i]['content'][$k]['time_id'] = $v['TimeId'];
					$data[$i]['content'][$k]['state'] = false;
					$data[$i]['content'][$k]['capacity_id'] = '';
					foreach ($capacity as $key => $value) {
						if(date('Y-m-d',strtotime("+".$i." day")) == $value['Capacity']){
							if($value['TimeId'] == $v['TimeId']){
								$data[$i]['content'][$k]['state'] = true;
								$data[$i]['content'][$k]['capacity_id'] = $value['CapacityId'];
								break;
							}
						}
					}
				}
			}
			$array = array('user_id' => $user_id,'data' => $data);
			$this->returnJsonData(200,$array);
      }
      
      //保存设置
      public function setCapacity() {
            $data = file_put_contents("php://input");
            $data = json_decode($data, true);
            if(!$data) {
            	$this->returnJsonData(300);
            }
            $arr = array();
            $i = 0;
            foreach($data['data'] as $Key=>$value) {
            	if($value['data'] == date("Y-m-d")) {
            		foreach($value['content'] as $ke => $val) {
            			$str = (int)$val['time'] - (int)date('H', time());
            			(int)time = substr($str, -2);
            			if($val['time'] < date('H', time())) {
            				$this->returnJsonData(1008);
            			}elseif($time <= 3) {
            				$this->returnJsonData(1008);
            			}
            		}
            	}
            	foreach($value['content'] as $k=>$v) {
            		if($v['state'] == false) {
            			if(!$v['capacity_id']) {
            				$this->returnJsonData(300);
            			}
            			$arr[$i++] = $v['capcity_id'];
            		}
            	}
            }
            if($arr) {
            	$where['CapacityId'] = array('in',$arr);
		$where['IsDelete'] = 0;
		$id = M('ord_orderinfo')->where($where)->field("OrderId")->select();
		if($id) {
			$this->returnJsonData(1008, $id);
		}
            }
            unset($where);
	    $arr = array();
	    $i = 0;
	    $j = 0;
            foreach($data['data'] as $key=>$value) {
            	foreach($value['content'] as $k=>$v) {
            		if($v['state']==false) {
            			$delWhere[$i++] = $v['capacity_id'];
            		}else{
            			$arr[$j]['TimeId'] = $v['time_id'];
            			$arr[$j]['CreaterTime'] = date('Y-m-d H:i:s', time());
            			$arr[$j]['CraftsmanId'] = $data['user_id'];
            			$arr[$j]['MaxNum'] = 1;
            			$arr[$j]['NouseNum'] = 1;
            			$arr[$j++]['Capacity'] = $value['date'];
            		}
            	}
            }
            if($delWhere) {
            	$where['CapacityId'] = array('in', $delWhere);
            	$del = M('crt_capacity')->where($where)->delete();
            }
            if($arr) {
            	$add = M('crt_capacity')->addAll($arr);
            }
            $this->returnJsonData(200);
      }
      
      //我的订单列表
      public function orderList() {
            	$postData = I('request.');
		$CraftsmanId = $postData['user_id'];
		if(!$CraftsmanId){
			$this->returnJsonData(300);
		}
		$where['IsDelete'] = 0;
		$where['CraftsmanId'] = $CraftsmanId;
		$where['_string'] = "Status = 3 or (Status = 0 and PayWay = 1)";
		$ordData = M('ord_orderinfo')->order('ReservationTime desc')->where($where)->field("OrderId,ReservationTime,Address,Status,PayWay")->select();
		unset($where);
		foreach($ordData as $value){
			$order_id_s .= $value['OrderId'].',';
		}
		$order_id_s = trim($order_id_s, ',');
		if($order_id_s) {
			$proLogData = M('ord_procedure_log')->where('OrderId in('.$order_id_s.')')->select();
			$prdData    = M('ord_order_item')->where('OrderId in ('.$oder_id_s.')')->field('OrderId, ProductName')->select();
		}
		$proData = M('prd_procedure')->select();
		$data = array();
		$orderIdWhere = array();
		$o = 0;
		foreach($ordData as $key => $value) {
			$log = array();
			$j = 0;
			foreach($proLogData as $k => $v) {
				if($value['OrderId'] == $v['OrderId']) {
					$log[$j]['OrderId'] = $v['OrderId'];
					$log[$j++]['ProcessId'] = $v['ProcessId'];
				}
			}
			$data[$key]['statusDescription'] = '未确认';
			$data[$key]['status'] = $value['Status'];
			$data[$key]['sort'] = 2;
			$proLog = array();
			$f = 0;
			foreach($prdData as $k => $v) {
				if($value['OrderId'] == $v['OrderId']) {
					$data[$key]['type'] 	= (string)$v['ProductName'];
					$data[$key]['order_id'] = (int)$v['OrderId'];
					$data[$key]['time'] 	= (string)$value['ReservationTime'];
					$data[$key]['address']  = (string)$value['Address'];
				}
			}
			if(!empty($log)) {
				foreach($proData as $prokey => $provalue) {
					if($provalue['ProcessId'] == $logvalue['ProcessId']) {
						$proLog[$f]['Orderid'] = $provalue['Orderid'];
						$proLog[$f]['OrderId'] = $logvalue['OrderId'];
						$proLog[$f++]['ProcessId'] = $provalue['ProcessId'];
					}
				}
				if(count($proLog)>1) {
					foreach($proLog as $k=>$v) {
						$temp[] = $v['Orderid'];
						array_multisort($temp, SORT_DESC, $proLog);
					}
					$temp = array();
				}
				if($proLog[0]['ProcessId'] == 1) {
					$data[$key]['statusDescription'] = '待服务';
					$data[$key]['state'] = 9;
					$data[$key]['sort'] = 3;
				}elseif($proLog[0]['ProcessId'] == 6) {
					//已服务显示已完成
					$orderIdWhere[$o++] = $proLog[0]['OrderId'];
					unset($data[$key]);
				}else{
					$data[$Key]['statusDescription'] = '服务中';
					$data[$Key]['state'] = 10;
					$data[$key]['sort'] = 1;
				}
			}
		}
		if(count($data)>1) {
			foreach($data as $key=>$val) {
				$temp_arr[] = $val['sort'];
				unset($data[$key]['sort']);
			}
			$temp_arr = array();
			$nine = array();
			$n = 0;
			$three = array();
			$t = 0;
			$ten = array();
			$te = 0;
			foreach($data as $key=>$val) {
				if($data[$key]['state'] == 9) {
					$nine[$n]['state'] = 9;
					$nine[$n]['statusDescription'] = '待服务';
					$nine[$n]['type'] = (string)$data[$key]['type'];
					$nine[$n]['order_id'] = (int)$data[$key]['order_id'];
					$nine[$n]['time'] = (string)$data[$key]['time'];
					$nine[$n++]['address'] = (string)$data[$key]['address'];
				}elseif($data[$key]['state'] == 3 or $data[$key]['state'] == 0) {
					$three[$t]['state'] = $data[$key]['state'];
					$three[$t]['statusDescription'] = '未确认';
					$three[$t]['type'] = (string)$data[$key]['type'];
					$three[$t]['order_id'] = (int)$data[$key]['order_id'];
					$three[$t]['time'] = (string)$data[$key]['time'];
					$three[$t++]['address'] = (string)$data[$key]['address'];
				}elseif($data[$key]['state'] == 10) {
					$ten[$te]['state'] = 10;
					$ten[$te]['statusDescription'] = '服务中';
					$ten[$te]['type'] = (string)$data[$key]['type'];
					$ten[$te]['order_id'] = (int)$data[$key]['order_id'];
					$ten[$te]['time'] = (string)$data[$key]['time'];
					$ten[$te++]['address'] = (string)$data[$key]['address'];
				}
			}
			if(count($nine)>1){
				foreach($nine as $key=>$val){
				    $time[] = $val['time'];
				    array_multisort($time,SORT_ASC,$nine);
				}
				$time = array();
			}
			if(count($three)>1){
				foreach($three as $key=>$val){
				    $time[] = $val['time'];
				    array_multisort($time,SORT_ASC,$three);
				}
				$time = array();
			}
			if(count($ten)>1){
				foreach($ten as $key=>$val){
				    $time[] = $val['time'];
				    array_multisort($time,SORT_ASC,$ten);
				}
				$time = array();
			}
			$unfinish = array_merge( $ten, $three, $nine);
		}else{
			$un = 0;
			foreach($data as $key=>$value) {
				$unfinish[$un]['state'] = $value['state'];
				$unfinish[$un]['statusDescription'] = $value['statusDescription'];
				$unfinish[$un]['type'] = $value['type'];
				$unfinish[$un]['order_id'] = $value['order_id'];
				$unfinish[$un]['time'] = $value['time'];
				$unfinish[$un++]['address'] = $value['address'];
				unset($data[$key]['sort']);
			}
		}
		unset($where);
		unset($map);
		$where['Status'] = array('in',array('2','4','7','8'));
		$where['IsDelete'] = 0;
		$where['CraftsmanId'] = $CraftsmanId;
		$map['_complex'] = $where;
		if(!empty($orderIdWhere)) {
			$map['OrderId'] = array('in',$orderIdWhere);
			$map['_logic'] = 'or';
		}
		$ordData = M('ord_orderinfo')->order('ReservationTime desc')->where($map)->field("OrderId,ReservationTime,Address,Status")->select();
		unset($where);
		$prdData = M('ord_order_item')->field('OrderId, ProductName')->select();
		$evaluationData = M('prd_evaluation')->field('OrderId, StarNums')->select();
		$arr = array();
		$i = 0;
		foreach($ordData as $key=>$value) {
			if($value['Status'] == 2) {
				$arr[$Key]['state'] = 2;
				$arr[$key]['statusDescription'] = '取消订单';
			}elseif($value['Status'] == 4 || $value['Status'] == 3 || $value['Status'] == 0) {
				$arr[$key]['state'] = 4;
				$arr[$key]['statusDescription'] = '未点评';
			}elseif($value['Status'] == 7) {
				$arr[$key]['state'] = 7;
				foreach($evaluationData as $k=>$v) {
					if($value['OrderId'] == $v['OrderId']) {
						if($v['StarNums'] == 5 || $v['StarNums'] == 4) {
							$arr[$key]['evaluate'] = 1;
							$arr[$key]['statusDescription'] = '好评';
						}elseif($v['StarNums'] == 2 || $v['StarNums'] == 1) {
							$arr[$key]['evaluate'] = 3;
							$arr[$key]['statusDescription'] = '差评';
						}else{
							$arr[$key]['evaluate'] = 2;
							$arr[$key]['statusDescription'] = '中评';
						}
					}
				}
			}elseif($value['Status'] == 8) {
				$arr[$key]['state'] = 8;
				$arr[$key]['statusDescription'] = '差评';
			}
			foreach($prdData as $k=>$v) {
				if($value['OrderId'] == $v['OrderId']) {
					$arr[$key]['type'] = (string)$v['ProductName'];
					$arr[$key]['order_id'] = (int)$v['OrderId'];
					$arr[$key]['time'] = (string)$value['ReservationTime'];
					$arr[$key]['address'] = (string)$value['Address'];
				}	
			}
		}
		if($unfinish) {
			$order_list['unfinish'] = $unfinish;
		}else{
			$order_list['unfinish'] = array();
		}
		$order_list['finish'] = $arr;
		if($order_list) {
			$this->returnJsonData(200, $order_list);
		}else{
			$this->returnJsonData(200, array());
		}
      }
      
      //订单详情页
      public function orderShow() {
            		$postData = I('request.');
			$OrderId = $postData['order_id'];
			if(!$OrderId){
				$this->returnJsonData(300);
			}
			$arr = array();
			$where['OrderId'] = $OrderId;
			$ordData = M('ord_orderinfo')->where($where)->field("RecycleTxt,OrderId,VmallOrderId,Address,Status,Name,ReservationTime,ProductRewardId,Phone,PayWay")->find();
			unset($where);
			$where['OrderId'] = $OrderId;
			$prdData = M('ord_order_item')->where($where)->field('OrderId,ProductName,ProductId')->find();
			unset($where);
			$where['ProductId'] = $prdData['ProductId'];
			$priceData = M('prd_productinfo')->where($where)->field('Profit')->find();
			unset($where);
			$where['ProductRewardId'] = $ordData['ProductRewardId'];
			$rewardData = M('prd_reward')->where($where)->field('RewardId')->find();
			unset($where);
			$where['RewardId'] = $rewardData['RewardId'];
			$rData = M('prd_reward')->where($where)->field('Price')->find();
			$sumPrice = $priceData['Profit']+$rewardData['Price'];
			$rewardData['Price'] = $rewardData['Price']?$rewardData['Price']:0;
			$data['order_id'] = (int)$ordData['OrderId'];
			$data['order_num'] = (string)$ordData['VmallOrderId'];
			$data['type'] = (string)$prdData['ProductName'];
			$data['price'] = (string)$sumPrice.'元(服务费：'.(string)$priceData['Profit'].'元+激励：'.(string)$rewardData['Price'].'元)';
			$data['address'] = (string)$ordData['Address'];
			$data['time'] = (string)$ordData['ReservationTime'];
			$data['user'] = (string)$ordData['Name'];
			$data['phone'] = (string)$ordData['Phone'];
			$data['RecycleTxt']	= (string)$ordData['RecycleTxt'];
			$proLogData = M('ord_procedure_log')->where(array('OrderId'=>$ordData['OrderId']))->select();
			if($proLogData){
				$data['state'] = 9;
				$data['status'] = '待服务';
				$data['yes'] = '现在出发';
				$data['no'] = '更改订单';
				if($ordData['PayWay'] == 1){
					if($ordData['Status'] == 3){
						$data['PayWay'] = '线下支付-已支付';
					}else{
						$data['PayWay'] = '线下支付-未支付(所收费用以短信为准)';
					}
				}else{
					$data['PayWay'] = '线上支付-已支付';
				}
			}else{
				$data['state'] = 3;
				$data['status'] = '未确认';
				$data['yes'] = '确认接单';
				$data['no'] = '无法接单';
				if($ordData['PayWay'] == 1){
					if($ordData['Status'] == 3){
						$data['PayWay'] = '线下支付-已支付';
					}else{
						$data['PayWay'] = '线下支付-未支付(所收费用以短信为准)';
					}
				}else{
					$data['PayWay'] = '线上支付-已支付';
				}
			}
			$this->returnJsonData(200,$data);
      }
      
      //更改订单状态
      public function updateOrderState() {
            		$postData = I('request.');
			$OrderId = $postData['order_id'];
			if(!$OrderId){
				$this->returnJsonData(300);
			}
			$data['ProcessId'] = 1;
			$proName = M('prd_procedure')->where(array('ProcessId'=>'1'))->field('Name')->find();
			$data['Name'] = $proName['Name'];
			$order = M('ord_orderinfo')->where(array('OrderId'=>$OrderId))->field('CraftsmanId')->find();
			$craftsman = M('crt_craftsmaninfo')->where(array('CraftsmanId'=>$order['CraftsmanId']))->field('Lat,Lng')->find();
			$data['OrderId'] = $OrderId;
			$data['CreaterTime'] = date("Y-m-d H:i:s");
			$data['Lng'] = $craftsman['Lng'];
			$data['Lat'] = $craftsman['Lat'];
			M('ord_procedure_log')->add($data);
			//回调调取XX接口
			$param['order_id']	= $OrderId;
			$param['status']	= 1;
			$param['lng']		= $data['Lng'];
			$param['lat']		= $data['Lat'];
			$this->_callback_by($param);
			
			$this->returnJsonData(200);
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
	      $order_id		= $param['order_id'];
	      $status		= $param['status'];
	      $lng		= $param['lng'];
              $lat		= $param['lat'];
              $log_url 		= '/opt/www_logs/'.C('TP_PROJECT_APP').'/api/'.'by_api_'.date('Ymd').'.log';
              wlog($log_url, $param);
              
              //回调调取XX接口
              $model = new \Artisans\Org\Scrypt($this->_scrypt_pwd);
              $param = array(
              		'id'=>$order_id,
			'status'=>$status,
			'gps'=>$lng.','.$lat
              );
              $params 	 = http_build_query($params);
              $en_string = $model->encrypt_base64($params);
              $url	 = $this->_callback_by_url.'?v='.urlencode($en_string);
              $data 	 = send_get_curl($url);
              wlog($log_url, $data);
	}
	
	//传送客户端手机号
	public function getPhone() {
	        $postData = I('request.');
        	$where['IsDelete'] = 0;
		$phone = M('sys_phone')->where($where)->field('Phone,PhoneType')->select();
		$data['unable'] = '';
		$data['modify'] = '';
		$data['help'] = '';
		$data['phone'] = '';
		foreach($phone as $value) {
			if($value['PhoneType'] == 0) {
				$data['unable'] = $value['Phone'];
			}elseif($value['PhoneType'] == 1) {
				$data['modify'] = $value['Phone'];
			}elseif($value['PhoneType'] == 2) {
				$data['help'] = $value['Phone'];
			}elseif($value['PhoneType'] == 3) {
				$data['phone'] = $value['Phone'];
			}
		}
		$this->returnJsonData(200, $data);
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
	      $postData    =  I('param.');
	      if (isset($postData['OrderId'])) {
	      		//查询的条件
	      		$where['ord.OrderId']     =  $postData['OrderId'];
			$where['dictionary.Type'] =  20;
			// 查询的字段
			$field[]     =  'ord.*';
			$field[]     =  'dictionary.DisKey as StatusValue';
			// 查询
			$info  =   M('ord_orderinfo ord')
				   ->join("left join sys_dictionary dictionary on ord.Status=dictionary.DisValue")
			           ->join("left join ord_order_item item on ord.OrderId=item.OrderId")
				   ->field($field)
				   ->where($where)
				   ->find();
			if($info) {
				$return['status']   =    200;
				$return['return']   =    $info;
			}else{
				$return['status']   =    409;   
				$return['msg']      =    '无订单数据';
			}
	      }else{
	      		$return['status']   =    300;   
			$return['msg']      =    '缺少参数';
	      }
	      json_return($return, $postData['test']);
	}
	
	//打电话记录日志接口
	public function CallPhoneLog() {
	      $postData=I('param.');
	      if (isset($postData['CraftsmanId']) && isset($postData['OrderId']) && isset($postData['Phone'])) {
	      	        $postData['CreaterTime'] = date("Y-m-d H:i:s");
	      	        $status = M('cut_callphonelog')->add($postData);
	      	        if($status) {
	      	        	$return['status'] = 200;
				$return['msg']    = 'success';
	      	        }else{
	      	        	$return['status'] = 500;
				$return['msg']    = 'error';
	      	        }
	      }else{
	      		$return['status'] = 300;
			$return['msg']    = '缺少参数';
	      }
	      json_return($return,$postData['test']);
	}
	
	//获取订单流程
	poublic function getOrderProcedure() {
	      $postData   =   I('param.');
	      if (isset($postData['OrderId'])) {
	      		$count = (int)M('ord_procedure_log')->where(' ( `OrderId` = %d ) ',$postData['OrderId'])->order('ProcessId Desc')->getField('ProcessId');
	      		$item  =      M('ord_order_item')->where(' ( `OrderId` = %d ) ',$postData['OrderId'])->find();
	      		if ($count==2) {
	      			$Process     =  M('prd_procedure')->where('( `ProcessId`=3 )')->select();
				$orderInfo   =  M('ord_orderinfo')->where(' ( `OrderId` = %d ) ',$postData['OrderId'])->find();
				$ProductInfo =  M('prd_productinfo')->find($item['ProductId']);
				if ($orderInfo['PayWay'] == 1 && $orderInfo['Status'] == 0 ) {
					$orderInfo['PayWay'] = '线下支付-未支付(所收费用以短信为准)';
				}elseif ($orderInfo['PayWay'] == 1 && $orderInfo['Status'] == 3) {
					$orderInfo['PayWay'] = '线下支付-已支付';
				}elseif ($orderInfo['Status'] == 3) {
					$orderInfo['PayWay'] = '线上支付-已支付';
				}elseif ($orderInfo['Status'] == 0) {
					$orderInfo['PayWay'] = '线上支付-未支付(所收费用以短信为准)';
				}
				$return['status'] 	= 200;
				$return['msg']	  	= 'success';
				$return['name']   	= $item['ProductName'];
				$return['ProcessInfo']  = $Process;
				$return['orderInfo']    = $orderInfo;
				$return['IsPhoto']      = $ProductInfo['IsPhoto'];
				$return['PhotoNum']     = $ProductInfo['PhotoNum'];
	      		}elseif($count==3) {
	      			$Process     = M('prd_procedure')->where('( `ProcessId`=4 )')->select();
				$orderInfo   = M('ord_orderinfo')->where(' ( `OrderId` = %d ) ',$postData['OrderId'])->find();
				$ProductInfo = M('prd_productinfo')->find($item['ProductId']);
	      			if ($orderInfo['PayWay'] == 1 && $orderInfo['Status'] == 0 ) {
					$orderInfo['PayWay'] = '线下支付-未支付(所收费用以短信为准)';
				}elseif ($orderInfo['PayWay'] == 1 && $orderInfo['Status'] == 3) {
					$orderInfo['PayWay'] = '线下支付-已支付';
				}elseif ($orderInfo['Status'] == 3) {
					$orderInfo['PayWay'] = '线上支付-已支付';
				}elseif ($orderInfo['Status'] == 0) {
					$orderInfo['PayWay'] = '线上支付-未支付(所收费用以短信为准)';
				}
				$return['status']      = 200;
				$return['msg']         = 'success';
				$return['name']        = $item['ProductName'];
				$return['ProcessInfo'] = $Process;
				$return['orderInfo']   = $orderInfo;
				$return['IsPhoto']     = $ProductInfo['IsPhoto'];
				$return['PhotoNum']    = $ProductInfo['PhotoNum'];
	      		}elseif($count==4) {
	      			$Process     = M('prd_procedure')->where('( `ProcessId`=5 )')->select();
				$orderInfo   = M('ord_orderinfo')->where(' ( `OrderId` = %d ) ',$postData['OrderId'])->find();
				$ProductInfo = M('prd_productinfo')->find($item['ProductId']);
				$return['status']	=200;
				$return['msg']		='success';
				$return['name']		=$item['ProductName'];
				$return['ProcessInfo']  =$Process;
				$return['IsPhoto']	=$ProductInfo['IsPhoto'];
				$return['PhotoNum']	=$ProductInfo['PhotoNum'];
	      		}else{
	      			$return['status']=410;
				$return['msg']='流程状态错误';
	      		}
	      }else{
	      		$return['status'] = 300;
			$return['msg']    = '缺少参数';
	      }
	      json_return($return,$postData['test']);
	}
	
	/**
	* 更新订单流程
	* @access       public
	* @param	string    OrderId       订单ID
	* @param	int	  CraftsmanId   XXXid
	* @param	float	  lng	        经度
	* @param	float	  lat           纬度
	*/
	public function UdateOrderProcedure() {
	      $postData  =   I('param.');
	      $where[]=$postData['OrderId'];
	      $where[]=$postData['CraftsmanId'];
	      if (isset($postData['OrderId']) && isset($postData['CraftsmanId'])) {
	      		$ord = M('ord_orderinfo')->where(' ( `OrderId` = %d ) AND ( `CraftsmanId` = %d )',$where)->find();
	      		if($ord) {
	      			if($ord['Status'] == 3 || $ord['Status'] == 0) {
	      				//服务中, 已到达
	      				$count = (int)M('ord_procedure_log')->where(' ( `OrderId` = %d ) ',$postData['OrderId'])->order('ProcessId Desc')->getField('ProcessId');
	      				if($count==0) {
	      					$return['status']=410;
						$return['msg']='订单状态错误';
	      				}
	      				if ($count==1) {
	      					$process = M('prd_procedure')->where('ProcessId=2')->find();
	      					$param['status']         = $log_data['ProcessId']   = $process['ProcessId'];
						$log_data['Name']        = $process['Name'];
						$log_data['CreaterTime'] = date("Y-m-d H:i:s");
						$param['order_id'] 	 = $log_data['OrderId']     = $postData['OrderId'];
						$param['lat']		 = $log_data['Lat']	    = $postData['Lat'];
						$param['lng']		 = $log_data['Lng']	    = $postData['Lng'];
						// 更新流程数据
						M('ord_procedure_log')->add($log_data);
						$this->_callback_by($param);
						
						$return['status']=200;
						$return['msg']='已更新为【'.$process['Name'].'】';
	      				}
	      				if($count==2) {
	      					if($postData['Lat']==0 && $postData['Lat']==0) {
	      						//没有数据记录, 更新为我已签到
	      						$process = M('prd_procedure')->where('ProcessId=3')->find();
	      						$param['status']  	 = $log_data['ProcessId'] = $process['ProcessId'];
							$log_data['Name']        = $process['Name'];
							$param['order_id']	 = $log_data['OrderId']   = $postData['OrderId'];
							$log_data['CreaterTime'] = date("Y-m-d H:i:s");
							$param['lat']		 = $log_data['Lat']       = $postData['Lat'];
							$param['lng']		 = $log_data['Lng']       = $postData['Lng'];
							// 更新流程数据
							M('ord_procedure_log')->add($log_data);
							$this->_callback_by($param);

							$return['status']=200;
							$return['msg']='已经强制更新为【'.$process['Name'].'】';
	      					}else{
		      					/*CREATE DEFINER=`用户名`@`%` FUNCTION `craft_get_distance`(lat1 DOUBLE,lng1 DOUBLE,lat2 DOUBLE,lng2 DOUBLE) RETURNS double
							BEGIN
							DECLARE distance DOUBLE DEFAULT 0;
							DECLARE lat VARCHAR(20) DEFAULT '';
							DECLARE lng VARCHAR(20) DEFAULT '';
							DECLARE r DECIMAL(10,3);
							SET r = 6378.137;
							SET lat1 = (lat1*PI())/180;
							SET lng1 = (lng1*PI())/180;
							SET lat2 = (lat2*PI())/180;
							SET lng2 = (lng2*PI())/180;
							SET lat = lat2-lat1;
							SET lng = lng2-lng1;
							SET distance = 2*ASIN(SQRT(POW(SIN(lat/2),2)+COS(lat1)*COS(lat2)* POW(SIN(lng/2),2)));
							SET distance = r*distance;
							RETURN CONVERT(distance,DECIMAL(10,1));
							END;*/
							$field[] = "*";
							$field[] = "craft_get_distance({$postData['Lat']}, {$postData['Lng']}, Lat, Lng) Distance";
							$Distance = M('ord_orderinfo')
							            ->where(' ( `OrderId` = %d ) ', $postData['OrderId'])
							            ->field($field)
							            ->find();
							if($Distance['Distance']>1 && !$postData['ForceUpdate']) {
								$return['status']=502;
								$return['msg']='距离预约地点大于1公里,还差:'.$Distance['Distance'].'公里';
							}else{
								//没有数据记录，更新为我已签到
								$process = M('prd_procedure')->where('ProcessId=3')->find();
								$param['status']	 =  $log_data['ProcessId'] = $process['ProcessId'];
								$log_data['Name']        =  $process['Name'];
								$param['order_id']	 =  $log_data['OrderId']   = $postData['OrderId'];
								$log_data['CreaterTime'] =  date("Y-m-d H:i:s");
								$param['lat']		 =  $log_data['Lat']	  = $postData['Lat'];
								$param['lng']		 =  $log_data['Lng']       = $postData['Lng'];
								// 更新流程数据
								M('ord_procedure_log')->add($log_data);
								$this->_callback_by($param);
	
								$return['status']=200;
								$return['msg']='成功更新为【'.$process['Name'].'】';
							}
	      					}
		      			}
		      			if($count == 3) {
		      				//没有数据记录, 更新为开始服务
		      				$process = M('prd_procedure')->where('ProcessId=4')->find();
						$param['status']	 = $log_data['ProcessId'] = $process['ProcessId'];
						$log_data['Name']	 = $process['Name'];
						$param['order_id']	 = $log_data['OrderId']   = $postData['OrderId'];
						$log_data['CreaterTime'] = date("Y-m-d H:i:s");
						$param['lat']		 = $log_data['Lat']	  = $postData['Lat'];
						$param['lng']		 = $log_data['Lng']	  = $postData['Lng'];
						// 更新流程数据
						M('ord_procedure_log')->add($log_data);
						$this->_callback_by($param);

						$return['status']=200;
						$return['msg']='成功更新为【'.$process['Name'].'】';
		      			}
		      			if($count == 4) {
		      				//交付关键点拍照
		      				$itemInfo    = M('ord_order_item')->where(' ( `OrderId` = %d ) ',$postData['OrderId'])->find();
		      				$ProductInfo = M('prd_productinfo')->find($itemInfo['ProductId']);
		      				if($ProductInfo['IsPhoto'] == 1 && $_FILES['img']) {
		      					$file = $_FILES['img'];
		      					if($ProductInfo['PhotoNum'] >= count($file['name']) && count($file['name']) > 0) {
		      						$img_root_path = C('UPLOAD_WX_IMG');
		      						$save_path     = './artians/app/process/';
		      						$config        = array(
		      							'maxSize' =>2097152, //设置附件上传大小 2M
									'rootPath'=>$img_root_path,
									'savePath'=>$save_path,
									'saveName'=>array('uniqid', ''),
									'exts'	  =>array('jpg', 'gif', 'png', 'jpeg'),
									'autoSub' =>true,
									'subName' =>array('date', 'Ymd')
		      						);
		      						$upload = new \Think\Upload($config);	//实例化上传类
		      						$info   = $upload->upload();
		      						if($info) {
		      							foreach($info as $val) {
		      								$data['OrderId']     = $postData['OrderId'];
		      								$data['PhotoUrl']    = C('VIEW_WX_IMG').trim($save_path, './').'/'.$val['savename'];
		      								$data['PhotoUrlCdn'] = C('VIEW_WX_IMG').trim($save_path, './').'/'.$val['savename'];
		      								$data['CreaterTime'] = date('Y-m-d H:i:s');
		      								M('ord_photoupload')->add($data);
		      							}
		      							
		      							//没有数据记录, 图片上传记录
		      							$process = M('prd_procedure')->where('ProcessId=5')->find();
									$param['status']	= $log_data['ProcessId'] = $process['ProcessId'];
									$log_data['Name']	= $process['Name'];
									$param['order_id']	= $log_data['OrderId']   = $postData['OrderId'];
									$log_data['CreaterTime']= date("Y-m-d H:i:s");
									$param['lat']		= $log_data['Lat']	 = $postData['Lat'];
									$param['lng']		= $log_data['Lng']	 = $postData['Lng'];
									
									// 更新流程数据
									M('ord_procedure_log')->add($log_data);
									$this->_callback_by($param);
									
									//没有数据记录, 图片上传记录
		      							$process = M('prd_procedure')->where('ProcessId=5')->find();
									$param['status']	= $log_data['ProcessId'] = $process['ProcessId'];
									$log_data['Name']	= $process['Name'];
									$param['order_id']	= $log_data['OrderId']   = $postData['OrderId'];
									$log_data['CreaterTime']= date("Y-m-d H:i:s");
									$param['lat']		= $log_data['Lat']	 = $postData['Lat'];
									$param['lng']		= $log_data['Lng']	 = $postData['Lng'];
									
									// 更新流程数据
									M('ord_procedure_log')->add($log_data);
									$this->_callback_by($param);
									
									$stepcount = M('cut_stepstate')->where(' ( `OrderId` = %d ) AND ( `CraftsmanId` = %d )',$where)->count();
									
									$steprd    = M('cut_stepstate')->where(' ( `OrderId` = %d ) AND ( `CraftsmanId` = %d )',$where)->find()['ProductId'];
									$step      = M('prd_step')->where(array('ProductId'=>$steprd))->count();
									
									if ($stepcount == $step   &&  $stepcount != 0) {
										//修改订单状态为已服务
										M('ord_orderinfo')->where(' ( `OrderId` = %d ) AND ( `CraftsmanId` = %d )',$where)->save(array('Status'=>4,'UpdateTime'=>date("Y-m-d H:i:s")));
										//记录日志
										if ($ord['Status']==0) {
											M('ord_update')->add(array('OrderId'=>$postData['OrderId'],'UpdateContent'=>'0|4','CreaterTime'=>date("Y-m-d H:i:s")));
										}else{
											M('ord_update')->add(array('OrderId'=>$postData['OrderId'],'UpdateContent'=>'3|4','CreaterTime'=>date("Y-m-d H:i:s")));
										}
									}
									
									$return['status'] = 200;
									$return['msg']	  = '成功更新为【'.$process['Name'].'】';
		      						}else{
		      							$return['status'] = 500;
		      							$return['msg']	  = $upload->getErrorMsg();
		      						}
		      					}else{
		      						$return['status'] = 1004;
								$return['msg']	  = '图片上传个数有误';
		      					}
		      				}else{
		      							//没有数据记录, 图片上传记录
		      							$process = M('prd_procedure')->where('ProcessId=5')->find();
									$param['status']	= $log_data['ProcessId'] = $process['ProcessId'];
									$log_data['Name']	= $process['Name'];
									$param['order_id']	= $log_data['OrderId']   = $postData['OrderId'];
									$log_data['CreaterTime']= date("Y-m-d H:i:s");
									$param['lat']		= $log_data['Lat']	 = $postData['Lat'];
									$param['lng']		= $log_data['Lng']	 = $postData['Lng'];
									
									// 更新流程数据
									M('ord_procedure_log')->add($log_data);
									$this->_callback_by($param);
									
									$stepcount = M('cut_stepstate')->where(' ( `OrderId` = %d ) AND ( `CraftsmanId` = %d )',$where)->count();
									
									$steprd    = M('cut_stepstate')->where(' ( `OrderId` = %d ) AND ( `CraftsmanId` = %d )',$where)->find()['ProductId'];
									$step      = M('prd_step')->where(array('ProductId'=>$steprd))->count();
									
									if ($stepcount == $step   &&  $stepcount != 0) {
										//修改订单状态为已服务
										M('ord_orderinfo')->where(' ( `OrderId` = %d ) AND ( `CraftsmanId` = %d )',$where)->save(array('Status'=>4,'UpdateTime'=>date("Y-m-d H:i:s")));
										//记录日志
										if ($ord['Status']==0) {
											M('ord_update')->add(array('OrderId'=>$postData['OrderId'],'UpdateContent'=>'0|4','CreaterTime'=>date("Y-m-d H:i:s")));
										}else{
											M('ord_update')->add(array('OrderId'=>$postData['OrderId'],'UpdateContent'=>'3|4','CreaterTime'=>date("Y-m-d H:i:s")));
										}
									}
									
									$return['status'] = 200;
									$return['msg']	  = '成功更新为【'.$process['Name'].'】';		
		      				}
		      			}
	      			}else{
					$return['status'] = 410;
					$return['msg']    = '订单状态错误'.$ord['Status'];
				}
	      		}else{
				$return['stauts'] = 501;
				$return['msg']	  = '该订单不在服务中';
			}
	      }else{
			$return['stauts']=300;
			$return['msg']='缺少参数';
	      }
	      json_return($return,$postData['test']);
	}
	
	public function getCompleteOrderInfo() {
	      $postData  = I('param.');
              $OrderId   = $postData['OrderId'];
              if($OrderId) {
              	         $where['OrderId'] = $OrderId;
			 $ordData = M('ord_orderinfo')->where($where)->field("RecycleTxt,OrderId,VmallOrderId,Address,Status,Name,ReservationTime,ProductRewardId,Phone,PayWay")->find();
			 $prdData = M('ord_order_item')->where($where)->field('OrderId,ProductName,ProductId')->find();
			 unset($where);
			 $where['ProductId'] = $prdData['ProductId'];
			 $priceData = M('prd_productinfo')->where($where)->field('Profit')->find();
			 unset($where);
			 $where['ProductRewardId'] = $ordData['ProductRewardId'];
			 $rewardData = M('prd_reward')->where($where)->field('RewardId')->find();
			 unset($where);
			 $sumPrice = $priceData['Profit']+$rewardData['Price'];
			 $rewardData['Price'] = $rewardData['Price']?$rewardData['Price']:0;
			 $data['order_id'] = (int)$ordData['OrderId'];
			 $data['order_num'] = (string)$ordData['VmallOrderId'];
			 $data['type'] = (string)$prdData['ProductName'];
			 $data['price'] = (string)$sumPrice.'元(服务费：'.(string)$priceData['Profit'].'元+激励：'.(string)$rewardData['Price'].'元)';
		         $data['address'] = (string)$ordData['Address'];
			 $data['Phone'] =(string)$ordData['Phone'];
			 $data['time'] = (string)$ordData['ReservationTime'];
			 $data['user'] = (string)$ordData['Name'];
			 $data['state'] = (int)$ordData['Status'];
			 $data['PayWay']=(int)$ordData['PayWay'];
			 $data['RecycleTxt']	= (string)$ordData['RecycleTxt'];
			 $proLogData = (int) M('ord_procedure_log')->where(' ( `OrderId` = %d ) ',$ordData['OrderId'])->order('ProcessId Desc')->getField('ProcessId');
			 
			 	if($ordData['Status'] == 3 && $ordData['PayWay']==1){
					$data['status'] = '未确认';
					$data['PayWay'] = '线下支付-已支付';
					$data['yes'] = '确认接单';
					$data['no'] = '无法接单';
				}elseif ($ordData['Status'] == 3) {
					$data['status'] = '未确认';
					$data['PayWay'] = '线上支付-已支付';
					$data['yes'] = '确认接单';
					$data['no'] = '无法接单';
					if ($proLogData==1) {
						$data['state'] = 9;
						$data['status'] = '待服务';
						$data['yes'] = '现在出发';
						$data['no'] = '更改订单';
					}else if ($proLogData<6 && $proLogData>1) {
						$data['state'] = 10;
						$data['status'] = '服务中';
					}
				}elseif ($ordData['Status'] == 0 && $ordData['PayWay']==1) {
					$data['status'] = '未支付';
					$data['PayWay'] = '线下支付-未支付(所收费用以短信为准)';
					$data['yes'] = '确认接单';
					$data['no'] = '无法接单';
					if ($proLogData==1) {
						$data['state'] = 9;
						$data['status'] = '待服务';
						$data['yes'] = '现在出发';
						$data['no'] = '更改订单';
					}if ($proLogData<6 && $proLogData>1) {
						$data['state'] = 10;
						$data['status'] = '服务中';
					}
				}elseif ($ordData['Status'] == 0) {
					$data['status'] = '未确认';
					$data['PayWay'] = '线上支付-未支付(所收费用以短信为准)';
					$data['yes'] = '确认接单';
					$data['no'] = '无法接单';
				}else if($ordData['Status'] == 4 && $ordData['PayWay']==1){
					$data['PayWay'] = '线下支付-已支付';
					$data['status'] = '未点评';
				}else if($ordData['Status'] == 4 ){
					$data['PayWay'] = '线上支付-已支付';
					$data['status'] = '未点评';
				}else if($ordData['Status'] == 7 && $ordData['PayWay']==1){
					$data['PayWay'] = '线下支付-已支付';
					$data['status'] = '已完成';
				}else if($ordData['Status'] == 7){
					$data['PayWay'] = '线上支付-已支付';
					$data['status'] = '已完成';
				}else if($ordData['Status'] == 2 && $ordData['PayWay']==1){
					unset($orderInfo['PayWay']);
					$data['status'] = '已取消';
				}else if($ordData['Status'] == 2){
					unset($orderInfo['PayWay']);
					$data['status'] = '已取消';
				}else if($ordData['Status'] == 8 && $ordData['PayWay']==1){
					$data['PayWay'] = '线下支付-已支付';
					$data['status'] = '差评';
				}else if($ordData['Status'] == 8){
					$data['PayWay'] = '线上支付-已支付';
					$data['status'] = '差评';
				}
				
				$return['status']=200;
				$return['msg']='获取成功';
				$return['OrderInfo']=array_filter($data,"fliter_null");
				$ProcessInfo=M('ord_procedure_log')->where('( `OrderId`=%d )  AND ( `ProcessId` <> 1)',$OrderId)->select();
				
				if ($ProcessInfo) {
					foreach ($ProcessInfo as $key => $value) {
						$ProcessInfo[$key]['ProcessId']=$key+1;
						
						$ProcessInfo[$key]['IsPhoto']=0;
						$ProcessInfo[$key]['CreaterTime'] = date('H:i',strtotime($value['CreaterTime']));

						if ($value['ProcessId']==5) {
							$ProcessInfo[$key]['IsPhoto']=1;
							$ProcessInfo[$key]['Photo'] =M('ord_photoupload')->where('( `OrderId`=%d )',$OrderId)->select();
							foreach ($ProcessInfo[$key]['Photo'] as $k => $v) {
								$ProcessInfo[$key]['Photo'][$k]=C('TMPL_PARSE_STRING')['__IMG_URL__'].$v['PhotoUrl'];
							}
						}
					}
					if($ordData['Status'] == 0 && $ordData['PayWay']==1){
						$return['OrderInfo']['status'] = '未点评';
						$return['OrderInfo']['PayWay'] = '线下支付-已支付';
						$return['OrderInfo']['state']=4;

					}elseif($ordData['Status'] == 3 && $ordData['PayWay']==1){
						$return['OrderInfo']['status'] = '未点评';
						$return['OrderInfo']['PayWay'] = '线下支付-已支付';
						$return['OrderInfo']['state']=4;

					}elseif ($ordData['Status'] == 3) {
						$return['OrderInfo']['state']=4;
						$return['OrderInfo']['status'] = '未点评';
						$return['OrderInfo']['PayWay'] = '线上支付-已支付';
						$return['OrderInfo']['yes'] = '确认接单';
						$return['OrderInfo']['no'] = '无法接单';
						if ($proLogData==1) {
							$return['OrderInfo']['state'] = 9;
							$return['OrderInfo']['status'] = '待服务';
							$return['OrderInfo']['yes'] = '现在出发';
							$return['OrderInfo']['no'] = '更改订单';
						}else if ($proLogData<6 && $proLogData>1) {
							$return['OrderInfo']['state'] = 10;
							$return['OrderInfo']['status'] = '服务中';
						}
					}
					$return['ProcessInfo']=array_filter($ProcessInfo,"fliter_null");
				}else{
					$return['ProcessInfo']=array();
				}
				$Evaluation=M('prd_evaluation')->where('( `OrderId`=%d )',$OrderId)->field('HeadImgUrl,HeadImgCdnUrl,Comments,StarNums,CreaterTime')->find();
				if ($Evaluation) {
					if ($Evaluation['StarNums']==5 || $Evaluation['StarNums']==4) {
						$Evaluation['Evaluation']='好评';
					}else if($Evaluation['StarNums']==3 || $Evaluation['StarNums']==2){
						$Evaluation['Evaluation']='中评';
					}else{
						$Evaluation['Evaluation']='差评';
					}
					
					$return['EvaluationInfo']=array_filter($Evaluation,"fliter_null");
				}else{
					$return['EvaluationInfo']=(object)array();
				}
	      }else{
	      		$return['status']=300;
			$return['msg']='缺少参数';
	      }
	      json_return($return,$postData['test']);
	}
	
	
}
