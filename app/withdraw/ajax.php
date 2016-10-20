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

public function set_action()
{		
	$province = $_POST['province'];
	$city = $_POST['city'];
	$bankname = $_POST['bankname'];
	$branch = $_POST['branch'];
	$cardno = $_POST['cardno'];
	$alipayaccount = $_POST['alipayaccount'];
	$tenpayaccount = $_POST['tenpayaccount'];
	$realname = $_POST['realname'];
	
	if($province || $city || $bankname || $branch || $cardno) $isbank = true;else $isbank = false;
	if(!$isbank && !$alipayaccount && !$tenpayaccount)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('至少填写一个提现账号！')));
	}
	
	if($province || $city || $bankname || $branch || $cardno) 
	{
		if($province == '' || $city == '' || $bankname == '' || $branch == '' || $cardno == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('银行账号信息填写不完整！')));
		} 
	}
	if(trim($realname) == '') H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写账户名！')));

	$this->model('withdraw')->set($this->user_id,$province,$city,$branch,$bankname,$cardno,$alipayaccount,$tenpayaccount,$realname);
	
	$turl = get_setting('base_url') . '/?/withdraw/set/';
	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), '1', NULL));
}

public function withdraw_action()
{		
	$type = $_POST['type'];
	$num = $_POST['num'];
	if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $num))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的提现金额，必须为数字！')));
	}
	if($num < 50)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('提现金额至少为50元！')));
	}
	$this->model('withdraw')->withdraw($this->user_id,$type,$num);
	$url = get_setting('base_url') . '/?/withdraw/list/';
	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), '1', NULL));
}


public function list_action()
{
	if ($log = $this->model('withdraw')->fetch_all('withdraw_list', 'uid = ' . $this->user_id, 'time DESC', (intval($_GET['page']) * 50) . ', 50'))
	{
		$url = get_setting('base_url') . '?/withdraw/list/';
		TPL::assign('log', $log);
		TPL::assign('url', $url);
	}
	TPL::output('withdraw/list_ajax');
}


public function get_checkinfo_action()
{		
	$id = $_POST['id'];
	$info = $this->model('withdraw')->fetch_one('withdraw_list', 'checkinfo', 'id = ' . intval($id));
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t($info)));
}


	
}