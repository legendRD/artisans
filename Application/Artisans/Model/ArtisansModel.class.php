<?php
namespace Artisans\Model;
use Think\Model;
class ArtisansModel extends Model{
  
  protected $autoCheckFields = False;
  
  private $_getTrade_url = 'http://localhost/order/addor';	       //生成订单号地址
  private $_orderPay_url = 'http://localhost/Paycenter/CreatePayInfo'; //支付接口
  private $_getStatus    = 'http://localhost/order/list';              //获取订单状态地址
  private $_pay_log 	 = '/share/pay_log_url/webArtisans/';	       //更新用户状态和XX
  private $_send_url 	 = 'http://localhost/paycenter/return_verify'; //支付完成之后请求地址
  private $_changeUrl    = 'http://localhost/Qrcode/shorturl';	       //长连接变为短链接
  
  /**
   * 获取广告位
   * @access public
   * @param number $source 平台
   * @param number $num 获取广告位的数量
   * @return unknown
   */
  public function getbanner($source=1, $num=3) {
	        $where = array(
	        	'apo.Osid'=>$source,
	        	'ab.IsDelete'=>0
	        );
	        if($num>0) {
	        	$info = M()->table('app_banner_os apo')
	        		   ->join('app_banner ab ON apo.BannerId = ab.BannerId')
	        		   ->where($where)
	        		   ->order(' OrderId asc ')
	        		   ->limit($num)
	        		   ->field('apo.BannerId, Osid, Title, ImgUrl, ImgCdnUrl, Url')
	        		   ->select();
	        }else{
	        	$info = M()->table('app_banner_os apo')
	        		   ->join('app_banner ab ON apo.BannerId = ab.BannerId')
	        		   ->where($where)
	        		   ->order(' OrderId asc ')
	        		   ->field('apo.BannerId, Osid, Title, ImgUrl, ImgCdnUrl, Url')
	        		   ->select();
	        }
	        return $info;
    }
	 
    /**
     * 获取单个产品信息
     * @access public
     * @param int $pro_id 产品id 
     * @param int $city_id 城市id
     * @param int $is_shelves 是否上架
     * @return boolean|Ambigous <multitype:, multitype:unknown >
     */	 
     public function getOenProInfo($pro_id, $city_id, $is_shelvs=true) {
     	    if(!($pro_id && $city_id)) {
     	    	 return false;
     	    }
     	    $where = array(
     	    	 'ppc.`CityId`'=>$city_id,
     	    	 'ppc.`ProductId`'=>$pro_id,
     	    	 'ppinfo.IsDelete'=>0
     	    );
     	    if($is_shelves) {
     	    	$where['ppinfo.IsShelves'] = 1;
     	    }
     	    $info = $this->getProInfo($where);
     	    return $info;
     }
     
     /**
      * 获取某个城市下的产品信息
      * @access public 
      * @param int $city_id 城市id
      * @param int $is_shelves 获取上架的产品,0获取所有的产品
      * @return mixed
      */
      public function getCityProInfo($city_id, $is_shelves, $num=0) {
      	     if(empty($city_id)) {
      	     	return false;
      	     }
      	     $where = array(
      	     	    'ppc.`CityId`'=>$city_id,
      	     	    'ppinfo.IsDelete'=>0
      	     );
      	     if($is_shelves) {
      	     	    $where['ppinfo.IsShelves'] = 1;
      	     }
      	     $info = $this->getProInfo($where, $num);
      	     return $info;
      }
      
