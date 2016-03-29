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
        
      }
      
      //获取XXX的评价信息
      // XXXId
    	// 产品Id
    	// 分页  page limit
    	public function getEvaluationList($param = null) {
    	  
    	}
    	
    	//获取产品关键步骤
    	public function getProductStep($post_data = null) {
    	  
    	}
    	
    	// 获取XX接口
    	// 产品Id    ProductId
    	// 城市Id    CityId
    	// 日期      Date
    
    	// 可传参数  CraftsmanId
    	public function capacityByCityProIdDate($param = null) {
    	  
    	}
    	
    	// 更新订单状态接口
    	public function updateOrderStatus($param = null) {
    	  
    	}
    	
    	    //发送验证码接口
    	    // 电话号 phone
	    // 平台来源 type
	    public function SendCode($param = null) {
	      
	    }
	    
	    //验证验证码接口
	    // 电话号 phone
      	    // 验证码 code
	    public function CheckCode($param = null) {
	      
	    }
	    
	    //保存评价接口
	    public function SaveEvaluation($param = null) {
	      
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
