<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class task_class extends AWS_MODEL
{
	var $search_task_total = 0;
	var $check_list_total = 0;
	var $task_list_total = 0;

//获取配置信息，用于获取只有一行的设置
function get_config()
{		
	if ($result = $this->fetch_all('task_set'))
	{
		foreach ($result[0] as $key => $val)
		{
			$settings[$key] = $val;
		}
		return $settings;
	}
	else
	{
		return false;
	}
}

//保存设置，针对只有只有一行的表
function set_config($vars)
{
	if (!is_array($vars))
	{
		return false;
	}
	
	foreach ($vars as $key => $val)
	{
		$this->update('task_set', 
		array
		(
			$key => $val
		), 
		"1 = '1'");
	}
	return true;
}

public function get_task_info($taskid)
{			
	return $this->fetch_row('task_list', 'taskid = ' . intval($taskid));	
}

public function get_task_join_info($taskid,$uid)
{			
	return $this->fetch_row('task_joins', 'taskid = ' . intval($taskid) . ' AND  uid = ' . intval($uid));	
}

public function get_check_list($page, $per_page)
{
	$where = array();
	$where[] = 'status = 1';
	if ($check_list = $this->fetch_page('task_list', implode(' AND ', $where), 'taskid DESC', $page, $per_page))
	{
		$this->check_list_total = $this->found_rows();
		return $check_list;
	}
}

public function search_task_list($page, $per_page, $taskid, $start_date = null, $end_date = null, $username = null, $rewardtype = null, $status = null, $flag)
{
	$where = array();
	
	if ($taskid)
	{
		$where[] = 'taskid = ' . $taskid;
	}
	
	if ($start_date)
	{
		$where[] = 'time >= ' . strtotime($start_date);
	}
	
	if ($end_date)
	{
		$where[] = 'time <= ' . strtotime('+1 day', strtotime($end_date));
	}
	
	if ($username)
	{
		$v = $this->model('account')->get_user_info_by_username($username);
		$where[] = 'uid = ' . intval($v['uid']);
	}
	
	if ($rewardtype)
	{
		$where[] = "rewardtype = '" . $rewardtype . "'";
	}
	
	if ($status)
	{
		$where[] = "status = '" . $status . "'";
	}
	
	if ($flag)
	{
		$where[] = "flag = '" . $flag . "'";
	}
	
	if ($task_info_list = $this->fetch_page('task_list', implode(' AND ', $where), 'taskid DESC', $page, $per_page))
	{
		$this->search_task_total = $this->found_rows();
		return $task_info_list;
	}
}

public function add_attach($file_name, $attach_access_key, $add_time, $file_location, $is_image = false)
{
	if ($is_image)
	{
		$is_image = 1;
	}
	
	return $this->insert('attach', array(
		'file_name' => htmlspecialchars($file_name), 
		'access_key' => $attach_access_key, 
		'add_time' => $add_time, 
		'file_location' => htmlspecialchars($file_location), 
		'is_image' => $is_image,
		'item_type' => 'task'
	));
}



public function remove_attach($id, $access_key)
{
	$attach = $this->fetch_row('attach', "id = " . intval($id) . " AND access_key = '" . $this->quote($access_key) . "'");
	
	if (! $attach)
	{
		return false;
	}
	
	$this->delete('attach', "id = " . intval($id) . " AND access_key = '" . $this->quote($access_key) . "'");
	
	if (!$this->fetch_row('attach', 'item_id = ' . $attach['item_id']))
	{
		$this->shutdown_update('task_list', array(
			'has_attach' => 0
		), 'taskid = ' . $attach['item_id']);
	}
	
	foreach(AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
	{
		@unlink(get_setting('upload_dir').'/task/' . date('Ymd', $attach['add_time']) . '/' . $val['w'] . 'x' . $val['h'] . '_' . $attach['file_location']);
	}
	
	@unlink(get_setting('upload_dir').'/task/' . date('Ymd', $attach['add_time']) . '/'  . $attach['file_location']);
	
	return true;
}

public function parse_attach_data($attach, $size)
{
	if (!$attach)
	{
		return false;
	}
	
	foreach ($attach as $key => $data)
	{		
		// Fix 2.0 attach time zone bug
		$date_dir = gmdate('Ymd', $data['add_time']);
		
		if (! file_exists(get_setting('upload_dir') . '/task/' . $date_dir . '/' . $data['file_location']))
		{
			$date_dir = gmdate('Ymd', ($data['add_time'] + 86400));
		}
		
		if (! file_exists(get_setting('upload_dir') . '/task/' . $date_dir . '/' . $data['file_location']))
		{
			$date_dir = gmdate('Ymd', ($data['add_time'] - 86400));
		}
			
		$attach_list[$data['id']] = array(
			'id' => $data['id'], 
			'is_image' => $data['is_image'], 
			'file_name' => $data['file_name'], 
			'access_key' => $data['access_key'], 
			'attachment' => get_setting('upload_url') . '/task/' . $date_dir . '/' . $data['file_location'],
		);
			
		if ($data['is_image'] == 1)
		{
			$attach_list[$data['id']]['thumb'] = get_setting('upload_url') . '/task/' . $date_dir . '/' . AWS_APP::config()->get('image')->attachment_thumbnail[$size]['w'] . 'x' . AWS_APP::config()->get('image')->attachment_thumbnail[$size]['h'] . '_' . $data['file_location'];
		}
	}
	
	return $attach_list;
}

public function get_attach($item_id, $size = 'square')
{
	$attach = $this->fetch_all('attach', "item_type = 'task' AND item_id = " . intval($item_id), "is_image DESC, id ASC");
	
	return $this->parse_attach_data($attach, $size);
}
	
public function get_attach_by_id($id)
{
	if ($attach = $this->fetch_row('attach', 'id = ' . intval($id)))
	{
		$data = $this->parse_attach_data(array($attach), 'square');
	
		return $data[$id];
	}
	
	return false;
}

public function get_file_class($file_name)
{
	switch (strtolower(H::get_file_ext($file_name)))
	{
		case 'jpg':
		case 'jpeg':
		case 'gif':
		case 'bmp':
		case 'png':
			return 'image';
			break;
		
		case '3ds' :
			return '3ds';
			break;
		
		case 'ace' :
		case 'zip' :
		case 'rar' :
		case 'gz' :
		case 'tar' :
		case 'cab' :
		case '7z' :
			return 'zip';
			break;
		
		case 'ai' :
		case 'psd' :
		case 'cdr' :
			return 'gif';
			break;
		
		default :
			return 'txt';
			break;
	}
}

public function publish_task($title, $rewardtype, $rewardnum, $message, $uid, $status, $topics = null, $attach_access_key = null, $create_topic = true)
{
	$settings = $this->model('task')->get_config();
	$user_info = $this->model('account')->get_user_info_by_uid($uid); 
	$username = $user_info['user_name'];
	if($settings['destype'] == 1)
	{
		if($user_info['integral'] < $settings['desnum']) H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您的积分账户不足！')));
		$colum2 = 'integral';
	}
	elseif($settings['destype'] == 2)
	{
		if($user_info['rmb'] < $settings['desnum']) H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您的现金账户不足！')));
		$colum2 = 'rmb';
	}
	
	if($rewardtype == 'point')
	{
		if($user_info['integral'] < $rewardnum) H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您的积分账户不足！')));
		$colum = 'integral';
	}
	elseif($rewardtype == 'rmb')
	{
		if($user_info['rmb'] < $rewardnum) H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您的现金账户不足！')));
		$colum = 'rmb';
	}
	
	if ($taskid = $this->insert('task_list', array(
		'uid' => intval($uid),
		'username' => $username,
		'title' => htmlspecialchars($title),
		'rewardtype' => $rewardtype,
		'rewardnum' => $rewardnum,
		'message' => htmlspecialchars($message),
		'status' => $status,
		'time' => time()
	)))
	{	
		//获取话题和用户信息
		if (is_array($topics))
		{
			foreach ($topics as $key => $topic_title)
			{
				$topic_id = $this->model('topic')->save_topic($topic_title, $uid, $create_topic);
				$this->model('topic')->save_topic_relation($uid, $topic_id, $taskid, 'task');
			}
		}
		
		if ($attach_access_key)
		{
			$this->model('task')->update_attach($taskid, $attach_access_key);
		}
	}
	//减少用户账户金额（发布费用）
	if($settings['destype'] == 1 || $settings['destype'] == 2)
	{
		$this->user_data_op($uid,$colum2,'des',$settings['desnum']);
	}
	
	//减少用户账户金额（悬赏）
	if($rewardtype == 'point' || $rewardtype == 'rmb')
	{
		$this->user_data_op($uid,$colum,'des',$rewardnum);
	}
	return $taskid;
}

//编辑任务
public function edit_task($taskid, $uid, $title, $message, $topics = null, $create_topic = true)
{
	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		return false;
	}
	
	$this->delete('topic_relation', 'item_id = ' . intval($taskid) . " AND `type` = 'task'");
	
	if (is_array($topics))
	{
		foreach ($topics as $key => $topic_title)
		{
			$topic_id = $this->model('topic')->save_topic($topic_title, $uid, $create_topic);
			
			$this->model('topic')->save_topic_relation($uid, $topic_id, $taskid, 'task');
		}
	}
	
	return $this->update('task_list', array(
		'title' => htmlspecialchars($title),
		'message' => htmlspecialchars($message)
	), 'taskid = ' . intval($taskid));
}


public function update_attach($item_id, $attach_access_key, $isjoin = FALSE)
{
	if (! $attach_access_key)
	{
		return false;
	}
	
	$database = $isjoin ? 'task_joins' : 'task_list';
	if($isjoin)
	{
		if ($this->update('attach', array(
		'item_id' => intval($item_id)
		), "item_type = 'task' AND item_id = 0 AND access_key = '" . $this->quote($attach_access_key) . "'"))
		{		
			return $this->shutdown_update('task_joins', array(
				'access_key' => $attach_access_key
			), 'id = ' . intval($item_id));
		}
	}
	else
	{
		if ($this->update('attach', array(
		'item_id' => intval($item_id)
		), "item_type = 'task' AND item_id = 0 AND access_key = '" . $this->quote($attach_access_key) . "'"))
		{		
			return $this->shutdown_update('task_list', array(
				'has_attach' => 1
			), 'taskid = ' . intval($item_id));
		}
	}
}

public function user_data_op($uid,$colum,$type,$num)
{
	if($type == 'add')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('users') . " SET $colum = $colum + $num WHERE uid = " . $uid);
	}
	elseif($type == 'des')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('users') . " SET $colum = $colum - $num WHERE uid = " . $uid);
	}
}

