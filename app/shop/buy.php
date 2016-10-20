<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class buy extends AWS_CONTROLLER
{



public function get_access_rule()
{
	$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
	$rule_action['actions'] = array();
	
	return $rule_action;
}

function index_action()
{
	$itemid = $_GET['id'];
	$item_info = $this->model('shop')->get_item_info($itemid);
	if(!$item_info) HTTP::redirect('/shop/');
	//检查收货地址
	if(!$this->model('shop')->check_user_address($this->user_id))
	{
		H::redirect_msg(AWS_APP::lang()->_t('请先设置收货地址'), get_setting('base_url') . '/shop/address/');
	}
	$this->crumb(AWS_APP::lang()->_t('商品购买'), '/shop/buy/' . $itemid);
	TPL::assign('item_info', $item_info);
	TPL::output('shop/buy');
}


	
	
}