      /*
       * 获取产品信息
       * @access public
       * @param array $where	查询的where条件
       * @return array
       */
       public function getProInfo($where, $num=0) {
       	      $num = (int)$num;
       	      if($num) {
       	      	        $info = M()->table('prd_product_city ppc')
       	      	        	   ->join('left join prd_productinfo ppinfo on ppc.`ProductId`=ppinfo.`ProductId`')
       	      	        	   ->where($where)
       	      	        	   ->order(' ppc.`Orderid` asc ')
       	      	        	   ->limit($num)
       	      	        	   ->field('ppinfo.`ProductId` as product_id, ppinfo.`IsShelves` as is_shelves, `ProductName` as product_name, `Price` as base_price, `ProductTitle` as title, LogoImgUrl, LogoImgCdnUrl, DetailImgUrl, DetailImgCdnUrl')
       	      	        	   ->select();
       	      }else{
       	      		$info = M()->table('prd_product_city ppc')
       	      	        	   ->join('left join prd_productinfo ppinfo on ppc.`ProductId`=ppinfo.`ProductId`')
       	      	        	   ->where($where)
       	      	        	   ->order(' ppc.`Orderid` asc ')
       	      	        	   ->field('ppinfo.`ProductId` as product_id, ppinfo.`IsShelves` as is_shelves, `ProductName` as product_name, `Price` as base_price, `ProductTitle` as title, LogoImgUrl, LogoImgCdnUrl, DetailImgUrl, DetailImgCdnUrl')
       	      	        	   ->select();
       	      }
       	      $tmp = array();
       	      if($info && is_array($info)) {
       	      		  foreach($info as $value) {
       	      		  	  if($value['product_id']) {
       	      		  	  	$promotion_info = array();
       	      		  	  	$promotion_info = $this->getPromotion($value['product_id']);
       	      		  	  	if($promotion_info['PromotionId']) {
       	      		  	  		$value['true_price'] = $promotion_info['ActivityPrice'];
       	      		  	  		$value['discount']   = $promotion_info['Discount'];
       	      		  	  	}else{
       	      		  	  		$value['true_price'] = $value['base_price']
       	      		  	  		$value['discount']   = '';
       	      		  	  	}
       	      		  	  	$tmp[] = $value;
       	      		  	  }
       	      		  }
       	      }
       	      return $tmp;
       }
       
       /**
	 * 获取产品促销信息
	 * @param int $pro_id
	 * @return mixed
	 */
       public function getPromotion($pro_id) {
       	      if(empty($pro_id)) {
       	      	       return false;
       	      }
       	      if(!is_numeric($pro_id)) {
       	      	       return false;
       	      }
       	      $time = date('Y-m-d H:i:s');
       	      $where = array(
       	      	     'ProductId'=>$pro_id,
       	      	     'StartTime'=>array('elt', $time),
       	      	     'EndTime'  =>array('egt', $time);
       	      	     'IsDelete' =>0,
       	      	     'IsUse'    =>1
       	      );
       	      $promotion_info = M('prd_promotion')->where($where)->find();
       	      return $promotion_info;
       }
       
       /**
	 * 获取城市信息
	 * @access public
	 * @param string $city 城市名
	 * @return mixed
	 */
	 public function getCityInfo($city_name) {
	 	if(empty($city_name)) {
	 		return false;
	 	}
	 	$city_info = M('sys_city')->where('CityName'=>array('like', $city_name.'%'))->find();
	 	return $city_info;
	 }
	 
	 public function cityInfo($city_id) {
	 	if(empty($city_id)) {
	 		return false;
	 	}
	 	$city_info = M('sys_city')->find($city_id);
	 	return $city_info;
	 }
	 
