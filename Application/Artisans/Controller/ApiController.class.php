<?php
namespace Artisans\Controller;
class ApiController extends CommonController {
  
  //获取产品列表接口
  // 必传
		// 平台id  PlatformId
		// 城市id  CityId
	// 可传
		// 排序  1 正序    2倒序  Sorting
		// 类型  查码表的类型     ClassType   
		// （是否分页）
		// 当前页                  page
		// 每页显示多少条产品      limit
      public function getProductList($param = null) {
             if(isset($param)) {
             	$postData = $param;
             }else{
             	$postData = I('param.');
             }
             
             $where['rela.PlatformId'] = $postData['PlatformId'];
             $where['rela.CityId']     = array('in', array('0', $postData['CityId']));
             
             if(isset($postData['CityId']) && isset($postData['PlatformId'])) {
             	      //分类的筛选条件
             	      if(isset($postData['ClassType'])) {
             	      		$where['rela.ClassType'] = $postData['ClassType'];
             	      }
             	      if(isset($postData['Sorting']) && $postData['Sorting'] == 2) {
             	      		$order['rela.Sorting'] = 'desc';
             	      }else{
             	      		$order['rela.Sorting'] = 'asc';
             	      }
             	      
             	      $field[] = 'rela.RelationshipId';
             	      $field[] = 'prd.ProductId';
             	      $field[] = 'prd.ProductName as name';
             	      $field[] = 'prd.ProductTitle as title';
             	      $field[] = 'prd.AddressInfo as addressInfo';
             	      $field[] = 'prd.LogoImgUrl as classImg';
             	      $field[] = 'prd.LogoImgCdnUrl as classImg_cdn';
             	      $field[] = 'prd.DetailImgUrl as headImg';
             	      $field[] = 'prd.DetailImgCdnUrl as headImg_cdn';
             	      $field[] = 'prd.ProductType as prdtype';
             	      $field[] = 'prd.Reservel as position';
             	      $field[] = 'prd.Price as proPrice';
             	      $field[] = 'prd.Reserve2 as colorClass';
             	      
             	      $where['prd.IsShelves'] = is_numeric($postData['IsShelves']) ? $postData['IsShelves']:1;
             	      $where['prd.IsDelete']  = 0;
             	      
             	      if(isset($postData['limit']) && isset($postData['page'])) {
             	      		$return['count'] = M('prd_product_platform_city as rela')
             	      				   ->join('left join prd_productinfo prd on prd.ProductId = rela.ProductId')
             	      				   ->where($where)
             	      				   ->order($order)
             	      				   ->count();
             	      		
             	      		$data = M('prd_product_platform_city')
             	      			->join('left join prd_productinfo prd on prd.ProductId = rela.ProductId')
             	      			->where($where)
             	      			->order($order)
             	      			->field($field)
             	      			->page($postData['page'], $postData['limit'])
             	      			->select();
             	      }else{
             	      		$data = M('prd_product_platform_city')
             	      			->join('left join prd_productinfo prd on prd.ProductId = rela.ProductId')
             	      			->where($where)
             	      			->order($order)
             	      			->field($field)
             	      			->page($postData['page'], $postData['limit'])
             	      			->select();	
             	      }
             	      if($data) {
             	      		foreach($data as $k = > $v) {
             	      			$attribute = M('prd_attribute')
             	      				     ->where(array('RelationshipId'=>$v['RelationshipId']))
             	      				     ->select();
             	      			foreach($attribute as $v1) {
             	      				$attributeName = $v1['Attribute'];
             	      				$AttributeValue = $v1['Value'];
             	      				$data[$k][$attributeName] = $AttributeValue;
             	      			}
             	      			foreach($data as $k=>$v) {
             	      				// 活动的查询的条件
             	      				$where_pro['ProductId'] = $v['ProductId'];
             	      				$where_pro['StartTime'] = array('lt', date("Y-m-d H:i:s"));
             	      				$where_pro['EndTime']   = array('gt', date("Y-m-d H:i:s"));
             	      				$where_pro['IsDelete']  = 0;
             	      				$where_pro['IsUse']	= 1;
             	      				// 活动表筛选的字段
						$field_pro[]  =  'PromotionID';
						$field_pro[]  =  'ActivityPrice as endPrice';
						$field_pro[]  =  'Discount as discount';
						$field_pro[]  =  'StartTime as startTime';
						$field_pro[]  =  'EndTime as endTime';
						
						$promotion    =   M('prd_promotion')->where($where_pro)->field($field_pro)->find();
						if($promotion) {
							$promotion['endPrice'] = (int)$promotion['endPrice'];
						}
						$data[$k]['promotion'] = $promotion;
             	      			}
             	      			$return['status']  =  200;
					$return['msg']   =  'success';
					$return['data']  =  $data;
             	      		}
             	      }
             }else{
             	      	$return['status']  =  300;
			$return['msg']  =  '参数错误';
             }
             
             if(isset($param)) {
             	      	return $return;
             }else{
             	      	json_return($return, $postData['test']);
             }
      }
      