public function get_task_list($page, $per_page, $order_by, $where)
{
	return $this->fetch_page('task_list', $where, $order_by, $page, $per_page);
}

public function get_task_joins_list($page, $per_page, $order_by, $where)
{
	$arrinfo = $this->query_all('SELECT * FROM ' . $this->get_table('task_joins') . ' AS task_joins LEFT JOIN ' . $this->get_table('task_list') . " AS task ON task.taskid = task_joins.taskid WHERE " . implode(' AND ', $where) . " ORDER BY task." . $order_by, calc_page_limit($page, $per_page));
	return $arrinfo;
}

public function get_task_list_by_condition($where, $orderby, $limit)
{
	if ($task_list = $this->fetch_all('task_list', $where, $orderby, $limit))
	{				
		return $task_list;
	}	
	return array();
}

//浏览+1
public function add_task_pageview($taskid)
{
	$this->shutdown_query("UPDATE " . $this->get_table('task_list') . " SET pageview = pageview + 1
WHERE taskid = " . intval($taskid));
}

public function task_join($taskid, $message, $uid, $at_uid = null, $attach_access_key)
{	
	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		return false;
	}
	
	if ($join_info = $this->model('task')->get_task_join_info($taskid,$uid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您已经报名了此任务，请勿重复报名！')));
	}
	
	$settings = $this->get_config();
	$joinfee = $settings['joinfee'];
	if($joinfee)
	{
		$user_info = $this->model('account')->get_user_info_by_uid($uid); 
		if($user_info['integral'] < $joinfee )
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您的积分不足，参加此任务需要报名费 %s 个积分！',$joinfee)));
		}
		$this->user_data_op($uid,'integral','des',$joinfee);
	}
	
	$this->check_exist_task_user($uid);
	
	$comment_id = $this->insert('task_joins', array(
		'uid' => intval($uid),
		'taskid' => intval($taskid),
		'message' => htmlspecialchars($message),
		'add_time' => time()
	));
	
	$this->update('task_list', array(
		'joinnum' => $this->count('task_joins', 'taskid = ' . intval($taskid))
	), 'taskid = ' . intval($taskid));
	
	if ($attach_access_key)
	{
		$this->model('task')->update_attach($comment_id, $attach_access_key, 1);
	}
	return $comment_id;
}

