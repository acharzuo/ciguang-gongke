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
	if ($this->user_info['permission']['visit_site'])
	{
		$rule_action['actions'][] = 'index';
	}
	return $rule_action;
}

function index_action()
{				
	$groupid = $_GET['id'];
	$info = $this->model('group')->get_group_info($groupid);
	if ($info)
	{
		$orderby = $_GET['orderby'] ? $_GET['orderby'] . ' DESC' : 'replytime DESC';
		$where = 'groupid = ' . $groupid;
		if($_GET['recommend']) $where .= ' AND recommend = 1';
		$this->model('group')->add_group_pageview($groupid);
		$joiners = $this->model('group')->get_group_joinners($groupid);
		$is_join_group = $this->model('group')->is_join_group($this->user_id,$groupid);
		$check_num = $this->model('group')->get_check_num($groupid);
		
		$thread_list = $this->model('group')->get_group_thread_info_page($_GET['page'], get_setting('contents_per_page'), $orderby, $where);
		//print_r(count($thread_list));
		$thread_list_total = $this->model('group')->found_rows();
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/group/id-' . $groupid), 
			'total_rows' => $thread_list_total,
			'per_page' => 10
		))->create_links());

		$this->crumb(AWS_APP::lang()->_t($info['name'] . '_群组'), '/group/' . $groupid);
		TPL::import_js('js/group.js');
		TPL::assign('info', $info);
		TPL::assign('joiners', $joiners);
		TPL::assign('thread_list', $thread_list);
		TPL::assign('check_num', $check_num);
		TPL::assign('is_join_group', $is_join_group);
		TPL::output('group/detail');
		
	}
	else
	{
		$pid = $_GET['pid'];
		$catid = $_GET['catid'];
		$where = " status = '1' ";
		if($pid)
		{
			$where .= " AND pid = " . $pid;
			$child = $this->model('group')->get_all_category_info($pid);
		}
		if($catid)$where .= " AND catid = " . $catid;
		
		$group_list = $this->model('group')->get_group_list($_GET['page'], get_setting('contents_per_page'), 'time DESC', $where);
		$group_list_total = $this->model('group')->found_rows();
		if($group_list)
		{
			foreach($group_list as $key => $val)
			{
				$thread_list = $this->model('group')->get_group_thread_info_limit('groupid = ' . $group_list[$key]['groupid'], $order = 'time DESC', 5);
				$group_list[$key]['thread_list'] = $thread_list;
			}
		}
		$hot_post_list = $this->model('group')->get_group_list_by_condition('status = 1', $order = 'postnum DESC', 5);
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/group/'), 
			'total_rows' => $group_list_total,
			'per_page' => 10
		))->create_links());
		$parent = $this->model('group')->get_all_category_info();
		$this->crumb(AWS_APP::lang()->_t('群组'), '/group/list/');
		TPL::assign('parent', $parent);
		TPL::assign('child', $child);
		TPL::assign('group_list', $group_list);
		TPL::assign('hot_list', $hot_post_list);
		TPL::output('group/group_list');
	}
}

function my_group_action()
{				
	$pid = $_GET['pid'];
	$catid = $_GET['catid'];
	$where = " status = '1' AND uid = " . $this->user_id;
	if($pid)
	{
		$where .= " AND pid = " . $pid;
		$child = $this->model('group')->get_all_category_info($pid);
	}
	if($catid)$where .= " AND catid = " . $catid;
	
	$group_list = $this->model('group')->get_group_list($_GET['page'], get_setting('contents_per_page'), 'time DESC', $where);
	$group_list_total = $this->model('group')->found_rows();
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_js_url('/group/'), 
		'total_rows' => $group_list_total,
		'per_page' => 10
	))->create_links());
	$parent = $this->model('group')->get_all_category_info();
	$this->crumb(AWS_APP::lang()->_t('我创建的群组'), '/group/my_group/');
	TPL::assign('parent', $parent);
	TPL::assign('child', $child);
	TPL::assign('group_list', $group_list);
	TPL::output('group/my_group');
}

function in_group_action()
{				
	$pid = $_GET['pid'];
	$catid = $_GET['catid'];
	$where[] = " grouplist.status = '1' AND joins.uid = " . $this->user_id;
	if($pid)
	{
		$where[] = " pid = " . $pid;
		$child = $this->model('group')->get_all_category_info($pid);
	}
	if($catid)$where[] = " catid = " . $catid;
	
	$group_list = $this->model('group')->get_group_joins_list($_GET['page'], get_setting('contents_per_page'), 'time DESC', $where);
	$group_list_total = count($group_list);
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_js_url('/group/'), 
		'total_rows' => $group_list_total,
		'per_page' => 10
	))->create_links());
	$parent = $this->model('group')->get_all_category_info();
	$this->crumb(AWS_APP::lang()->_t('我加入群组'), '/group/in_group/');
	TPL::assign('parent', $parent);
	TPL::assign('child', $child);
	TPL::assign('group_list', $group_list);
	TPL::output('group/in_group');
}
	