   // 获取产品详情的接口 
     // 平台id    ProductId
  	// 城市id    CityId
  	// 产品Id    PlatformId
      public function getProductInfo($param = null) {
             if(isset($param)) {
             	$postData = $param;
             }else{
             	$postData = I('param.');
             }
             if(isset($postData['CityId']) && isset($postData['PlatformId']) && isset($postData['ProductId'])) {
             		$where['prd.IsShelves']   = is_numeric($postData['IsShelves']) ? $postData['IsShelves'] : 1;
             		$where['prd.IsDelete']    = 0;
             		$where['rela.ProductId']  = $postData['ProductId'];
             		$where['rela.PlatformId'] = $postData['PlatformId'];
             		$where['rela.CityId']     = array('in', array('0', $postData['CityId']));
             		
             		$field[]   =   'rela.RelationshipId';
			$field[]   =   'prd.ProductId';
			$field[]   =   'prd.ProductName as name';
			$field[]   =   'prd.ProductTitle as title';
			$field[]   =   'prd.AddressInfo as addressInfo';
			$field[]   =   'prd.LogoImgUrl as classImg';
			$field[]   =   'prd.LogoImgCdnUrl as classImg_cdn';
			$field[]   =   'prd.DetailImgUrl as headImg';
			$field[]   =   'prd.DetailImgCdnUrl as headImg_cdn';
			$field[]   =   'prd.ProductType as prdtype';
			$field[]   =   'prd.Reserve1 as position';
			$field[]   =   'prd.ProductIntroduction as proIntro';
			$field[]   =   'prd.Promise as proMise';
			$field[]   =   'prd.UserInstructions as userInstr';
			$field[]   =   'prd.Description as description';
			$field[]   =   'prd.Highlights as proSpecial';
			$field[]   =   'prd.Advantage as proAdvan';
			$field[]   =   'prd.ProductImgUrl1 as img1';
			$field[]   =   'prd.ProductImgUrl2 as img2';
			$field[]   =   'prd.ProductImgUrl3 as img3';
			$field[]   =   'prd.ProductImgUrl4 as img4';
			$field[]   =   'prd.ProductImgUrl5 as img5';
			$field[]   =   'prd.ProductImgUrl6 as img6';
			$field[]   =   'prd.ProductImgCdnUrl1 as cdn_img1';
			$field[]   =   'prd.ProductImgCdnUrl2 as cdn_img2';
			$field[]   =   'prd.ProductImgCdnUrl3 as cdn_img3';
			$field[]   =   'prd.ProductImgCdnUrl4 as cdn_img4';
			$field[]   =   'prd.ProductImgCdnUrl5 as cdn_img5';
			$field[]   =   'prd.ProductImgCdnUrl6 as cdn_img6';
			$field[]   =   'prd.Price as proPrice';
			
			$data  =  M('prd_product_platform_city as rela')
				  ->join("left join prd_productinfo prd on prd.ProductId=rela.ProductId")
				  ->where($where)
				  ->field($field)
				  ->find();
				  
			if($data) {
				$attribute = M('prd_attribute')
					     ->where(array('RelationshipId'=>$data['RelationshipId']))
					     ->select();
					     
				foreach($attribute as $v1) {
					$attributeName = $v1['Attribute'];
					$AttributeValue = $v1['Value'];
					$data[$attributeName] = $AttributeValue;
				}
				
				// 活动的查询的条件
				$where_pro['ProductId']   =   $data['ProductId'];
				$where_pro['StartTime']   =   array('lt',date("Y-m-d H:i:s"));
				$where_pro['EndTime']     =   array('gt',date("Y-m-d H:i:s"));
				$where_pro['IsDelete']    =   0;
				$where_pro['IsUse']       =   1;

				// 活动表筛选的字段
				$field_pro[]  =  'PromotionID';
				$field_pro[]  =  'ActivityPrice as endPrice';
				$field_pro[]  =  'Discount as discount';
				$field_pro[]  =  'StartTime as startTime';
				$field_pro[]  =  'EndTime as endTime';
				
				$promotion    =   M('prd_promotion')->where($where_pro)->field($field_pro)->find();
				$data['promotion'] = $promotion;
				if(isset($data['promotion']['discount'])) {
					if($promotion['discount'] == 0) {
						$data['promotion']['statusinfo'] = '';
					}else{
						$data['promotion']['discount'] = $promotion['discount']/10;
						$data['promotion']['stautsinfo'] = $data['promotion']['discount'].'折优惠';
					}
					$data['IsDiscount'] = 1;
				}else{
					$data['IsDiscount'] = 0;
				}
				
				if(session('source')) {
					$source_where['ProductId']  = $postData['ProductId'];
					$source_where['SourceName'] = session('source');
					$source_where['StartTime']  = array('lt',date("Y-m-d H:i:s"));
					$source_where['EndTime']    = array('gt',date("Y-m-d H:i:s"));
					$source_where['IsDelete']   = 0;
					$source_where['IsUse']      = 1;
					
					$source_field[]   =  'SourceName as source';
					$source_field[]   =  'Price as endPrice';
					$source_field[]   =  'StartTime as startTime';
					$source_field[]   =  'EndTime as endTime';
					$source_field[]   =  'Description as statusinfo';
					
					$sourceinfo = M('prd_soure')
						      ->where($source_where)
						      ->field($source_field)
						      ->find();
						      
					if($sourceinfo) {
						$data['promotion']  = $sourceinfo;
						$data['IsDiscount'] = 1;
					}
				}
				
				$data['proIntro']  = FormatInfo(':', $data['proIntro']);
				$data['proMise']   = FormatInfo(':', $data['proMise']);
				$data['userInstr'] = FormatInfo(':', $data['userInstr']);
				$data['proSpecial']= FormatInfo(';', $data['proSpecial']);
				$data['proAdvan']  = FormatInfo(';', $data['proAdvan']);
				
				if($data['prdtype'] == 2) {
					$data['helpYou']	     = FormatInfo(';',$data['helpYou']);
				        $data['useStep']	     = FormatInfo(';',$data['useStep']);
				        $data['qqService']	     = FormatInfo(';',$data['qqService']);
				        $data['phoneService']	     = FormatInfo(';',$data['phoneService']);
				        $data['ServiceTime']	     = FormatInfo(';',$data['ServiceTime']);
				        $data['ServicesDescription'] = FormatInfo(';',$data['ServicesDescription']);
				}
				
				for($i=1; $i<=6; $i++) {
					if($data['img'.$i]) {
						$img[]     = $data['img'.$i];
						$cdn_img[] = $data['cdn_img'.$i];
					}
					unset($data['img'.$i]);
					usset($data['cdn_img'.$i]);
				}
				$data['img'] = $img;
				$data['cdn_img'] = $cdn_img;
				
				$return['status'] = 200;
				$return['msg']	  = 'success';
				$return['data']   = $data;
			}else{
				$return['status']  =  305;
				$return['msg']     =  '未找到该产品';
			}
             }else{
             		$return['status']  =  300;
			$return['msg']  =  '参数错误';
             }
             if(isset($param)) {
             	      	return $return;
             }else{
             	      	json_return($return, $postData['test']);
             }
      }
      