	 /*
	  * 获取城市下的XXX
	  * @param     array $var
	  * city_id    int   城市id 【必传】
	  * lng 经度
	  * lat 纬度
	  * order_type int   排序【3距离， 1好评率， 2完成订单量】
	  * currpage   int   当前页
	  * page_size  int   显示条数
	  * @return    mixed
	  */
	  public function getCityUserInfo($var) {
	  	 $city_id = (int)$var['city_id'];
	  	 if(empty($city_id)) {
	  	 	return false;
	  	 }
	  	 $status = $this->isOpen($city_id);
	  	 if(empty($status)) {
	  	 	return false;
	  	 }
	  	 $lng = $var['lng'];
	  	 $lat = $var['lat'];
	  	 $order = (int)$var['order_type'];
	  	 $currpage = $var['currpage'] > 0 ? $var['currpage'] : 1;
	  	 $page_size = $var['page_size'] > 0 ? $var['page_size'] : 10;
	  	 $startnum = ($currpage-1) * $page_size;
	  	 $endnum = $currpage * $page_size;
	  	 $field = " ccman.CraftsmanId as user_id, GoodRate as good_rate, ServiceNum as service_num, TrueName as true_name, HeadImgCdnUrl as head_img_cdn, HeadImgUrl as head_img, ccman.Source as source, ccman.Description as description, ccman.city as city_id";
	  	 if($lat && $lng) {
	  	 	$field .= ", ROUND(craft_get_distance({$lat}, {$lng}, Lat, Lng), 1) as distance ";
	  	 }
	  	 switch($order) {
	  	 	case 3:	//距离
	  	 		if($lat && $lng) {
	  	 			$orderby = ' distance asc, good_rate desc ';
	  	 		}else{
	  	 			$orderby = ' good_rate desc ';
	  	 		}
	  	 		break;
	  	 	case 1: //好评率
	  	 		$orderby = ' good_rate desc ';
	  	 		break;
	  	 	case 2: //完成单量
	  	 		$orderby = ' service_num desc, good_rate desc ';
	  	 		break;
	  	 	default:
	  	 		$orderby = ' good_rate desc ';
	  	 }
	  	 $user_info = M()->table('prd_product_city ppc')
	  	 		 ->join('left join prd_product_craftsman ppcman on ppc.ProductId = ppcman.ProductId')
	  	 		 ->join('left join crt_craftsmaninfo ccman on ppcman.CraftsmanId = ccman.CraftsmanId')
	  	 		 ->where(array('ppc.CityId'=>$city_id, 'ccman.City'=>$city_id, 'ccman.State'=>0, 'ccman.IsDelete'=>0))
	  	 		 ->group('user_id')
	  	 		 ->order($orderby)
	  	 		 ->limit($startnum, $endnum)
	  	 		 ->field($field)
	  	 		 ->select();
	  	if($user_info) {
	  		//查询来源
	  		$source_arr = $this->getCraftSource();
	  		foreach($user_info as &$value) {
	  			$value['source'] = $source_arr[$value['source']];
	  		}
	  	}else{
	  		$user_info = array();
	  	}
	  	return $user_info;
	  }
	  
	  /*
	   * 城市是否开通
	   * @access      public
	   * @param       int     $city_id   城市id
	   * @return      boolean
	   */
	   public function isOpen($city_id) {
	   	  $city_id = (int)$city_id;
	   	  $status  = M('sys_city')->where(array('CityId'=>$city_id))->getField('IsOpen');
	   	  if($status == 0) {
	   	  	return true;
	   	  }else{
	   	  	return false;
	   	  }
	   }
	   
	   /*
	    * 获取某个产品下面的XXX
	    * @param     array     $var
	    * city_id    int             城市id 【必传】
	    * pro_id     int             产品id 【必传】
	    * time       datetime        预约时间
	    * lat        float           纬度
	    * lng        float           经度
	    * order_type int             排序 【4价格，3距离，2完成订单量】
	    * @return    mixed
	    */
	    public function getProUserInfo($var) {
	    	   $data['city_id']	= $city_id	  = (int)$var['city_id'];
		   $data['pro_id']	= $pro_id         = (int)$var['pro_id'];
		                          $time		  = $var['time'];
		   $data['lat']	        = $lat		  = $var['lat'];
		   $data['lng']		= $lng		  = $var['lng'];
		   $data['order_type']	= $order_type     = (int)$var['order_type'];
		   $date = date('Y-m-d',strtotime($time));
		   if(!($city_id && $pro_id)) {
		   	return false;
		   }
		   //判断城市该产品上架
		   $is_shelves	= $this->isShelves($pro_id,$city_id);
		   if(!$is_shelves) {
		   	return false;
		   }
		   //产品的价格
		   $pro_info    = $this->getOneProInfo($pro_id, $city_id, 1);
		   $pro_price   = $pro_info[0]['true_price'];
		   //时间id
		   $time_id     = $this->getTimeId($time);
		   $user_info   = array();
		   if(empty($time_id) && $time) {
		   	return false;
		   }else{
		   	$data['pro_price'] = $pro_price;
		   	$data['date']      = $date;
		   	$data['time_id']   = $time_id;
		   	$data['currpage']  = $var['currpage'];
		   	$data['page_size'] = $var['page_size'];
		   	$user_infp = $this->getUserInfo($data);
		   }
		   return $user_info;
	    }
	    
