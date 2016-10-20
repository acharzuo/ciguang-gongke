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
	if(!$this->model('withdraw')->check_account($this->user_id))
	{
		H::redirect_msg(AWS_APP::lang()->_t('请先设置提现账号'), get_setting('base_url') . '/withdraw/set/'); 
	}
	$this->crumb(AWS_APP::lang()->_t('用户提现'), '/withdraw/');
	$info = $this->model('withdraw')->get_account($this->user_id);
	TPL::assign('info', $info);
	TPL::output('withdraw/withdraw');
}


function set_action()
{				
	$this->crumb(AWS_APP::lang()->_t('提现账号'), '/withdraw/set/');
	$info = $this->model('withdraw')->get_account($this->user_id);
	TPL::assign('info', $info);
	TPL::output('withdraw/set');
}
	

function list_action()
{				
	$this->crumb(AWS_APP::lang()->_t('充值记录'), '/withdraw/list/');
	TPL::output('withdraw/list');
}
	
	
}