      //获取XXX列表接口
      // 必传
    	// 城市id    City
    	// 经纬度    lat  lng
    	// 时间点    Capacity  TimeId
    	// 产品Id    ProductId
    
    	// 可传
    	// 分页      page  limit
    	// 排序规则  order        
      public function getCraftsManList($param = null) {
        	if(isset($param)) {
	             	$postData = $param;
	        }else{
	             	$postData = I('param.');
	        }
	        if(isset($postData['City']) && isset($postData['Capacity']) && isset($postData['TimeId']) && isset($postData['ProductId'])) {
	        	$where['crt.State']   		=  isset($postData['State'])   ?$postData['State']   :0;
			$where['crt.IsCheck'] 		=  isset($postData['IsCheck']) ?$postData['IsCheck'] :0;
			$where['crt.IsDelete']		=  isset($postData['IsDelete'])?$postData['IsDelete']:0;
			
			$where['crt.City']    		=  $postData['City'];
			$where['cap.Capacity']		=  $postData['Capacity'];    
			$where['cap.TimeId']  		=  $postData['TimeId'];
			$where['prd.ProductId']         =  $postData['ProductId'];
			
			$where['cap.NouseNum']		=  array('neq',0);
			$where['prd.State']             =  1;
			
			if(isset($postData['lng']) && isset($postData['lat'])) {
				$field[] = "craft_get_distance({$postData['lat']}, {$postData['lng']}, crt.Lat, crt.Lng) as Distance";
				if(isset($postData['Distance'])) {
					$order['Distance'] = $postData['Distance'];
				}
			}
			
			if(isset($postData['goodRate'])) {
				$order['goodRate'] = $postData['goodRate'];
			}
			$order['goodRate'] = isset($postData['goodRate']) ? $postData['goodRate'] : 'asc';
			
			if(isset($postData['serviceNum'])) {
				$order['serviceNum'] = $postData['serviceNum'];
			}
			
			if(isset($postData['order'])) {
				$order = $postData['order'];
			}
			
			$field[]   =    'crt.City as city_id';
			$field[]   =    'crt.Phone as phone';
			$field[]   =    'crt.CraftsmanId';
			$field[]   =    'crt.TrueName as trueName';
			$field[]   =    'crt.HeadImgUrl as headImg';
			$field[]   =    'crt.HeadImgCdnUrl as cdn_headImg';
			$field[]   =    'crt.Source as source';
			$field[]   =    'crt.Description as description';
			$field[]   =    'crt.Title as title';
			$field[]   =    'crt.Address as address';
			$field[]   =    'crt.GoodRate as goodRate';
			$field[]   =    'crt.ServiceNum as serviceNum';
			$field[]   =    'cap.NouseNum as nouseNum';
			
			if(isset($postData['page']) && isset($postData['limit'])) {
				$return['count'] = M('crt_craftsmaninfo as crt')
						   ->join("left join crt_capacity cap on crt.CraftsmanId=cap.CraftsmanId")
						   ->join("right join prd_product_craftsman prd on crt.CraftsmanId=prd.CraftsmanId")
						   ->where($where)
						   ->count();
						   
				$data = M('crt_craftsmaninfo as crt')
					->join("left join crt_capacity cap on crt.CraftsmanId=cap.CraftsmanId")
					->join("right join prd_product_craftsman prd on crt.CraftsmanId=prd.CraftsmanId")
					->where($where)
					->field($field)
					->page($postData['page'], $postData['limit'])
					->order($order)
					->select();
			}else{
				$data = M('crt_craftsmaninfo as crt')
				        ->join("left join crt_capacity cap on crt.CraftsmanId=cap.CraftsmanId")
				        ->join("right join prd_product_craftsman prd on crt.CraftsmanId=prd.CraftsmanId")
				        ->where($where)
				        ->field($field)
					->order($order)
				        ->select();
			}
			
			foreach($data as $k => $v) {
				$params['CraftsmanId'] = $v['CraftsmanId'];
				$list = $this->getArtisansProductList($params);
				$params['limit'] = 2;
				$params['page']  = 1;
				$Comment = $this->getEvaluationList($params);
				
				$data[$k]['Distance']    = ceil($data[$k]['Distance']);
				$data[$k]['disPrice']    = ceil($data[$k]['Distance'])*2;
				$data[$k]['productList'] = $list['data'];
				
				if($Comment['data']) {
					$data[$k]['Comment'] = $Comment['data'];
				}else{
					$data[$k]['Comment'] = "";
				}
				$data[$k]['index'] = ($k+1)+(($postData['page']-1)*$postData['limit']);
			}
			
	        	$return['data']    =  $data;
			$return['status']  =  200;
			$return['msg']     = 'success';	
	        }else{
	        	$return['status']  =  300;
			$return['msg']     = '缺少参数';
	        }
	        if(isset($param)) {
	        	return $return;
	        }else{
	        	json_return($return, $postData['test']);
	        }
      }
      