public function get_task_info_by_ids($taskids)
{
	if (!is_array($taskids) OR sizeof($taskids) == 0)
	{
		return false;
	}
	
	array_walk_recursive($taskids, 'intval_string');
	
	if ($task_list = $this->fetch_all('task_list', "id IN(" . implode(',', $taskids) . ")"))
	{
		foreach ($task_list AS $key => $val)
		{
			$result[$val['id']] = $val;
		}
	}
	
	return $result;
}


public function get_comment_by_id($comment_id)
{
	if ($comment = $this->fetch_row('task_joins', 'id = ' . intval($comment_id)))
	{
		$comment_user_infos = $this->model('account')->get_user_info_by_uids(array(
			$comment['uid'],
			$comment['at_uid']
		));
		
		$comment['user_info'] = $comment_user_infos[$comment['uid']];
		$comment['at_user_info'] = $comment_user_infos[$comment['at_uid']];
	}
	
	return $comment;
}

public function get_comments($taskid, $page, $per_page)
{
	if ($comments = $this->fetch_page('task_joins', 'taskid = ' . intval($taskid), 'add_time ASC', $page, $per_page))
	{
		foreach ($comments AS $key => $val)
		{
			$comment_uids[$val['uid']] = $val['uid'];
			
			if ($val['at_uid'])
			{
				$comment_uids[$val['at_uid']] = $val['at_uid'];
			}
		}
		
		if ($comment_uids)
		{
			$comment_user_infos = $this->model('account')->get_user_info_by_uids($comment_uids);
		}
		
		foreach ($comments AS $key => $val)
		{
			$comments[$key]['user_info'] = $comment_user_infos[$val['uid']];
			$comments[$key]['at_user_info'] = $comment_user_infos[$val['at_uid']];
		}
	}
	return $comments;
}