	/*
	 * 查询XXX信息
	 * @access public
	 * @param  array  $var
	 * @return mixed
	 */
	 public function getUserInfo($var) {
	 	$pro_price	= $var['pro_price'];
		$pro_id	        = $var['pro_id'];
		$city_id        = $var['city_id'];
		$date	        = $var['date'];
		$time_id	= $var['time_id'];
		$lat	        = $var['lat'];
		$lng	        = $var['lng'];
		$order	        = $var['order_type'];
		$currpage	= $var['currpage'];
		$page_size	= $var['page_size'];
		$pro_price	= $pro_price>0 ? $pro_price:0;
		$currpage	= $currpage >0 ? $currpage :1;
		$page_size	= $page_size>0 ? $page_size:10;
		$startnum 	= ($currpage-1)*$page_size;
		$endnum		= $currpage*$page_size;
		if(!($pro_id && $city_id)) {
			return false;
		}
		$field = ' select ccman.CraftsmanId as user_id, GoodRate as good_rate, ServiceNum as service_num, TrueName as true_name, HeadImgCdnUrl as head_img_cdn, HeadImgUrl as head_img, ccman.Source as source, ccman.Description as description, ccman.city as city_id';
		if($time_id && $date) {
			$field .= ', NouseNum as cctime_num';
		}
		if($lat && $lng) {
			if($time_id && $date) {
				$field .= ", ROUND(craft_get_distance({$lat}, {$lng}, Lat, Lng) as ditance, ({$pro_price}+(CASE when cctime.Price>0 THEN cctime.Price ELSE 0 END)+ifnull((SELECT ROUND(craft_get_distance({$lat}, {$lng}, Lat, Lng))*Price FROM crt_craftsman_distance ccd WHERE ccd.CraftsmanId = ccman.CraftsmanId AND ccd.`Min`<=ROUND(craft_get_distance({$lat}, {$lng}, Lat, Lng)) AND ccd.`Max` > ROUND(craft_get_distance({$lat}, {$Lng}, Lat, Lng)) limit 1),0)+(CASE when Addition > 0 THEN Addition ELSE 0 END)) as total_price ";
			}else{
				$field .= ", ROUND(craft_get_distance({$lat},{$lng},Lat,Lng),1) as distance, ifnull({$pro_price}+ifnull((SELECT ROUND(craft_get_distance({$lat},{$lng},Lat,Lng))*Price FROM crt_craftsman_distance ccd WHERE ccd.CraftsmanId=ccman.CraftsmanId AND ccd.`Min`<=ROUND(craft_get_distance({$lat},{$lng},Lat,Lng)) AND ccd.`Max`>ROUND(craft_get_distance({$lat},{$lng},Lat,Lng))),0)+(CASE when Addition>0 THEN Addition ELSE 0 END)) as total_price ";
			}
		}else{
			if($time_id && $date) {
				$field .= " ({$pro_price} + (CASE when cctime.Price > 0 THEN cctime.Price ELSE 0 END) + (CASE when Addition > 0 THEN Addition ELSE 0 END)) as total_price "
			}else{
				$field .= " ({$pro_price} + (CASE when Addition > 0 THEN Addition ELSE 0 END)) as total_price ";
			}
		}
		$table = " FROM prd_product_craftsman ppcman LEFT JOIN crt_craftsmaninfo ccman ON ppcman.CraftsmanId = ccman.CraftsmanId LEFT JOIN crt_craftsman_add ccadd on ccadd.CraftsmanId = ccman.CraftsmanId ";
		$where = " WHERE ppcman.ProductId = {$pro_id} AND ccman.City = {$city_id} and ccman.State = 0 and ccman.IsDelete = 0 ";
		if($time_id && $date) {
			$table .= " LEFT JOIN crt_capacity cctime ON ccman.CraftsmanId = cctime.CraftsmanId ";
			$where .= " AND cctime.TimeId = {$time_id} and cctime.Capacity = '{$date}' and NouseNum > 0 ";
		}
		switch($order) {
			case 4: //价格
				$orderby = ' order by total_price asc, good_rate desc ';
				break;
			case 3: //距离
				if($lat && $lng) {
					$orderby = ' order by distance asc, good_rate desc ';
				}else{
					$orderby = ' order by good_rate desc, total_price asc ';
				}
				break;
			case 1: //好评率
				$orderby = ' order by good_rate desc, total_price asc ';
				break;
			case 2: //完成单量
				$orderby = ' order by service_num desc, good_rate desc ';
				break;
			default:
				$orderby = ' order by good_rate desc, total_price asc';
		}
		$limit = "limit {$startnum}, {$endnum} ";
		$sql = $field.$table.$where.$orderby.$limit;
		$user_info = M()->query($sql);
		if($user_info) {
			//查询来源
			$source_arr = $this->getCraftSource();
			foreach($user_info as &$value) {
				$value['source'] = $source_arr[$value['source']];
			}
		}else{
			$user_info = array();
		}
		return $user_info;
	 }
	 