      //获取XXX产品列表接口
      //XXXId CraftsmanId
      public function getArtisansProductList($param = null) {
             if(isset($param)) {
	             	$postData = $param;
	     }else{
	             	$postData = I('param.');
	     }
	     
	     if(isset($postData['CraftsmanId'])) {
	     		$where_pro['prd.IsShelves']     =  isset($postData['IsShelves'])  ?$postData['IsShelves']  :1;
			$where_pro['prd.ProductType']   =  isset($postData['ProductType'])?$postData['ProductType']:0;
			$where_pro['car.CraftsmanId']   =  $postData['CraftsmanId'];
			$where_pro['car.State']         =  1;
			
			$field_pro[]   =   'prd.ProductId';
			$field_pro[]   =   'prd.ProductName';
			$field_pro[]   =   'prd.ProductTitle';
			$field_pro[]   =   'prd.LogoImgUrl as classImg';
			$field_pro[]   =   'prd.LogoImgCdnUrl as classImg_cdn';
			
			$return['data']  =   M('prd_product_craftsman as car')
					     ->join("left join prd_productinfo prd on prd.ProductId=car.ProductId")
					     ->where($where_pro)
				             ->field($field_pro)
					     ->select();
					     
			$return['status']    =  200;
			$return['msg']       =  'success';	   
	     }else{
	     		$return['status']  =   300;
			$return['msg']     =   '参数错误';
	     }
	     
	     if(isset($param)) {
	        	return $return;
	     }else{
	        	json_return($return, $postData['test']);
	     }
      }
      
