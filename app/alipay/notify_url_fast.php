<?php
require_once('../../system/init.php');
require_once("lib/alipay_notify.class.php");

class myReturn 
{
	var $db = '';
	var $tablepre = '';
	function myReturn() 
	{
		require_once(AWS_PATH . '/config/database.php');
		$this->tablepre = $config['prefix'];
		$this->db = Zend_Db::factory($config['driver'] , $config['master']);
	}
	
	function get_settings() 
	{		
		if ($result = $this->db->fetchRow("SELECT * FROM {$this->tablepre}charge_set WHERE 1 = 1"))
		{
			foreach ($result as $key => $val)
			{
				$settings[$key] = $val;
			}
			return $settings;
		}
		else
		{
			return false;
		}
	}
	
	//充值成功
	public function charge_successful($orderno)
	{			
		$order_info = $this->db->fetchRow("SELECT * FROM {$this->tablepre}charge_list WHERE orderno = " . $orderno);
		if($order_info['status']==1)
		{
			$uid = $order_info['uid'];
			$money = $order_info['money'];
			$pointnum = $order_info['pointnum'];
			$this->db->query("UPDATE {$this->tablepre}charge_list SET status = 2 WHERE orderno = " . $orderno);
			if($order_info['paytype'] == 'rmb')
			{
				$this->db->query("UPDATE {$this->tablepre}users SET rmb = rmb + {$money} WHERE uid = " . $uid);
			}
			elseif($order_info['paytype'] == 'point')
			{
				$this->db->query("UPDATE {$this->tablepre}users SET integral = integral + {$pointnum} WHERE uid = " . $uid);
			}
			return true;
		}
		return false;
	}
}

		$obj = new myReturn();
		$settings = $obj->get_settings();
		//配置信息
		$alipay_config['partner']		= 	$settings['alipayid'];
		$alipay_config['key']			= 	$settings['alipaykey'];
		$alipay_config['sign_type']     = 	strtoupper('MD5');
		$alipay_config['input_charset']	= 	strtolower('utf-8');
		$alipay_config['cacert']    	= 	getcwd().'\\cacert.pem';
		$alipay_config['transport']    	= 'http';
		
		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		
		//验证成功
		if($verify_result) 
		{
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];
		
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
		
			//交易状态
			$trade_status = $_POST['trade_status'];
		
			if($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') 
			{
				$result = $obj->charge_successful($out_trade_no); 
			}
				
			echo "success";		//请不要修改或删除
		}
		else 
		{
			echo "fail";
			//logResult("notify--->fail");
		}




?>