	 /*
	  * XXX来源
	  * @access private
	  * @return multitype
	  */
	  public function getCraftSource() {
	  	$info = M('sys_dictionary')->where(array('Type'=>4))->select();
	  	if($info) {
	  		foreach($info as $value) {
	  			$data[$value['DisValue']] = $value['DisKey'];
	  		}
	  	}else{
	  		$data = array();
	  	}
	  	return $data;
	  }
	  
	  /*
	   * 获取时间id
	   * @access public
	   * @param  datetime $time 预约时间
	   * @return number
	   */
	   public function getTimeId($time) {
	   	  if(empty($time)) {
	   	  	return false;
	   	  }
	   	  $date = date('Y-m-d', strtotime($time));
	   	  $prd_servicetime = M('prd_servicetime')->where('IsDelete = 0')->select();
	   	  $time_id = 0;
	   	  if(is_array($prd_servicetime) && $prd_servicetime) {
	   	  	foreach($prd_servicetime as $value) {
	   	  		if(strtotime($date.' '.$value['StartTime'].':00')<=strtotime($time) && strtotime($date.' '.$value['EndTime'].':00')>=strtotime($time)) {
	   	  			$time_id = $value['TimeId'];
	   	  			break;
	   	  		}
	   	  	}
	   	  }
	   	  return $time_id;
	   }
	   
	   /*
	    * 时间id信息
	    * @access public
	    * @param  int    $time_id
	    * @return mixed
	    */
	    public function getTimeIdInfo($time_id) {
	    	   if(empty($time_id)) {
	    	   	return false;
	    	   }
	    	   $time_id_info = M('prd_servicetime')->find($time_id);
	    	   return $time_id_info;
	    }
	    
	    /*
	     * 判断产品有没有上架
	     * @access public
	     * @param  int     $pro_id  产品id
	     * @param  int     $city_id 城市id
	     * @return boolean
	     */
	     public function isShelves($pro_id, $city_id) {
	     	    if(!($pro_id && $city_id)) {
	     	    	 return false;
	     	    }
	     	    $is_open = $this->isOpen($city_id);
	     	    if(empty($is_open)) {
	     	    	 return false;
	     	    }
	     	    $where = array(
	     	    	'ppc.`CityId`'=>$city_id,
	     	    	'ppc.`ProductId`'=>$pro_id,
	     	    	'ppinfo.IsDelete'=>0,
	     	    	'ppinfo.IsShelves'=>1
	     	    );
	     	    $product_id = M()->table('prd_product_city ppc')
	     	                     ->join(' left join prd_productinfo ppinfo on ppc.`ProductId` = ppinfo.`ProductId`')
	     	                     ->join(' left join prd_promotion pp on ppinfo.`ProductId` == pp.`ProductId')
	     	                     ->where($where)
	     	                     ->getField('ppinfo.`ProductId` as product_id')
	     	    if($product_id) {
	     	    	return true;
	     	    }else{
	     	    	return false;
	     	    }
	     }
	     
