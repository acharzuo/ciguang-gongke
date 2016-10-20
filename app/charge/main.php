<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class main extends AWS_CONTROLLER
{



public function get_access_rule()
{
	$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
	$rule_action['actions'] = array();
	
	return $rule_action;
}

function index_action()
{
	HTTP::redirect('/charge/charge/');
}

function charge_action()
{				
	if (is_mobile() AND HTTP::get_cookie('_ignore_ua_check') != 'TRUE')
	{
		HTTP::redirect('/mm/charge/');
	}
	TPL::assign('config', $this->model('charge')->get_config());
	$this->crumb(AWS_APP::lang()->_t('账户充值'), '/charge/charge/');
	TPL::output('charge/charge');
}

function list_action()
{				
	if (is_mobile() AND HTTP::get_cookie('_ignore_ua_check') != 'TRUE')
	{
		HTTP::redirect('/mm/charge_list/');
	}
	$this->crumb(AWS_APP::lang()->_t('充值记录'), '/charge/list/');
	TPL::output('charge/list');
}
	
function pay_action()
{	
	//echo '--'.$_GET['orderid'];
	if (! isset($_GET['id'])) HTTP::redirect('/charge/list/');
	$orderid = $_GET['id'];
	if($_GET['ismobile'])
	{
		$this->model('charge')->goto_alipay_mobile($orderid);
	}
	else
	{
		$this->model('charge')->goto_alipay($orderid);	
	}
}


	
	
}
