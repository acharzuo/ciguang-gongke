<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class group extends AWS_ADMIN_CONTROLLER
{
	
	
public function index_action()
{
	$this->crumb(AWS_APP::lang()->_t('系统设置'), 'admin/group/');
	TPL::assign('settings', $this->model('group')->get_config());
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1301));
	TPL::output('admin/group/set');
}
	
public function save_action()
{		
	define('IN_AJAX', TRUE);	
	if ($_POST['createfee'])
	{
		if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $_POST['createfee']))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('创建费必须为一个正整数！')));
	}

	unset($_POST['_post_type']);
	$this->model('group')->set_config($_POST);
	H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统设置修改成功')));
}

public function category_action()
{
	$this->crumb(AWS_APP::lang()->_t('分类管理'), 'admin/group/category/');
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1302));
	TPL::assign('list', json_decode($this->model('group')->build_category_json(), true));
	TPL::assign('category_option', $this->model('group')->build_category_html(0, 0, null, false));
	TPL::output('admin/group/category');
}
	
//编辑分类页面载入
public function cate_edit_action()
{
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1302));
	$category = $this->model('group')->get_category_info($_GET['catid']);
	TPL::assign('category', $category);
	TPL::assign('category_option', $this->model('group')->build_category_html(0, $category['pid'], null, false));
	TPL::output('admin/group/category_edit');
}

//新增、编辑分类
public function cate_save_action()
{
	define('IN_AJAX', TRUE);
	
	$category_id = intval($_GET['catid']);
	$parent_id = intval($_POST['parent_id']);
	
	$category_list = $this->model('group')->fetch_category($category_id);
	
	if ($category_id > 0 AND $parent_id > 0 AND $category_list = $this->model('group')->fetch_category($category_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统允许最多二级分类, 当前分类下有子分类, 不能移动到其它分类')));
		}
		
	if (trim($_POST['name']) == '')
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入分类名称')));
	}
	
	//增加新分类
	if (!$category_id)
	{
		$category_id = $this->model('group')->add_category($_POST['name'], $parent_id);
	}
	
	$category = $this->model('group')->get_category_info($category_id);
	
	if ($category['id'] == $parent_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能设置当前分类为父级分类')));
	}
	
	$update_data = array(
		'name' => $_POST['name'], 
		'pid' => $parent_id,
	);
	
	$this->model('group')->update_category($category_id, $update_data);
	
	H::ajax_json_output(AWS_APP::RSM(array(
		'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/group/category/'
	), 1, null));
}
	
//删除分类
public function cate_remove_action()
{
	define('IN_AJAX', TRUE);
	
	$catid = intval($_POST['catid']);
	
	if ($this->model('group')->is_group_exist($catid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类下存在群组, 请先删除群组, 再删除当前分类')));
	}
	
	$this->model('group')->delete_category($catid);

	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}	

//群组审核
public function check_action()
{
	$check_list = $this->model('group')->get_check_list($_GET['page'], $this->per_page);
	
	$total_rows = $this->model('group')->check_list_total;
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/group/check/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('群组审核'), "admin/group/check/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('list', $check_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1303));
	TPL::output('admin/group/check_list');
}

//群组审核，批量操作
public function mulit_check_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['groupids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要审核的群组')));
	}
	
	$func = 'group_' . $_POST['batch_type'];
	
	foreach ($_POST['groupids'] AS $groupid)
	{
		$this->model('group')->$func($groupid);
	}

	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

//群组列表
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
			'url' => get_setting('base_url') . '/?/admin/group/list/' . implode('__', $param)
		), 1, null));
	}
	
	$group_list = $this->model('group')->search_group_list($_GET['page'], $this->per_page, $_GET['username'], $_GET['keyword'], base64_decode($_GET['start_date']), base64_decode($_GET['end_date']));
	
	$total_rows = $this->model('group')->search_group_total;
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/group/list/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('群组管理'), "admin/group/list/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('list', $group_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1304));
	TPL::output('admin/group/group_list');
}

//群组批量操作
public function group_batch_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['groupids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要删除的群组')));
	}
	
	foreach ($_POST['groupids'] AS $key => $groupid)
	{
		$this->model('group')->group_decline($groupid);
	}
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

//帖子列表
public function thread_action()
{
	if ($this->is_post())
	{			
		foreach ($_POST as $key => $val)
		{
			if ($key == 'start_date' OR $key == 'end_date')
			{
				$val = base64_encode($val);
			}
			
			if ($key == 'username' || $key == 'keyword')
			{
				$val = rawurlencode($val);
			}
			
			$param[] = $key . '-' . $val;
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/?/admin/group/thread/' . implode('__', $param)
		), 1, null));
	}
	
	$thread_list = $this->model('group')->search_thread_list($_GET['page'], $this->per_page, $_GET['groupid'], $_GET['username'], $_GET['keyword'], base64_decode($_GET['start_date']), base64_decode($_GET['end_date']));
	
	$total_rows = $this->model('group')->search_thread_total;
	
	if($thread_list)
	{
		foreach($thread_list as $key => $val)
		{
			$group_info = $this->model('group')->get_group_info($thread_list[$key]['groupid']);
			$thread_list[$key]['groupname'] = $group_info['name'];
		}
	}
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/group/thread/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('帖子管理'), "admin/group/thread/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('list', $thread_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1305));
	TPL::output('admin/group/thread_list');
}

//帖子批量操作
public function thread_batch_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['threadids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要删除的帖子')));
	}
	
	foreach ($_POST['threadids'] AS $key => $threadid)
	{
		$this->model('group')->remove_thread($threadid);
	}
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}


	


}