	     /*
	      * 订单活动明细
	      * @access public 
	      * @param  int    $active_id
	      * @param  int    $order_id
	      * @return mixed
	      */
	      public function addOrderActive($active_id, $order_id) {
	      	     if($active_id && $order_id) {
	      	     	 $data['OrderId']     = $order_id;
	      	     	 $data['ActiveId']    = $active_id;
	      	     	 $data['CreaterTime'] = date('Y-m-d H:i:s');
	      	     	 $id                  = M('ord_order_activeId')->add($data);
	      	     	 if(empty($id)) {
	      	     	 	//订单活动表日志
	      	     	 	return false;
	      	     	 }else{
	      	     	 	return $id;
	      	     	 }
	      	     }else{
	      	     	   return false;
	      	     }
	      }
	      
	      /*
	       * 订单套餐明细
	       * @access public
	       * @param  int    $order_id
	       * @param  int    $package_id
	       * @return mixed
	       */
	       public function addPackage($var, $package_id='') {
	       	      $order_id = $var['order_id'];
	       	      $package_id = (int)$package_id;
	       	      $order_id   = (int)$order_id;
	       	      if(empty($order_id)) {
	       	      	       return false;
	       	      }
	       	      if($package_id) {
	       	      	 $package_info = M()->table('prd_package pinfo')
	       	      	                    ->join('prd_packageitme pdetail on pinfo.PackageId = pdetail.PackageId')
	       	      	                    ->join('prd_packageinfo pp on detail.ProductId = pp.ProductId')
	       	      	                    ->where(array('pdetail.Packageid'=>$package_id))
	       	      	                    ->field('pinfo.Name as name, pinfo.Price as price, pdetail.ProductId as product_id, pdetail.Price as product_price, pp.ProductName as product_name')
	       	      	                    ->select();
	       	      	if($package_info) {
	       	      		foreach($package_info as $value) {
	       	      			$tmp['OrderId']     = $order_id;
	       	      			$tmp['PackageId']   = $package_id;
	       	      			$tmp['PackageName'] = $value['name'];
	       	      			$tmp['ProductId']   = $value['product_id'];
	       	      			$tmp['ProductName'] = $value['product_name'];
	       	      			$tmp['Price']       = $value['product_price'];
	       	      			$data[]             = $tmp;
	       	      		}
	       	      		$id = M('ord_order_item')->addAll($data);
	       	      	}
	       	      }else{
	       	      	   $data['OrderId']     = $order_id;
	       	      	   $data['ProductId']   = $var['product_id'];
	       	      	   $data['ProductName'] = $var['product_name'];
			   $data['Price']	= $var['product_price'];
			   $id = M('ord_order_item')->add($data);
	       	      }
	       	      if($id) {
	       	      	        return $id;
	       	      }else{
	       	      	        //订单套餐日志表
	       	      	        return false;
	       	      }
	       }
	       
	       /*
	        * 优惠券支付
	        * @access public
	        * @param  int     $order_id
	        * @param  int     $pay_way
	        * @param  string  $pay_code
	        * @return mixed
	        */
	        public function addPayPreferential($order_id, $pay_way, $pay_code) {
	        	if($order_id && $pay_way && $pay_code) {
	        		$data['OrderId']	= $order_id;
				$data['PayWay']		= $pay_way;
				$data['PayCode']	= $pay_code;
				$data['CreaterTime']    = date('Y-m-d H:i:s');
				$id = M('ord_pay')->add($data);
				if($id) {
					return $id;
				}else{
					//优惠券支付日志
					return false;
				}
	        	}else{
	        		return false;
	        	}
	        }
	        
