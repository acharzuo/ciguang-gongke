<?php
require_once('../../system/init.php');
require_once("lib/alipay_notify.class.php");
?>
<!DOCTYPE HTML>
<html>
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
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
		$this->db->query("UPDATE {$this->tablepre}charge_list SET status = 2 WHERE orderno = " . $orderno);
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
		$alipay_config['transport']    	= 	'http';
		
		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
		
		//验证成功
		if($verify_result) 
		{				
			//商户订单号
			$out_trade_no = $_GET['out_trade_no'];
		
			//支付宝交易号
			$trade_no = $_GET['trade_no'];
		
			//交易状态
			$trade_status = $_GET['trade_status'];
		
			if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') 
			{
				$obj->charge_successful($out_trade_no); 
			}
			else 
			{
			  	echo "trade_status=".$_GET['trade_status'];
			}
			echo '<a href="/?/charge/list/">充值成功，请返回网站查看！</a>';
		}
		else 
		{
			//验证失败
			//如要调试，请看alipay_notify.php页面的verifyReturn函数
			echo '<a href="/?/charge/list/">充值失败，请返回网站查看！</a>' ;
		}
?>
        <title>支付宝充值结果</title>
	</head>
    <body>
    </body>
</html>