      //获取XXX的详细信息接口
      // XXXId    CraftsmanId
	    // 经纬度 lat lng
      public function getCraftsManInfo($param = null) {
             if(isset($param)) {
	             	$postData = $param;
	     }else{
	             	$postData = I('param.');
	     }
	     
	     if(isset($postData['CraftsmanId'])) {
	     		$where['CraftsmanId'] = $postData['CraftsmanId'];
	     		if(isset($postData['lng']) && isset($post['lat'])) {
	     			$field[] = "craft_get_distance({$postData['lat']}, {$postData['lng']}, Lat, Lng) as Distance";
	     		}
	     		$field[]   =    'CraftsmanId';
			$field[]   =    'TrueName as trueName';
			$field[]   =    'HeadImgUrl as headImg';
			$field[]   =    'HeadImgCdnUrl as cdn_headImg';
			$field[]   =    'Source as source';
			$field[]   =    'Title as title';
			$field[]   =    'Address as address';
			$field[]   =    'GoodRate as goodRate';
			$field[]   =    'ServiceNum as serviceNum';
			$field[]   =    'Description as description';
			
			$data      =    M('crt_craftsmaninfo')->where($where)->field($field)->find();
			
			$list 	   =   $this->getArtisansProductList($where);
			$data['productInfo'] =   $list['data'];
			
			$return['status']    =  200;
			$return['msg']       =  'success'; 
			$return['data']      =  $data;
	     }
	     
	     if(isset($param)) {
	        	return $return;
	     }else{
	        	json_return($return, $postData['test']);
	     }
      }
      
      //获取XXX的评价信息
      // XXXId
    	// 产品Id
    	// 分页  page limit
    	public function getEvaluationList($param = null) {
    	  	if(isset($param)) {
		         $postData = $param;
		}else{
		         $postData = I('param.');
		}
		
		if(isset($postData['CraftsmanId']) || isset($postData['ProductId'])) {
			if(isset($postData['CraftsmanId'])) {
				$where['CraftsmanId']   =   $postData['CraftsmanId'];
			}
			if(isset($postData['ProductId']))   {
				$where['ProductId']     =   $postData['ProductId'];
			}
			
			$field[]='NickName as name';
			$field[]='StarNums as nums';
			$field[]='Comments as comments';
			$field[]='HeadImgUrl as headImg';
			$field[]='HeadImgCdnUrl as cdn_headImg';
			$field[]='CreaterTime as time';
			$field[]='UserId as userId';
			
			if(isset($postData['page']) && isset($postData['limit'])) {
				$return['count'] = M('prd_evalution')->where($where)->count();
				$data = M('prd_evaluation')->where($where)->field($field)->page($postData)
			}
		}
		
		if(isset($param)) {
		         return $return;
		}else{
		         json_return($return, $postData['test']);
		}
    	}
    	