public function task_hire($taskid,$joinuid)
{	
	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除！')));
	}
	
	if ($task_info['flag'] != 1)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该任务已指定雇员，请勿重复操作')));
	}
	
	$user_info = $this->model('account')->get_user_info_by_uid($joinuid);
	
	$update_data = array(
		'flag' => 2, 
		'joinuid' => $joinuid, 
		'joinusername' => $user_info['user_name'], 
		'jointime' => time(),
	);
	//中标数+1，进行中的任务数+1
	$this->task_hire_user_data_op($joinuid);
	$this->update('task_list',$update_data, "taskid = " . $taskid);
	H::ajax_json_output(AWS_APP::RSM(array('url' => get_setting('base_url') . '/?/task/mytask/flag-2')), '1', NULL); 
}

public function task_hire_user_data_op($uid)
{
	$this->shutdown_query("UPDATE " . $this->get_table('task_user') . " SET hirenum = hirenum + 1,dealingnum = dealingnum + 1 WHERE uid = " . $uid);
}


public function hand_over($taskid,$joinuid)
{	
	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除！')));
	}
	
	if ($task_info['joinuid'] != $joinuid)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您无权进行此操作')));
	}
	
	$update_data = array(
		'flag' => 3, 
		'finishtime' => time(),
	);
	$this->update('task_list',$update_data, "taskid = " . $taskid);
	H::ajax_json_output(AWS_APP::RSM(array('url' => get_setting('base_url') . '/?/task/intask/flag-3')), '1', NULL); 
}

public function task_confirm($taskid)
{	
	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除！')));
	}
	$settings = $this->get_config();
	//分配赏金
	$admin_reward = 0;
	if($task_info['rewardnum'])
	{
		$admin_reward = $task_info['rewardnum'] * $settings['adminrate'] / 100;
		if($task_info['rewardtype'] == 'point')
		{
			$admin_reward = round($admin_reward);
			$user_reward = $task_info['rewardnum'] - $admin_reward;
			$this->user_data_op($task_info['joinuid'],'integral','add',$user_reward);
		}
		elseif($task_info['rewardtype'] == 'rmb')
		{
			$admin_reward = round($admin_reward,2);
			$user_reward = $task_info['rewardnum'] - $admin_reward;
			$this->user_data_op($task_info['joinuid'],'rmb','add',$user_reward);
		}
		//echo '任务奖金 --> ' . $task_info['rewardnum'] . '  网站提成 -->' . $admin_reward . '  用户提成 -->' . $user_reward;
	}
	$this->task_confirm_user_data_op($task_info['joinuid']); //进行中的任务-1，完成任务数+1
	//更新数据库
	$update_data = array(
		'flag' => 4, 
		'user_reward' => $user_reward, 
		'admin_reward' => $admin_reward, 
		'confirmtime' => time(),
	);
	$this->update('task_list',$update_data, "taskid = " . $taskid);
	H::ajax_json_output(AWS_APP::RSM(array('url' => get_setting('base_url') . '/?/task/mytask/flag-4')), '1', NULL); 
}

