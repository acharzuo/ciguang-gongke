<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class charge_class extends AWS_MODEL
{
	var $search_charge_total = 0;

//生成订单
function create_order($uid,$money,$payname,$paytype)
{		
	$v = $this->model('account')->get_user_info_by_uid($uid); 
	$username = $v['user_name'];
	$orderno = time() . rand(11111,9999);
	$pointnum = 0;
	if($paytype == 'point')
	{
		//获取购买力
		$settings = $this->model('charge')->get_config(); 
		$pointnum = $settings['pointrate'] * $money;
	}
	$id = $this->insert('charge_list', 
	array(
		'orderno' => $orderno,
		'uid' => intval($uid),
		'username' => $username,
		'payname' => $payname,
		'paytype' => $paytype,
		'money' => $money,
		'pointnum' => $pointnum,
		'time' => time()
	));
	return $id;
}

public function search_charge_list($page, $per_page, $orderno, $start_date = null, $end_date = null, $username = null, $money_min = null, $money_max = null, $payname = null, $paytype = null)
{
	$where = array();
	
	if ($orderno)
	{
		$where[] = 'orderno = ' . $orderno;
	}
	
	if ($start_date)
	{
		$where[] = 'time >= ' . strtotime($start_date);
	}
	
	if ($end_date)
	{
		$where[] = 'time <= ' . strtotime('+1 day', strtotime($end_date));
	}
	
	if ($username)
	{
		$v = $this->model('account')->get_user_info_by_username($username);
		$uid = $v['uid'];
		$where[] = 'uid = ' . intval($uid);
	}
	
	if ($money_min)
	{
		$where[] = 'money >= ' . $money_min;
	}
	
	if ($money_max)
	{
		$where[] = 'money <= ' . $money_max;
	}
	
	if ($payname)
	{
		$where[] = "payname = '" . $payname . "'";
	}
	
	if ($paytype)
	{
		$where[] = "paytype = '" . $paytype . "'";
	}
	
	if ($charge_info_list = $this->fetch_page('charge_list', implode(' AND ', $where), 'orderid DESC', $page, $per_page))
	{
		$this->search_charge_total = $this->found_rows();
		return $charge_info_list;
	}
}

public function remove_charge($orderid)
{			
	return $this->delete('charge_list', 'orderid = ' . intval($orderid));	
}

public function get_order_info($orderid)
{			
	return $this->fetch_row('charge_list', 'orderid = ' . $orderid);	
}

/*/担保交易
public function goto_alipay($orderid)
{			
	if(!$order_info = $this->get_order_info(intval($orderid)))
	{
		H::redirect_msg(AWS_APP::lang()->_t('该订单不存在或已删除！'), get_setting('base_url') . '?/charge/list/'); 
	}
	if($order_info['status'] == 2)
	{
		H::redirect_msg(AWS_APP::lang()->_t('该订单已经充值过了！'), get_setting('base_url') . '?/charge/list/'); 
	}
	$settings = $this->model('charge')->get_config(); 
	if($order_info['payname'] == 'alipay')
	{
		$content = "<form id='alipayment' name='alipayment' action='?/alipay/alipayapi/' method='post'>\r\n";
		$content .= "<input type='hidden' name='WIDseller_email' value='".$settings['alipayaccount']."'/>\r\n";
		$content .= "<input type='hidden' name='WIDout_trade_no' value='".$order_info['orderno']."'/>\r\n";
		$content .= "<input type='hidden' name='WIDprice' value='".$order_info['money']."'/>\r\n";
		$content .= "</form>";
		//构造提交表单
		echo '<!DOCTYPE html>';
        echo '<html><head>';
        echo '<meta content="text/html;charset=utf-8" http-equiv="Content-Type" />';
        echo '</head>';
        echo '<body onload="javascript:document.alipayment.submit();">';
        echo $content;
        echo '</body></html>';
        exit();
	}
	elseif($order_info['payname'] == 'tenpay')
	{
		echo "tenpay";
	}
}*/

//即时到账
public function goto_alipay($orderid) 
{			
	if(!$order_info = $this->get_order_info(intval($orderid)))
	{
		H::redirect_msg(AWS_APP::lang()->_t('该订单不存在或已删除！'), get_setting('base_url') . '?/charge/list/'); 
	}
	if($order_info['status'] == 2)
	{
		H::redirect_msg(AWS_APP::lang()->_t('该订单已经充值过了！'), get_setting('base_url') . '?/charge/list/'); 
	}
	$settings = $this->model('charge')->get_config(); 
	if($order_info['payname'] == 'alipay')
	{
		$content = "<form id='alipayment' name='alipayment' action='?/alipay/alipayapi/' method='post'>\r\n";
		$content .= "<input type='hidden' name='WIDseller_email' value='".$settings['alipayaccount']."'/>\r\n";
		$content .= "<input type='hidden' name='WIDout_trade_no' value='".$order_info['orderno']."'/>\r\n";
		$content .= "<input type='hidden' name='WIDtotal_fee' value='".$order_info['money']."'/>\r\n";
		$content .= "</form>";
		//构造提交表单
		echo '<!DOCTYPE html>';
        echo '<html><head>';
        echo '<meta content="text/html;charset=utf-8" http-equiv="Content-Type" />';
        echo '</head>';
        echo '<body onload="javascript:document.alipayment.submit();">';
        echo $content;
        echo '</body></html>';
        exit();
	}
	elseif($order_info['payname'] == 'tenpay')
	{
		echo "tenpay";
	}
}

public function goto_alipay_mobile($orderid)
{			
	if(!$order_info = $this->get_order_info(intval($orderid)))
	{
		H::redirect_msg(AWS_APP::lang()->_t('该订单不存在或已删除！'), get_setting('base_url') . '?/charge/list/'); 
	}
	if($order_info['status'] == 2)
	{
		H::redirect_msg(AWS_APP::lang()->_t('该订单已经充值过了！'), get_setting('base_url') . '?/charge/list/'); 
	}
	$settings = $this->model('charge')->get_config(); 
	if($order_info['payname'] == 'alipay')
	{
		$content = "<form id='alipayment' name='alipayment' action='?/alipay/alipayapi/mobile/' method='post' target='_blank'>\r\n";
		$content .= "<input type='hidden' name='WIDseller_email' value='".$settings['alipayaccount']."'/>\r\n";
		$content .= "<input type='hidden' name='WIDout_trade_no' value='".$order_info['orderno']."'/>\r\n";
		$content .= "<input type='hidden' name='WIDtotal_fee' value='".$order_info['money']."'/>\r\n";
		$content .= "</form>";
		//构造提交表单
		echo '<!DOCTYPE html>';
        echo '<html><head>';
        echo '<meta content="text/html;charset=utf-8" http-equiv="Content-Type" />';
        echo '</head>';
        echo '<body onload="javascript:document.alipayment.submit();">';
        echo $content;
        echo '</body></html>';
        exit();
	}
	elseif($order_info['payname'] == 'tenpay')
	{
		echo "tenpay";
	}
}

//获取配置信息，用于获取只有一行的设置
function get_config()
{		
	if ($result = $this->fetch_all('charge_set'))
	{
		foreach ($result[0] as $key => $val)
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

//保存设置，针对只有只有一行的表
function set_config($vars)
{
	if (!is_array($vars))
	{
		return false;
	}
	
	foreach ($vars as $key => $val)
	{
		$this->update('charge_set', 
		array
		(
			$key => $val
		), 
		"1 = '1'");
	}
	return true;
}

}
?>