    	//获取产品关键步骤
    	public function getProductStep($post_data = null) {
    	  	if(isset($post_data)) {
    	  		$param = $post_data;
    	  		$exit_type = 'array';
    	  	}else{
    	  		$param = I('request.');
    	  		$exit_type = 'json';
    	  	}
    	  	
    	  	$pro_id   = $param['pro_id'];
    	  	$order_id = $param['order_id'];
    	  	
    	  	//产品步骤
    	  	if($pro_id) {
    	  		$step_info = M('prd_step')
    	  			     ->where("ProductId = %d and IsDelete = 0", $pro_id)
    	  			     ->field("StepId step_id, Description description")
    	  			     ->select();
    	  	}
    	  	
    	  	//订单步骤
    	  	if($order_id) {
    	  		$order_step_info = M('cut_stepstate')
    	  				   ->where("OrderId = %d", $order_id)
    	  				   ->order("StepId asc")
    	  				   ->select();
    	  	}
    	  	if(empty($order_step_info)) {
    	  		$order_step_info = array();
    	  	}
    	  	
    	  	$data = array(
    	  		'pro_step'=>$step_info,
    	  		'order_step'=>$order_step_info
    	  	);
    	  	
    	  	if($exit_type == 'json') {
    	  		json_return(array('status'=>200, 'msg'=>'success', 'data'=>$data));
    	  	}else{
    	  		return array('status'=>200, 'msg'=>'success', 'data'=>$data);
    	  	}
    	}
    	
    	// 获取XX接口
    	// 产品Id    ProductId
    	// 城市Id    CityId
    	// 日期      Date
    
    	// 可传参数  CraftsmanId
    	public function capacityByCityProIdDate($param = null) {
    	  	if(isset($param)) {
		         $postData = $param;
		}else{
		         $postData = I('param.');
		}
    	  	
    	  	if(isset($postData['ProductId']) && (isset($postData['CityId']) || isset($postData['CraftsmanId'])) && isset($postData['Date'])) {
    	  		$where['crt.State']       =  isset($postData['State'])   ?$postData['State']   :0;
			$where['crt.IsCheck']     =  isset($postData['IsCheck']) ?$postData['IsCheck'] :0;
			$where['crt.IsDelete']    =  isset($postData['IsDelete'])?$postData['IsDelete']:0;
    	  		$where['cap.Capacity']    =  $postData['Date'];
    	  		if(isset($postData['CraftsmanId'])) {
    	  			$where['crt.CraftsmanId'] = $postData['CraftsmanId'];
    	  		}else{
    	  			$where['crt.City'] = $postData['CityId'];
    	  		}
    	  		$where['prd.ProductId'] = $postData['ProductId'];
    	  		$where['prd.State'] =  1;
    	  		
    	  		$field[]   =    'sum(cap.NouseNum) as nouseNum';
    	  		
    	  		$data = M('prd_servicetime as times')
    	  			->field('times.StartTime as time,times.TimeId as time_id')
    	  			->order('time')
    	  			->where('times.IsDelete=0')
    	  			->select();
    	  		
    	  		foreach($data as $ks => $vs) {
    	  			$where['cap.TimeId'] = $vs['time_id'];
    	  			$info = M('crt_capacity as cap')
    	  				->join("right join crt_craftsmaninfo crt on crt.CraftsmanId=cap.CraftsmanId")
    	  				->join("right join prd_product_craftsman prd on cap.CraftsmanId=prd.CraftsmanId")
    	  				->where($where)
    	  				->field($field)
    	  				->select();
    	  			$data[$ks]['nouseNum'] = $info[0]['nouseNum'];
    	  		}
    	  		
    	  		$unix = time();
    	  		$date = date("Y-m-d", $unix);
    	  		if($date == $postData['Date']) {
    	  			$Hour[] = date('H:00', $unix);
    	  			$Hour[] = date('H:00', $unix+3600);
    	  			$Hour[] = date('H:00', $unix+7200);
    	  			$Hour[] = date('H:00', $unix+10800);
    	  		}
    	  		
    	  		foreach($data as $key => $value) {
    	  			if($value['nouseNum'] > 0) {
    	  				$data[$key]['state']=true;
    	  			}else{
    	  				$data[$key]['state']=false;
    	  			}
    	  		}
    	  		
    	  		if($Hour) {
    	  			if($Hour[0] > $value['time']) {
    	  				$data[$key]['state'] = false;
    	  			}
    	  			foreach($Hour as $k => $v) {
    	  				if($v == $value['time']) {
    	  					$data[$key]['state'] = false;
    	  				}
    	  			}
    	  		}
    	  		
    	  		$return['status']   =  200  ;
			$return['msg']      =  'success';
			$return['data']     =  $data;	
    	  	}else{
    	  		$return['status']   =  300;
			$return['msg']      =  '缺少参数';
    	  	}
    	  	
    	  	if($param) {
    	  		return $return;
    	  	}else{
    	  		json_return($return, $postData['test']);
    	  	}
    	}
    	