public function task_confirm_user_data_op($uid)
{
	$this->shutdown_query("UPDATE " . $this->get_table('task_user') . " SET dealingnum = dealingnum - 1,finishnum = finishnum + 1 WHERE uid = " . $uid);
}

//任务用户表操作
public function task_user_data_op($uid,$colum,$type,$num)
{
	if($type == 'add')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('task_user') . " SET $colum = $colum + $num WHERE uid = " . $uid);
	}
	elseif($type == 'des')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('task_user') . " SET $colum = $colum - $num WHERE uid = " . $uid);
	}
}

public function check_exist_task_user($uid)
{
	if(!$this->fetch_one('task_user', 'uid', 'uid = ' . intval($uid)))
	{
		$user_info = $this->model('account')->get_user_info_by_uid($uid);
		$username = $user_info['user_name'];
		$this->insert('task_user', array(
		'uid' => $uid, 
		'username' => $username
		));
	}
}

public function task_comment($taskid,$rate,$comment) 
{
	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除！')));
	}
	if ($task_info['comment'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您已经评价了，请勿重复评价！')));
	}
	
	//更新数据库
	$update_data = array(
		'comment' => $comment, 
	);
	$this->task_comment_user_data_op($task_info['joinuid'],$rate);
	$this->update('task_list',$update_data, "taskid = " . $taskid);
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('评价成功！')));
}

public function task_comment_user_data_op($uid,$rate)
{	
	$info = $this->fetch_row('task_user', 'uid = ' . intval($uid));
	
	if($rate == 1)
	{
		$colum = 'bestnum';
	}
	elseif($rate == 2)
	{
		$colum = 'mednum';
	}
	elseif($rate == 3)
	{
		$colum = 'badnum';
	}
	
	$new_colum = $info[$colum] + 1;
	$commentnum = $info['commentnum'] + 1;
	
	$update_data = array(
		$colum => $new_colum, 
		'commentnum' => $commentnum, 
	);	
	$this->update('task_user',$update_data, "uid = " . $uid);
	$this->update_task_user_rate($uid);
}

//更新好评率
public function update_task_user_rate($uid)
{
	$info = $this->fetch_row('task_user', 'uid = ' . intval($uid));
	$bestnum = $info['bestnum'];
	$commentnum = $info['commentnum'];
	if($bestnum != 0 && $commentnum !=0)
	{
		$bestrate = $bestnum / $commentnum * 100;
		$bestrate = round($bestrate,2);
		$this->shutdown_query("UPDATE " . $this->get_table('task_user') . " SET bestrate = $bestrate WHERE uid = " . $uid);
	}
}

function get_task_user_info_by_uids($uids)
{
	if (! is_array($uids) OR sizeof($uids) == 0)
	{
		return false;
	}
	
	array_walk_recursive($uids, 'intval_string');
	
	$uids = array_unique($uids);
	
	if (sizeof($uids) == 1)
	{
		if ($one_user_info = $this->get_task_user_info_by_uid(end($uids), $attrib))
		{
			return array(
				end($uids) => $one_user_info
			);
		}
		
	}
	
	static $users_info;
	
	if ($users_info[implode('_', $uids) . '_attrib'])
	{
		return $users_info[implode('_', $uids) . '_attrib'];
	}
	else if ($users_info[implode('_', $uids)])
	{
		return $users_info[implode('_', $uids)];
	}
	
	if ($user_info = $this->fetch_all('task_user', "uid IN(" . implode(',', $uids) . ")"))
	{
		foreach ($user_info as $key => $val)
		{
			$data[$val['uid']] = $val;
			
			$query_uids[] = $val['uid'];
		}
		
		$users_info[implode('_', $uids)] = $data;
	}
	
	return $data;
}


function get_task_user_info_by_uid($uid)
{
	if (! $uid)
	{
		return false;
	}

	$sql = "SELECT * FROM " . $this->get_table('task_user') . " WHERE uid = " . intval($uid);

	if (! $user_info = $this->query_row($sql))
	{
		return false;
	}
	
	$users_info[$uid] = $user_info;
	
	return $user_info;
}