function create_action()
{	
	$groupid = intval($_GET['id']);
	
	TPL::import_js('js/ajaxupload.js');
	if($info = $this->model('group')->get_group_info($groupid))
	{
		if (($info['uid'] != $this->user_id) && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个群组'), '/group/');
		}
		;
		$picurl = '/uploads/group/' . $info['picurl'];
		TPL::assign('info', $info);
		TPL::assign('picurl', $picurl);
		TPL::assign('cate_parent', $this->model('group')->build_category_html(0,$info['pid'],null,false));
		TPL::assign('cate_child', $this->model('group')->build_category_html($info['pid'],$item_info['catid'],null,false));
	}
	else
	{
		if (!$this->user_info['permission']['create_group'] && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限创建群组'), '/group/');
		}
		$this->crumb(AWS_APP::lang()->_t('创建群组'), '/group/create/');
		TPL::assign('cate_parent', $this->model('group')->build_category_html(0,0,null,false));
	}
	TPL::import_js('js/fileupload.js');
	TPL::output('group/group_create');
}

//增加群组，需审核的话进行跳转
public function group_create_check_action()
{
	H::redirect_msg(AWS_APP::lang()->_t('群组创建成功，等待管理员审核'), '/group/'); 
}

//加入群组，需审核的话进行跳转
public function group_join_check_action()
{
	H::redirect_msg(AWS_APP::lang()->_t('已提交入群请求，等待群主审核'), '/group/' . $_GET['groupid']); 
}

//加入群组时提示及跳转
public function group_join_action()
{
	$groupid = $_GET['groupid'];
	H::redirect_msg(AWS_APP::lang()->_t('您已成功加入该群'), '/group/' . $groupid); 
}

//退出群组时提示及跳转
public function group_exit_action()
{
	$groupid = $_GET['groupid'];
	H::redirect_msg(AWS_APP::lang()->_t('您已成功退出该群'), '/group/' . $groupid); 
}

//群成员管理
public function group_manage_action()
{
	$groupid = $_GET['groupid'];
	if(!$info = $this->model('group')->get_group_info($groupid))
	{
		H::redirect_msg(AWS_APP::lang()->_t('该群组不存在或已删除'), '/group/');
	}
	if($info['uid'] != $this->user_id && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
	{
		H::redirect_msg(AWS_APP::lang()->_t('对不起，你的权限不足'), '/group/');
	}
	$this->crumb(AWS_APP::lang()->_t('成员管理'), '/group/group_manage/');
	TPL::assign('groupid', $groupid);
	TPL::output('group/group_manage');
}

//发帖
public function publish_action()
{	
	if ($_GET['id'])
	{
		if (!$thread_info = $this->model('group')->get_thread_info($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('帖子不存在或已删除'));
		}
		
		$group_info = $this->model('group')->get_group_info($thread_info['groupid']);
		if($group_info['uid'] == $this->user_id) $isowner = TRUE; else $isowner = FALSE;
	
		if ($thread_info['uid'] != $this->user_id && !$isowner && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个帖子'), '/group/thread/' . $_GET['id']);
		}
		TPL::assign('info', $thread_info);
	}
	
	if(intval($_GET['groupid']))
	{
		if(!$group_info = $this->model('group')->get_group_info(intval($_GET['groupid'])))
		{
			H::redirect_msg(AWS_APP::lang()->_t('来路错误！'), '/group/');
		}
		$status = $this->model('group')->is_join_group($this->user_id,intval($_GET['groupid']));
		if($status != 2) H::redirect_msg(AWS_APP::lang()->_t('只有本组用户才能发帖！'), '/group/');
	}
	
	if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $thread_info['uid'] == $this->user_id AND $_GET['id']) OR !$_GET['id'])
	{
		TPL::assign('attach_access_key', md5($this->user_id . time()));
	}
	
	TPL::import_js('js/publish_thread.js');
	
	if (get_setting('advanced_editor_enable') == 'Y')
	{
		import_editor_static_files();
	}

	if (get_setting('upload_enable') == 'Y')
	{
		// fileupload
		TPL::import_js('js/fileupload.js');
	}
	TPL::output('group/publish');
}

function my_thread_action()
{				
	$this->crumb(AWS_APP::lang()->_t('我的帖子'), '/group/my_thread/');
	TPL::output('group/my_thread');
}
	
	
}