    	// 更新订单状态接口
    	public function updateOrderStatus($param = null) {
    	        if(isset($param)) {
		         $postData = $param;
		}else{
		         $postData = I('param.');
		}
    	  	
    	  	$data['OrderId'] = $postData['id'];
    	  	$res = M('ord_orderinfo')->where('OrderId = %d AND (Status=4 or Status=3 or Status=0)', $data['OrderId'])->find();
    	  	if($res) {
    	  		M('ord_orderinfo')->where('OrderId = %d AND (Status=4 or Status=3 or Status=0)', $data['OrderId'])->save(array('Status'=>$postData['ss'], 'UpdateTime'=>date('Y-m-d H:i:s')));
    	  		//记录日志
    	  		M('ord_update')->add(array('OrderId'=>$postData['OrderId'],'UpdateContent'=>'4|'.$postData['id'],'CreaterTime'=>date("Y-m-d H:i:s")));
    	  		
    	  		$return['status']=200;
			$return['msg']='success';
    	  	}else{
    	  		$return['status']=300;
			$return['msg']='订单状态不符合';
    	  	}
    	  	if($param) {
    	  		return $return;
    	  	}else{
    	  		json_return($return, $postData['test']);
    	  	}
    	}
    	
    	    //发送验证码接口
    	    // 电话号 phone
	    // 平台来源 type
	    public function SendCode($param = null) {
	      	   if(isset($param)) {
		         $postData = $param;
		   }else{
		         $postData = I('param.');
		   }
	    	   
	    	   if(isset($postData['phone']) && isset($postData['type'])) {
	    	   	$postData['phone'] = trim($postData['phone']);
	    	   	if(check_phone($postData['phone'])) {
	    	   		$addData['Phone']	= $postData['phone'];
		        	$addData['Captcha']	= mt_rand(1000,9999);
		        	$addData['CreaterTime'] = date("Y-m-d H:i:s",time());
		        	$addData['LoseTime']	= date("Y-m-d H:i:s",time()+300);
		        	$addData['Source']	= $postData['type'];
		        	
		        	$content = 'XXXXX验证码（'.$postData['Captcha'].'）';
		        	$sendVerify = D('Comm')->sendShortMsg($addData['Phone'], $content, 'artisans_register');
		        	if($sendVerify['SendMessageResult']['Resultcode'] == '00') {
		        		$where['Phone'] = $postData['phone'];
		        		$res = M('cut_captcha')
		        		       ->where($where)
		        		       ->find();
		        		if($res) {
		        			$id = M('cut_captcha')
		        			      ->where($where)
		        			      ->save($addData);
		        		}else{
		        			$id = M('cut_captcha')
		        			      ->add($addData);
		        		}
		        		if($id) {
		        			$return['status'] = 200;
		        			$return['msg']    = 'success';
		        			$return['code']   = $res;
		        		}else{
		        			$return['status'] = 500;
		        			$return['msg']    = '发送失败';
		        			$return['code']   = $res;
		        		}
		        	}else{
		        		$return['status'] = 500;
			    		$return['msg']    = '发送失败';
			    		$return['code']   = $sendVerify;
		        	}
	    	   	}else{
	    	   		$return['status']  = 400;
			 	$return['msg']     = '数据格式错误';
	    	   	}
	    	   }else{
	    	   	$return['status'] = 300;
    			$return['msg']    = '缺少参数';
	    	   }
	    	   
	    	   if($param) {
	    	   	return $return;
	    	   }else{
	    	   	json_return($return, $postData['test']);
	    	   }
	    }
	    
