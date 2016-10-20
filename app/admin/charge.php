<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class charge extends AWS_ADMIN_CONTROLLER
{
	
	
public function index_action()
{
	$this->crumb(AWS_APP::lang()->_t('系统设置'), 'admin/charge/');
	TPL::assign('setting', $this->model('charge')->get_config());
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(901));
	TPL::output('admin/charge/set');
}
	
public function save_action()
{		
	define('IN_AJAX', TRUE);	
	if ($_POST['isopen'])
	{
		//设置判断
		if (!$_POST['isrmb'] && !$_POST['ispoint'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('至少开启一个充值类型！')));
		}
		elseif ($_POST['ispoint'])
		{
			if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $_POST['pointrate']))
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('购买力必须为一个正整数！')));
		}
		//--------
		
		//接口判断
		if (!$_POST['isalipay'] && !$_POST['istenpay'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('支付宝接口和财付通接口至少选择一个！')));
		}
		else
		{
			if ($_POST['isalipay'])
			{
				if (!$_POST['alipayid'] || !$_POST['alipaykey'] || !$_POST['alipayaccount'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('支付宝接口信息不完整')));
				}
			}
			if ($_POST['istenpay'])
			{
				if (!$_POST['tenpayid'] || !$_POST['tenpaykey'] || !$_POST['tenpayaccount'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('财付通接口信息不完整')));
				}
			}
		}
		//-------
	}
	unset($_POST['_post_type']);
	$this->model('charge')->set_config($_POST);
	H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统设置修改成功')));
}

public function list_action()
{

	if ($this->is_post())
	{			
		foreach ($_POST as $key => $val)
		{
			if ($key == 'start_date' OR $key == 'end_date')
			{
				$val = base64_encode($val);
			}
			
			if ($key == 'username')
			{
				$val = rawurlencode($val);
			}
			
			$param[] = $key . '-' . $val;
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/?/admin/charge/list/' . implode('__', $param)
		), 1, null));
	}
	
	//echo $_GET['money_min']. ' - ' . $_GET['money_max'];
	$charge_list = $this->model('charge')->search_charge_list($_GET['page'], $this->per_page, $_GET['orderno'], base64_decode($_GET['start_date']), base64_decode($_GET['end_date']), $_GET['username'], $_GET['money_min'], $_GET['money_max'], $_GET['payname'], $_GET['paytype']);
	
	$total_rows = $this->model('charge')->search_charge_total;
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/charge/list/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('充值管理'), "admin/charge/list/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('keyword', $_GET['keyword']);
	TPL::assign('list', $charge_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(902));
	TPL::output('admin/charge/list');
}

public function charge_batch_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['orderids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要操作的订单')));
	}
	
	foreach ($_POST['orderids'] AS $key => $orderids)
	{
		$this->model('charge')->remove_charge($orderids);
	}
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}
	
	


}