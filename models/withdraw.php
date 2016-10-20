<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class withdraw_class extends AWS_MODEL
{
	var $check_list_total = 0;
	var $search_withdraw_total = 0;

public function check_account($uid)
{
	if($this->fetch_one('withdraw_set', 'uid', 'uid = ' . intval($uid))) return true;else return false;
}

public function get_account($uid)
{
	return $this->fetch_row('withdraw_set', 'uid = ' . intval($uid));
}

public function get_withdraw_info($id)
{
	return $this->fetch_row('withdraw_list', 'id = ' . intval($id));
}

public function set($uid,$province,$city,$branch,$bankname,$cardno,$alipayaccount,$tenpayaccount,$realname)
{
	if(!$this->fetch_one('withdraw_set', 'uid', 'uid = ' . intval($uid)))
	{
		$user_info = $this->model('account')->get_user_info_by_uid($uid);
		$username = $user_info['user_name'];
		$insert_data = array(
		'uid' => $uid, 
		'username' => $username,
		'alipayaccount' => $alipayaccount, 
		'tenpayaccount' => $tenpayaccount, 
		'province' => $province, 
		'city' => $city, 
		'branch' => $branch, 
		'bankname' => $bankname, 
		'cardno' => $cardno, 
		'realname' => $realname
		);
		$this->insert('withdraw_set', array_filter($insert_data));
	}
	else
	{
		$update_data = array(
		'alipayaccount' => $alipayaccount, 
		'tenpayaccount' => $tenpayaccount, 
		'province' => $province, 
		'city' => $city, 
		'branch' => $branch, 
		'bankname' => $bankname, 
		'cardno' => $cardno, 
		'realname' => $realname
		);
		$this->update('withdraw_set',$update_data, "uid = " . $uid);
	}
}

public function withdraw($uid,$type,$num)
{
	$user_info = $this->model('account')->get_user_info_by_uid($uid);
	$account_info = $this->get_account($uid);
	if($user_info['rmb'] < $num)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您的现金账户余额不足！')));
	}
	if($type == 'alipay')
	{
		$typename = "支付宝";
		$account = $account_info['alipayaccount'];
	}
	elseif($type == 'tenpay')
	{
		$typename = "财付通";
		$account = $account_info['tenpayaccount'];
	}
	elseif($type == 'bank')
	{
		$typename = "银行卡";
		$account = '开户地：' . $account_info['province'] . ' -- ' .$account_info['city'] . ' -- ' .$account_info['branch'] . '<br/>' .
				   '开户行：' . $account_info['bankname'] . '<br/>' .
				   '卡号：' . $account_info['cardno'] . '<br/>' .
				   '开户名：' . $account_info['realname'] . '<br/>' ;
	}
	$insert_data = array(
	'uid' => $uid, 
	'username' => $user_info['user_name'],
	'type' => $typename, 
	'account' => $account, 
	'num' => $num, 
	'time' => time()
	);
	$this->insert('withdraw_list', $insert_data);
	$this->user_data_op($uid,'rmb','des',$num);
}

public function user_data_op($uid,$colum,$type,$num)
{
	if($type == 'add')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('users') . " SET $colum = $colum + $num WHERE uid = " . $uid);
	}
	elseif($type == 'des')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('users') . " SET $colum = $colum - $num WHERE uid = " . $uid);
	}
}

public function get_check_list($page, $per_page)
{
	$where = array();
	$where[] = 'status = 1';
	if ($check_list = $this->fetch_page('withdraw_list', implode(' AND ', $where), 'id DESC', $page, $per_page))
	{
		$this->check_list_total = $this->found_rows();
		return $check_list;
	}
}

public function delete_check($id,$isreturn = FALSE)
{
	if (!$info = $this->get_withdraw_info($id))
	{
		return;
	}
	//退回赃款
	if($isreturn)$this->user_data_op($info['uid'],'rmb','add',$info['num']);
	$this->delete('withdraw_list', 'id = ' . intval($id));	
}


public function search_withdraw_list($page,$per_page,$start_date,$end_date,$username,$money_min,$money_max,$type,$status )
{
	$where = array();
	
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
		$where[] = 'uid = ' . intval($v['uid']);
	}
	
	if ($money_min)
	{
		$where[] = 'num >= ' . $money_min;
	}
	
	if ($money_max)
	{
		$where[] = 'num <= ' . $money_max;
	}
	
	if ($type)
	{
		if($type == 1) $where[] = "type = '支付宝'";
		elseif($type == 2) $where[] = "type = '财付通'";
		elseif($type == 3) $where[] = "type = '银行卡'";
	}
	
	if ($status)
	{
		$where[] = "status = '" . $status . "'";
	}
	
	if ($withdraw_info_list = $this->fetch_page('withdraw_list', implode(' AND ', $where), 'id DESC', $page, $per_page))
	{
		$this->search_withdraw_total = $this->found_rows();
		return $withdraw_info_list;
	}
}


//检查记录是否是“已拒绝”
public function check_withdraw_status_3($id)
{			
	$status = $this->fetch_one('withdraw_list', 'status', 'id = ' . intval($id));
	if($status == 3) return true; else return false;
}

//删除订单
public function remove_withdraw($id)
{			
	$this->delete('withdraw_list', 'id = ' . intval($id));	
}


}
?>