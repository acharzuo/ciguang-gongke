<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class withdraw extends AWS_ADMIN_CONTROLLER
{
	

public function index_action()
{
	$check_list = $this->model('withdraw')->get_check_list($_GET['page'], $this->per_page);
	
	$total_rows = $this->model('withdraw')->check_list_total;
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/withdraw/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('提现审核'), "admin/withdraw/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('list', $check_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1201));
	TPL::output('admin/withdraw/check_list');
}

public function check_action()
{
	$id = intval($_GET['id']);
	$info = $this->model('withdraw')->get_withdraw_info($id);
	if (!$info)
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('申请不存在或已删除')));
	}
		
	$this->crumb(AWS_APP::lang()->_t('提现处理'), "admin/withdraw/check/");	
	TPL::assign('info', $info);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1201));
	TPL::output('admin/withdraw/check');
}


public function check_batch_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['ids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要操作的订单')));
	}
	
	foreach ($_POST['ids'] AS $key => $id)
	{
		$this->model('withdraw')->delete_check($id,TRUE);
	}
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

//通过提现申请
public function check_submit_action()
{
	define('IN_AJAX', TRUE);
	$id = intval($_POST['id']);
	$status = intval($_POST['status']);
	$checkinfo = $_POST['checkinfo'];
	
	$info = $this->model('withdraw')->get_withdraw_info($id);
	
	if (!$info)
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('申请不存在或已删除')));
	}
	
	if (!$status)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择要进行的操作')));
	}
	
	if (trim($checkinfo) == '')
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入处理说明，比如交易号，拒绝原因等。')));
	}
	
	$update_data = array(
	'status' => $status, 
	'checktime' => time(),
	'checkinfo' => $checkinfo
	);
	$this->model('withdraw')->update('withdraw_list',$update_data, "id = " . $id);
	
	//如果是拒绝，进行相关滚回
	if($status == 3)
	{
		$this->model('withdraw')->user_data_op($info['uid'],'rmb','add',$info['num']);
	}
	
	H::ajax_json_output(AWS_APP::RSM(array(
		'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/withdraw/check/id-' . $id), 1, null));
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
			'url' => get_setting('base_url') . '/?/admin/withdraw/list/' . implode('__', $param)
		), 1, null));
	}
	
	$withdraw_list = $this->model('withdraw')->search_withdraw_list($_GET['page'], $this->per_page, base64_decode($_GET['start_date']), base64_decode($_GET['end_date']), $_GET['username'], $_GET['money_min'], $_GET['money_max'], $_GET['type'], $_GET['status']);
	
	$total_rows = $this->model('withdraw')->search_withdraw_total;
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/withdraw/list/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('任务管理'), "admin/withdraw/list/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('list', $withdraw_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1202));
	TPL::output('admin/withdraw/withdraw_list');
}

public function get_checkinfo_action()
{
	$id = intval($_GET['id']);
	$info = $this->model('withdraw')->get_withdraw_info($id);
	if (!$info)
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('记录不存在或已删除')));
	}
	H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t($info['checkinfo'])));
	
}

public function withdraw_batch_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['ids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要删除的记录')));
	}
	
	foreach ($_POST['ids'] AS $key => $id)
	{
		//只对已拒绝status=3的记录进行删除
		if(!$this->model('withdraw')->check_withdraw_status_3($id)) continue;
		$this->model('withdraw')->remove_withdraw($id);
	}
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}




}