public function task_approval($taskid)
{
	if (!$info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除！')));
	}
	$this->shutdown_query("UPDATE " . $this->get_table('task_list') . " SET status = 2 WHERE taskid = " . $taskid);
}

public function task_decline($taskid)
{
	if (!$info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除！')));
	}
	//退回赃款
	if($info['rewardtype'] == 'point')
	{
		$this->user_data_op($info['uid'],'integral','add',$info['rewardnum']);
	}
	elseif($info['rewardtype'] == 'rmb')
	{
		$this->user_data_op($info['uid'],'rmb','add',$info['rewardnum']);
	}
	$this->shutdown_query("UPDATE " . $this->get_table('task_list') . " SET status = 3 WHERE taskid = " . $taskid);
}

//删除任务
public function remove_task($taskid)
{			
	if (!$task_info = $this->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除！')));
	}
	//删除参与者
	$this->delete('task_joins', "taskid = " . intval($taskid)); 
	//删除话题
	$this->delete('topic_relation', "type = 'task' AND item_id = " . intval($taskid)); 
	//删除附件
	if($task_info['has_attach'])
	{
		$attach = $this->fetch_all('attach', "item_type = 'task' AND item_id = " . intval($taskid)); 
		foreach($attach as $attach_key => $attach_val)
		{
			foreach(AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
			{
				@unlink(get_setting('upload_dir').'/task/' . date('Ymd', $attach[$attach_key]['add_time']) . '/' . $val['w'] . 'x' . $val['h'] . '_' . $attach[$attach_key]['file_location']);
			}
			@unlink(get_setting('upload_dir').'/task/' . date('Ymd', $attach[$attach_key]['add_time']) . '/' . $attach[$attach_key]['file_location']);
		}
		//删除附件记录
		$this->delete('attach', "item_type = 'task' AND item_id = " . intval($taskid)); 
	}
	//删除任务
	$this->delete('task_list', 'taskid = ' . intval($taskid));
}


public function get_task_list_by_topic_ids($page, $per_page, $order_by, $topic_ids)
{
	if (!$topic_ids)
	{
		return false;
	}
	
	if (!is_array($topic_ids))
	{
		$topic_ids = array(
			$topic_ids
		);
	}

	array_walk_recursive($topic_ids, 'intval_string');
	
	$result_cache_key = 'task_list_by_topic_ids_' . implode('_', $topic_ids) . '_' . md5($order_by . $page . $per_page);
	
	$found_rows_cache_key = 'task_list_by_topic_ids_found_rows_' . implode('_', $topic_ids) . '_' . md5($order_by . $page . $per_page);
		
	$where[] = 'topic_relation.topic_id IN(' . implode(',', $topic_ids) . ')';

	if (!$found_rows = AWS_APP::cache()->get($found_rows_cache_key))
	{
		$_found_rows = $this->query_row('SELECT COUNT(DISTINCT task.taskid) AS count FROM ' . $this->get_table('task_list') . ' AS task LEFT JOIN ' . $this->get_table('topic_relation') . " AS topic_relation ON task.taskid = topic_relation.item_id AND topic_relation.type = 'task' WHERE " . implode(' AND ', $where));
		
		$found_rows = $_found_rows['count'];
		
		AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
	}
	
	$this->task_list_total = $found_rows;
	
	if (!$result = AWS_APP::cache()->get($result_cache_key))
	{
		$result = $this->query_all('SELECT task.* FROM ' . $this->get_table('task_list') . ' AS task LEFT JOIN ' . $this->get_table('topic_relation') . " AS topic_relation ON task.taskid = topic_relation.item_id AND topic_relation.type = 'task' WHERE " . implode(' AND ', $where) . ' GROUP BY task.taskid ORDER BY task.' . $order_by, calc_page_limit($page, $per_page));
		
		AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
	}
	
	return $result;
}

public function get_task_joins_attach($access_key)
{
	$attach = $this->fetch_row('attach', "access_key = '" . $access_key . "'");
	$arr['file_name'] = $attach['file_name'];
	$arr['is_image'] = $attach['is_image'] ? TRUE : FALSE;
	$arr['attach_path'] = get_setting('upload_url') . '/task/' . date('Ymd',$attach['add_time']) . '/' . $attach['file_location'];
	return $arr;
}


}
?>