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