	        /*
	         * 订单状态明细
	         * @access private
	         * @param  array   $var
	         * @return mixed
	         */
	         public function addOrderState($var) {
	                $data['OrderId'] = $order_id = $var['order_id'];
	                if(empty($order_id)) {
	                	return false;
	                }
	                $data['State']       = (int)$var['state'];
	                $data['Description'] = $var['state_desc'];
	                $data['CreaterBy']   = $var['cuid'] ? $var['cuid'] : 0;
	                $data['CreaterTime'] = $var['create_time'];
	                $id = M('ord_state')->add($data);
	                if($id) {
	                	return $id;
	                }else{
	                	//订单状态日志
	                	return false;
	                }
	         }
	         
	         /*
	          * 更改订单
	          * @access  public
	          * @param   int    $order_id
	          * @param   array  $arr
	          */
	          public function addUpdateOrder($order_id, $arr) {
	          	 $order_info = M('ord_orderinfo')->find($order_id);
	          	 $update_content = '';
	          	 foreach($order_info as $key=>$value) {
	          	 	if($arr[$key]!=>$value && isset($arr[$key])) {
	          	 		$update_content .= $key.'◎◎'.$arr[$key].'◎◎'.$value.'||';
	          	 	}
	          	 }
	          	 $data['OrderId'] = $order_id;
	          	 $data['UpdateContent'] = $update_content;
	          	 $data['CreaterTime'] = date('Y-m-d H:i:s');
	          	 $id = M('ord_update')->add($data);
	          	 if($id) {
	          	 	//更改订单日志
	          	 	return true;
	          	 }else{
	          	 	return false;
	          	 }
	          }
	          
	          /*
	           * 产品活动是否存在
	           * @access public 
	           * @param  int     $pro_id
	           * @param  int     $active_id
	           * @param  string  $datetime
	           * @return boolean
	           */
	           public function isProActive($pro_id, $active_id, $datetime='') {
	           	  $datetime = empty($datetime) ? date('Y-m-d H:i:s') : $datetime;
	           	  if(!($pro_id && $active_id)) {
	           	  	return false;
	           	  }
	           	  $where = array(
	           	  	'pap.ActiveId'=>$active_id,
	           	  	'pap.ProductId'=>$pro_id,
	           	  	'pa.StartTime'=>array('elt', $datetime),
	           	  	'pa.EndTime'=>array('glt', $datetime),
	           	  	'pa.IsDelete'=>0
	           	  );
	           	  $active_id = M()->table('prd_active_product pap')
	           	  		  ->join('prd_active pa on pa.ActiveId = pap.ActiveId')
	           	  		  ->where($where)
	           	  		  ->getField('pa.ActiveId');
	           	  if($active_id) {
	           	  	return true;
	           	  }else{
	           	  	return false;
	           	  }
	           }
	           
	           /*
	            * 收货地址
	            * @access  public
	            * @param   unknown $var
	            * @return  boolean
	            */
	            public function addAddress($var) {
	            	   $data['userId'] = $user_id = $var['user_id'];
	            	   if(empty($user_id)) {
	            	   	return false;
	            	   }
	            	   $data['IsDefault']   = $var['is_default'] ? 1 : 0;
	            	   $data['Name']        = $var['name'];
	            	   $data['Sex']	        = $var['sex']==0 ? 0 : 1;	//0女，1男
			   $data['Province']    = $var['province_id'];
			   $data['City']	= $var['city_id'];
			   $data['Area']	= ''; 				//暂时不用
			   $data['Address']	= $var['address'];
			   $data['Phone']	= $var['phone'];
			   $data['Phone1']	= $var['second_phone'];
			   $data['Email']	= $var['email'];
			   $data['CreaterTime']	= date('Y-m-d H:i:s');
			   $data['IsDelete']	= 0;
			   $id = M('cut_address')->add($data);
			   if($id) {
			   	return $id;
			   }else{
			   	return false;
			   }
	            }
	            
	
 }
