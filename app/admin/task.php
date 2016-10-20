<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class task extends AWS_ADMIN_CONTROLLER
{
	
	
public function settings_action()
{
	$this->crumb(AWS_APP::lang()->_t('系统设置'), 'admin/task/settings/');
	$settings = $this->model('task')->get_config();
	if($settings['destype'] == 1)
	{
		$desnum = round($settings['desnum']); 
	}
	elseif($settings['destype'] == 2 || $settings['destype'] == 3)
	{
		$desnum = $settings['desnum']; 
	}
	TPL::assign('settings', $settings);
	TPL::assign('desnum', $desnum);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1101));
	TPL::output('admin/task/settings');
}
	
public function save_action()
{		
	define('IN_AJAX', TRUE);	

	//设置判断
	if ($_POST['destype'] == 1)
	{
		if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $_POST['desnum']))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('发布费类型为积分时，发布费必须为一个正整数！')));
	}
	elseif ($_POST['destype'] == 2)
	{
		if(!preg_match('/^\d+(?=\.{0,1}\d+$|$)/', $_POST['desnum']))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('发布费类型为人民币时，发布费必须为一个正数！')));
	}
	
	if ($_POST['enterfee'])
	{
		if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $_POST['enterfee']))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('浏览费必须为一个正整数！')));
	}
	
	if ($_POST['adminrate'])
	{
		if(!preg_match('/^\d+(?=\.{0,1}\d+$|$)/', $_POST['adminrate']))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('手续费费率必须为一个正数！')));
		if($_POST['adminrate'] > 100)
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('手续费率不能大于100！')));
	}

	unset($_POST['_post_type']);
	$this->model('task')->set_config($_POST);
	H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统设置修改成功')));
}

public function check_action()
{
	$check_list = $this->model('task')->get_check_list($_GET['page'], $this->per_page);
	
	$total_rows = $this->model('task')->check_list_total;
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/task/check/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('任务审核'), "admin/task/check/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('list', $check_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1102));
	TPL::output('admin/task/check_list');
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
			'url' => get_setting('base_url') . '/?/admin/task/list/' . implode('__', $param)
		), 1, null));
	}
	
	$task_list = $this->model('task')->search_task_list($_GET['page'], $this->per_page, $_GET['taskid'], base64_decode($_GET['start_date']), base64_decode($_GET['end_date']), $_GET['username'], $_GET['rewardtype'], $_GET['status'], $_GET['flag']);
	
	$total_rows = $this->model('task')->search_task_total;
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/task/list/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('任务管理'), "admin/task/list/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('list', $task_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1103));
	TPL::output('admin/task/task_list');
}

public function task_batch_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['taskids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要删除的任务')));
	}
	
	foreach ($_POST['taskids'] AS $key => $taskid)
	{
		$this->model('task')->remove_task($taskid);
	}
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

public function mulit_check_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['taskids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要审核的任务')));
	}
	
	$func = 'task_' . $_POST['batch_type'];
	
	foreach ($_POST['taskids'] AS $taskid)
	{
		$this->model('task')->$func($taskid);
	}

	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}
	
	


}