<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|   
+---------------------------------------------------------------------------
*/


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

public function setup()
{
	$this->crumb(AWS_APP::lang()->_t('任务中心'), '/task/publish/');
}

public function publish_action()
{
	if ($_GET['id'])
	{
		if (!$task_info = $this->model('task')->get_task_info($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('任务不存在或已删除'));
		}
		
		if (!$this->user_info['permission']['edit_task'] && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
		{
			if ($task_info['uid'] != $this->user_id)
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个任务'), '/task/' . $_GET['id']);
			}
		}
		
		TPL::assign('task_info', $task_info);
		TPL::assign('task_topics', $this->model('topic')->get_topics_by_item_id($task_info['taskid'], 'task'));
	}
	else if (!$this->user_info['permission']['publish_task'])
	{
		H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布任务'));
	}
	else if ($this->is_post() AND $_POST['message'])
	{
		TPL::assign('article_info', array(
			'title' => $_POST['title'],
			'message' => $_POST['message']
		));
	}
	
	if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $task_info['uid'] == $this->user_id AND $_GET['id']) OR !$_GET['id'])
	{
		TPL::assign('attach_access_key', md5($this->user_id . time()));
	}
	
	TPL::assign('human_valid', human_valid('question_valid_hour'));
	
	TPL::import_js('js/publish_task.js');
	
	if (get_setting('advanced_editor_enable') == 'Y')
	{
		import_editor_static_files();
	}

	if (get_setting('upload_enable') == 'Y')
	{
		// fileupload
		TPL::import_js('js/fileupload.js');
	}
	$settings = $this->model('task')->get_config(); 
	TPL::assign('isreward', $settings['isreward']);
	TPL::assign('settings', $settings);
	TPL::output('task/publish');
}

function index_action()
{
	$taskid = $_GET['id'];
	$task_info = $this->model('task')->get_task_info($taskid);
	
	if ($task_info)
	{
		if ($task_info['has_attach'])
		{
			$task_info['attachs'] = $this->model('task')->get_attach($task_info['taskid'], 'min');
			
			$task_info['attachs_ids'] = FORMAT::parse_attachs($task_info['message'], true);
		}
		
		$task_user_info = $this->model('account')->get_user_info_by_uid($task_info['uid'], true);
		
		$task_info['message'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($task_info['message'])));
		if ($_GET['item_id'])
		{
			$comments[] = $this->model('task')->get_comment_by_id($_GET['item_id']);
		}
		else
		{
			$comments = $this->model('task')->get_comments($task_info['taskid'], $_GET['page'], 10);
			foreach ($comments AS $key => $val)
			{
				$comment_uids[$val['uid']] = $val['uid'];
			}
			$comment_users_info = $this->model('task')->get_task_user_info_by_uids($comment_uids); 
			foreach ($comments AS $key => $val)
			{
				$comments[$key]['user_taskinfo'] = $comment_users_info[$val['uid']];
				if($val['access_key'])
				{
					$comments[$key]['attach_info'] = $this->model('task')->get_task_joins_attach($val['access_key']);
				}
			}
		}
		
		if(!$task_info) HTTP::redirect('/task/');
		$this->model('task')->add_task_pageview($taskid);
		$this->crumb(AWS_APP::lang()->_t('任务详情'), '/task/' . $taskid);
		TPL::assign('comments', $comments);
		
		TPL::import_js('js/task_detail.js');
	
		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}
	
		if (get_setting('upload_enable') == 'Y')
		{
			// fileupload
			TPL::import_js('js/fileupload.js');
		}
		TPL::assign('attach_access_key', md5($this->user_id . time()));
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/task/id-' . $task_info['taskid']), 
			'total_rows' => $task_info['joinnum'],
			'per_page' => 10
		))->create_links());
		TPL::assign('task_info', $task_info);
		TPL::assign('task_user_info', $task_user_info);
		TPL::assign('task_topics', $this->model('topic')->get_topics_by_item_id($task_info['taskid'], 'task'));
		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $task_info['uid']));
		TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($task_info['uid'], $user['reputation'], 5));
		TPL::output('task/task_detail');
		
	}
	else
	{
		$rewardtype = $_GET['rewardtype'];
		$orderby = $_GET['orderby'];
		$where = " status = 2 ";
		if($rewardtype == "noreward")
		{
			$where .= " AND (rewardtype != 'rmb' AND rewardtype != 'point') ";
		}
		elseif($rewardtype == "rmb")
		{
			$where .= " AND rewardtype = 'rmb' ";
		}
		elseif($rewardtype == "point")
		{
			$where .= " AND rewardtype = 'point' ";
		}
		if(!$orderby)
		{
			$orderby = "time DESC";
		}
		else
		{
			$orderby = $orderby. " DESC";
		}
		
		$task_list = $this->model('task')->get_task_list($_GET['page'], get_setting('contents_per_page'), $orderby, $where);
		if ($task_list)
		{
			foreach ($task_list AS $key => $val)
			{
				$task_ids[] = $val['taskid'];
				
				$task_uids[$val['uid']] = $val['uid'];
			}
			
			$task_topics = $this->model('topic')->get_topics_by_item_ids($task_ids, 'task');
			$task_users_info = $this->model('account')->get_user_info_by_uids($task_uids); //根据任务的uid批量获取信息
			
			foreach ($task_list AS $key => $val)
			{
				$task_list[$key]['user_info'] = $task_users_info[$val['uid']];
			}
		}
		$task_list_total = $this->model('task')->found_rows();
		
		$hot_rmb_list = $this->model('task')->get_task_list_by_condition("status = 2 AND rewardtype = 'rmb'", $order = 'rewardnum DESC', 5);
		if ($hot_rmb_list)
		{
			foreach ($hot_rmb_list AS $key => $val)
			{
				$hot_rmb_list_uids[$val['uid']] = $val['uid'];
			}
			$hot_rmb_list_users_info = $this->model('account')->get_user_info_by_uids($hot_rmb_list_uids);
			
			foreach ($hot_rmb_list AS $key => $val)
			{
				$hot_rmb_list[$key]['user_info'] = $hot_rmb_list_users_info[$val['uid']];
			}
		}
		
		$hot_point_list = $this->model('task')->get_task_list_by_condition("status = 2 AND rewardtype = 'point'", $order = 'rewardnum DESC', 5);
		if ($hot_point_list)
		{
			foreach ($hot_point_list AS $key => $val)
			{
				$hot_point_list_uids[$val['uid']] = $val['uid'];
			}
			$hot_point_list_users_info = $this->model('account')->get_user_info_by_uids($hot_point_list_uids);
			
			foreach ($hot_point_list AS $key => $val)
			{
				$hot_point_list[$key]['user_info'] = $hot_point_list_users_info[$val['uid']];
			}
		}
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/task/id-' . $task_info['taskid']), 
			'total_rows' => $task_list_total,
			'per_page' => 10
		))->create_links());
		$this->crumb(AWS_APP::lang()->_t('任务列表'), '/task/');
		TPL::assign('task_list', $task_list);
		TPL::assign('task_topics', $task_topics);
		TPL::assign('hot_rmb_list', $hot_rmb_list);
		TPL::assign('hot_point_list', $hot_point_list);
		TPL::output('task/task_list');		
	}
}

