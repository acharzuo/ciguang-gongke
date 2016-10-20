<?php
define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{


public function get_access_rule()
{
	$rule_action['rule_type'] = 'white';
	
	$rule_action['actions'] = array();
	
	return $rule_action;
}

function setup()
{
	HTTP::no_cache_header();
}

public function charge_action()
{		
	//充值金额
	if(!preg_match('/^\d+(?=\.{0,1}\d+$|$)/', $_POST['chargenum']))
	H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('充值金额必须为一个正数！')));
	if($_POST['chargenum'] < 0.1)
	H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('充值金额至少为0.1元！')));
	//生成支付订单
	$orderid = $this->model('charge')->create_order($this->user_id,$_POST['chargenum'],$_POST['payname'],$_POST['paytype']);
	$chargelisturl = get_setting('base_url') . '/?/charge/list/';
	H::ajax_json_output(AWS_APP::RSM(array('url' => $chargelisturl), '1', NULL));
}

public function charge_list_action()
{
	if ($log = $this->model('charge')->fetch_all('charge_list', 'uid = ' . $this->user_id, 'time DESC', (intval($_GET['page']) * 50) . ', 50'))
	{
		$url = get_setting('base_url') . '/?/charge/pay/';
		TPL::assign('log', $log);
		TPL::assign('url', $url);
	}
	TPL::output('charge/list_ajax');
}

public function charge_list_mobile_action()
{
	$log = $this->model('charge')->fetch_all('charge_list', 'uid = ' . $this->user_id, 'time DESC', (intval($_GET['page']) * 10));
	$url = get_setting('base_url') . '/?/charge/pay/id-';
	TPL::assign('log', $log);
	TPL::assign('url', $url);
    TPL::output('mm/ajax/charge_list');
}





	
}