<?php
namespace Reportsystem\Controller;
use Think\Controller;
class ApiCommController extends Controller {

	protected $log_status = false;
	protected $log_url	  = '';
	protected $log_data   = array();

	//写入日志
	public function comWlog($log_data=array(), $log_url='') {
		if(empty($log_url)) {
			$log_url = C('WWW_LOG_URL').ACTION_NAME.date(Ymd).'.log';
		}
		$this->log_url = $log_url;
		$this->log_dat = $log_data;
		if($this->log_status) {
			wlog($this->log_url, $this->log_data);
		}
	}

	//消息提示
	public function apiMessageReturn($status, $data=NULL) {
		switch ($status) {
			case 200:
				$message = 'success';
				break;
			case 300:
				$message = 'Lack of parameter';
				break;
			case 10001:
				$message = '账号密码有误';
				break;
			case 10002:
				$message = '用户使用的设备与绑定的设备不一致';
				break;
			case 10003:
				$message = '重置密码失败';
				break;
			case 10004:
				$message = '已经绑定过设备';
				break;
			case 10005:
				$message = '解除绑定审核中';
				break;
			case 10006:
				$message = '锁定设备失败';
				break;
			case 10007:
				$message = '用户未绑定设备';
				break;
			case 10008:
				$message = '用户密码和初始密码一致';
				break;
			case 20001:
				$message = '产品信息未找到';
				break;
			case 20002:
				$message = '产品易被上报';
				break;
			case 20003:
				$message = '产品上报失败';
				break;
			case 30001:
				$message = '优惠码不存在';
				break;
			case 30002:
				$message = '优惠码上报失败';
				break;
			case 30003:
				$message = '门店不存在';
				break;
			case 40001:
				$message = '申请解除绑定提交失败';
				break;
			case 50001:
				$message = '没有上报过门店';
				break;
			case 50002:
				$message = '用户未绑定sn';
				break;
			case 50003:
				$message = '更新门店上传信息失败';
				break;
		}
		$hash['stauts']  = $stauts;
		$hash['message'] = $message;
		if(isset($data)) {
			$hash['data'] = $data;
		}

		//记日志
		$this->comWlog($hash);

		if(I('get.parse')=='echo_info') {
			pp($hash);
		}
		$this->ajaxReturn($hash);
	}
}
