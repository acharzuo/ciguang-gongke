<?php
/* *
 * 功能：即时到账交易接口接入页
 * 版本：3.3
 * 修改日期：2012-07-23。
 */
header("Content-type:text/html;charset=utf-8");

if (!defined('IN_ANWSION'))
{
	die;
}

class alipayapi extends AWS_CONTROLLER
{
	
//即时到账提交
function index_action()
{
	require_once("lib/alipay_submit.class.php");
	//配置信息
	$settings = $this->model('charge')->get_config(); 
	$alipay_config['partner']		= 	$settings['alipayid'];
	$alipay_config['key']			= 	$settings['alipaykey'];
	$alipay_config['sign_type']     = 	strtoupper('MD5');
	$alipay_config['input_charset']	= 	strtolower('utf-8');
	$alipay_config['cacert']    	= 	getcwd().'\\cacert.pem';
	$alipay_config['transport']    	= 	'http';
	
	//支付类型
	$payment_type = "1";
	
	//服务器异步通知页面路径
	$notify_url = get_setting('base_url') . "/app/alipay/notify_url_fast.php";
	//需http://格式的完整路径，不能加?id=123这类自定义参数

	//页面跳转同步通知页面路径
	$return_url = get_setting('base_url') . "/app/alipay/return_url_fast.php";
	//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

	//卖家支付宝帐户
	$seller_email = $_POST['WIDseller_email'];
	//必填

	//商户订单号
	$out_trade_no = $_POST['WIDout_trade_no'];
	//商户网站订单系统中唯一订单号，必填

	//订单名称
	$subject = get_setting('site_name') . "账户充值";
	//必填

	//付款金额
	$total_fee = $_POST['WIDtotal_fee'];
	//必填

	//订单描述
	$body = $_POST['WIDbody'];
	
	//商品展示地址
	$show_url = $_POST['WIDshow_url'];
	//需以http://开头的完整路径，例如：http://www.xxx.com/myorder.html

	//防钓鱼时间戳
	$anti_phishing_key = "";
	//若要使用请调用类文件submit中的query_timestamp函数

	//客户端的IP地址
	$exter_invoke_ip = "";
	//非局域网的外网IP地址，如：221.0.0.1

	//构造要请求的参数数组，无需改动
	$parameter = array(
			"service" => "create_direct_pay_by_user",
			"partner" => trim($alipay_config['partner']),
			"payment_type"	=> $payment_type,
			"notify_url"	=> $notify_url,
			"return_url"	=> $return_url,
			"seller_email"	=> $seller_email,
			"out_trade_no"	=> $out_trade_no,
			"subject"	=> $subject,
			"total_fee"	=> $total_fee,
			"body"	=> $body,
			"show_url"	=> $show_url,
			"anti_phishing_key"	=> $anti_phishing_key,
			"exter_invoke_ip"	=> $exter_invoke_ip,
			"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
	);
	//建立请求
	$alipaySubmit = new AlipaySubmit($alipay_config);
	$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "自动提交中……请勿刷新或关闭此页面！");
	echo $html_text;
}

//手机支付提交
function mobile_action()
{
	require_once("lib_mobile/alipay_submit.class.php");
	//配置信息
	$settings = $this->model('charge')->get_config(); 
	$alipay_config['partner']				= 	$settings['alipayid'];
	$alipay_config['key']					= 	$settings['alipaykey'];
	//$alipay_config['private_key_path']		= 	'app/alipay/key/rsa_private_key.pem';
	//$alipay_config['ali_public_key_path']	= 	'app/alipay/key/alipay_public_key.pem';
	$alipay_config['sign_type']    			= 	'MD5';
	$alipay_config['input_charset']			= 	'utf-8';
	$alipay_config['cacert']    			= 	getcwd().'\\cacert.pem';
	$alipay_config['transport']   			= 	'http';
	//print_r($alipay_config);
	//返回格式
	$format = "xml";
	$v = "2.0";
	
	//请求号
	$req_id = rand(1111,9999).date('Ymdhis');
	
	//服务器异步通知页面路径
	$notify_url = get_setting('base_url') . "/app/alipay/notify_url_mobile.php";
	//需http://格式的完整路径，不能加?id=123这类自定义参数

	//页面跳转同步通知页面路径
	$call_back_url = get_setting('base_url') . "/app/alipay/return_url_mobile.php";
	//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
	
	//操作中断返回地址
	$merchant_url = get_setting('base_url') . "/app/alipay/merchant_url.php";

	//卖家支付宝帐户
	$seller_email = $_POST['WIDseller_email'];
	//必填

	//商户订单号
	$out_trade_no = $_POST['WIDout_trade_no'];
	//商户网站订单系统中唯一订单号，必填

	//订单名称
	$subject = get_setting('site_name') . "账户充值";
	//必填

	//付款金额
	$total_fee = $_POST['WIDtotal_fee'];
	//必填
	
	//请求业务参数详细
	$req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . $seller_email . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url></direct_trade_create_req>';

	//构造要请求的参数数组，无需改动
	$para_token = array(
		"service" => "alipay.wap.trade.create.direct",
		"partner" => trim($alipay_config['partner']),
		"sec_id" => trim($alipay_config['sign_type']),
		"format"	=> $format,
		"v"	=> $v,
		"req_id"	=> $req_id,
		"req_data"	=> $req_data,
		"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
	);
	//print_r($para_token);
	//建立请求
	$alipaySubmit = new AlipaySubmit($alipay_config);
	$html_text = $alipaySubmit->buildRequestHttp($para_token);
	//URLDECODE返回的信息
	$html_text = urldecode($html_text);
	
	//解析远程模拟提交后返回的信息
	$para_html_text = $alipaySubmit->parseResponse($html_text);
	//获取request_token
	$request_token = $para_html_text['request_token'];
	
	
	//**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************
	
	//业务详细
	$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
	//必填
	
	//构造要请求的参数数组，无需改动
	$parameter = array(
			"service" => "alipay.wap.auth.authAndExecute",
			"partner" => trim($alipay_config['partner']),
			"sec_id" => trim($alipay_config['sign_type']),
			"format"	=> $format,
			"v"	=> $v,
			"req_id"	=> $req_id,
			"req_data"	=> $req_data,
			"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
	);
	
	//建立请求
	$alipaySubmit = new AlipaySubmit($alipay_config);
	$html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');
	echo $html_text;
}

}
?>