	    //验证验证码接口
	    // 电话号 phone
      	    // 验证码 code
	    public function CheckCode($param = null) {
	      	   if(isset($param)) {
		         $postData = $param;
		   }else{
		         $postData = I('param.');
		   }
	    	   
	    	   if(isset($postData['phone']) && isset($postData['code'])) {
	    	   	$where['Phone']    = $postData['phone'];
	    		$where['Captcha']  = $postData['code'];
	    		$where['LoseTime'] = array('gt',date("Y-m-d H:i:s"));
	    		$res = M('cut_captcha')->where($where)->find();
	    		
	    		if($res) {
	    			$return['status'] = 200;
	    			$return['msg']    = 'success';
	    			$return['info']   = $res;
	    		}else{
	    			$return['status']=500;
    				$return['msg']='验证码过期或验证码错误';
	    		}
	    	   }else{
	    	   	$return['status']=300;
    			$return['msg']='参数错误';
	    	   }
	    	   
	    	   if($param) {
	    	   	return $return;
	    	   }else{
	    	   	json_return($return, $postData['test']);
	    	   }
	    }
	    
	    //保存评价接口
	    public function SaveEvaluation($param = null) {
	           if(isset($param)) {
		         $postData = $param;
		   }else{
		         $postData = I('param.');
		   }
		   
		   if(isset($postData['recordid']) && isset($postData['score'])) {
		   	$where['recordid']=$postData['recordid'];
    			$data=M('ord_record')->where($where)->find();
    			if($data) {
    				$addData['OrderId']=$data['caseid'];
	    			$addData['NickName']=$data['nick_name'];
	    			$addData['UserId'] = M('cut_uid_thirdname')->where(" ThirdName='%s' ",$data['openid'])->getField('UserId');
	    			$addData['HeadImgUrl'] = '/img/valuation/atator/'.$data['openid'].'.jpg';
	    			$addData['HeadImgCdnUrl'] = '/img/valuation/atator/'.$data['openid'].'.jpg';
	    			$addData['CraftsmanId'] =  M('crt_craftsmaninfo')->where(" UserName='%s' ",$data['username'])->getField('CraftsmanId');
	    			$addData['StarNums']=$postData['score'];
	    			$addData['CreaterTime']=date("Y-m-d H:i:s");
	    			$addData['Source']=0;
	    			$addData['Comments']=$postData['content'];
	    			$addData['ProductId']=M('ord_order_item')->where(array('OrderId'=>$data['caseid']))->getField('ProductId');
	    			
	    			$add=M('prd_evaluation')->add($addData);
	    			if($add) {
	    				M('ord_orderinfo')->where('OrderId = %d AND (Status=4  or Status=3 or Status=0)',$addData['OrderId'])->save(array('Status'=>7,'UpdateTime'=>date("Y-m-d H:i:s")));
					// 记录日志
					M('ord_update')->add(array('OrderId'=>$addData['OrderId'],'UpdateContent'=>'7','CreaterTime'=>date("Y-m-d H:i:s")));
					
					$return['status']=200;
    					$return['msg']='success';
	    			}else{
	    				$return['status']=500;
	    				$return['msg']='失败';
	    				$return['data']=$addData;
	    			}
    			}else{
    				$return['status']=500;
    				$return['msg']='失败';
    			}
		   }else{
		   	$return['status']=300;
    			$return['msg']='缺少参数';
		   }
		   
		   if($param) {
	    	   	return $return;
	    	   }else{
	    	   	json_return($return, $postData['test']);
	    	   }
	    }
	    
	    public function api() {
	           C('TMPL_L_DELIM', '<{');
	           C('TMPL_R_DELIM', '>}');
	           $this->display('api');
	    }
	    
	    //获取XXX基本信息接口
	    public function getcraftinfo() {
	           
	    }
}