function mytask_action()
{
	$rewardtype = $_GET['rewardtype'];
	$flag = $_GET['flag'];
	$where = " status = 2 AND uid = '" . $this->user_id . "' ";
	if($rewardtype == "noreward")
	{
		$where .= " AND (rewardtype != 'rmb' AND rewardtype != 'point') ";
	}
	elseif($rewardtype == "rmb")
	{
		$where .= " AND rewardtype = 'rmb' ";
	}
	elseif($rewardtype == "point")
	{
		$where .= " AND rewardtype = 'point' ";
	}
	if(!$flag)
	{
		$where .= " AND flag = '1' ";
	}
	else
	{
		$where .= " AND flag =  " . $flag;
	}
	
	$task_list = $this->model('task')->get_task_list($_GET['page'], get_setting('contents_per_page'), 'time DESC', $where);
	if ($task_list)
	{
		foreach ($task_list AS $key => $val)
		{
			$task_ids[] = $val['taskid'];
			
			$task_uids[$val['uid']] = $val['uid'];
		}
		
		$task_topics = $this->model('topic')->get_topics_by_item_ids($task_ids, 'task');
		$task_users_info = $this->model('account')->get_user_info_by_uids($task_uids);
		
		foreach ($task_list AS $key => $val)
		{
			$task_list[$key]['user_info'] = $task_users_info[$val['uid']];
		}
	}
	$task_list_total = $this->model('task')->found_rows();
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_js_url('/task/id-' . $task_info['taskid']), 
		'total_rows' => $task_list_total,
		'per_page' => 10
	))->create_links());
	$this->crumb(AWS_APP::lang()->_t('我发布的任务'), '/task/mytask/');
	TPL::assign('task_list', $task_list);
	TPL::assign('task_topics', $task_topics);
	TPL::output('task/my_task_list');		
}


function intask_action()
{
	$rewardtype = $_GET['rewardtype'];
	$flag = $_GET['flag'];
	$where[] = " task.status = 2 AND task_joins.uid = " . $this->user_id ;
	if($rewardtype == "noreward")
	{
		$where[] = " rewardtype != 'rmb' AND rewardtype != 'point' ";
	}
	elseif($rewardtype == "rmb")
	{
		$where[] = " rewardtype = 'rmb' ";
	}
	elseif($rewardtype == "point")
	{
		$where[] = " rewardtype = 'point' ";
	}
	if(!$flag)
	{
		$where[] = " flag = 1 ";
	}
	else
	{
		$where[] = " flag =  " . $flag;
	}
	
	$task_list = $this->model('task')->get_task_joins_list($_GET['page'], get_setting('contents_per_page'), 'time DESC', $where);
	if ($task_list)
	{
		foreach ($task_list AS $key => $val)
		{
			$task_ids[] = $val['taskid'];
			
			$task_uids[$val['uid']] = $val['uid'];
		}
		
		$task_topics = $this->model('topic')->get_topics_by_item_ids($task_ids, 'task');
		$task_users_info = $this->model('account')->get_user_info_by_uids($task_uids);
		
		foreach ($task_list AS $key => $val)
		{
			$task_list[$key]['user_info'] = $task_users_info[$val['uid']];
		}
	}
	$task_list_total = $this->model('task')->found_rows();
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_js_url('/task/id-' . $task_info['taskid']), 
		'total_rows' => $task_list_total,
		'per_page' => 10
	))->create_links());
	$this->crumb(AWS_APP::lang()->_t('我参加的任务'), '/task/intask/');
	TPL::assign('task_list', $task_list);
	TPL::assign('task_topics', $task_topics);
	TPL::output('task/intask_list');		
}

public function task_comment_load_action() 
{
	$taskid = $_GET['id'];
	TPL::assign('taskid', $taskid);
	TPL::output('task/task_comment');
}

//需要审核时，提示用户
public function publish_check_action()
{
	H::redirect_msg(AWS_APP::lang()->_t('任务发布成功，等待管理员审核！'), '/task/'); 
}


}