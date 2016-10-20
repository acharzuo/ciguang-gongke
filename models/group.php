<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class group_class extends AWS_MODEL
{
	var $check_list_total = 0;
	var $search_group_total = 0;
	var $search_thread_total = 0;

//获取配置信息，用于获取只有一行的设置
function get_config()
{		
	if ($result = $this->fetch_all('group_set'))
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
		$this->update('group_set', 
		array
		(
			$key => $val
		), 
		"1 = '1'");
	}
	return true;
}

/* 获取分类 JSON 数据 */
public function build_category_json($pid = 0, $prefix = '')
{
	if (!$category_list = $this->fetch_category($pid))
	{
		return false;
	}
	
	if ($prefix)
	{
		$_prefix = $prefix . ' ';
	}
	
	foreach ($category_list AS $category_id => $val)
	{
		$data[] = array(
			'id' => $category_id,
			'name' => $_prefix . $val['name'],
			'pid' => $val['pid']
		);
		
		if ($val['child'])
		{
			$prefix .= '-';
			
			$data = array_merge($data, json_decode($this->build_category_json($val['id'], $prefix), true));
		}
		
		unset($prefix);
	}
	return json_encode($data);
}

/* 获取分类数组 */
public function fetch_category($pid = 0)
{
	$category_list = array();
	
	if (!$category_all = $this->fetch_category_data($pid))
	{
		return $category_list;
	}
	
	foreach ($category_all AS $key => $val)
	{		
		$category_list[$val['id']] = array(
			'id' => $val['id'],
			'name' => $val['name'],
			'pid' => $val['pid']
		);
		
		if ($child_list = $this->fetch_category($val['id']))
		{
			$category_list[$val['id']]['child'] = $child_list;
		}
	}
	
	return $category_list;
}

public function fetch_category_data($pid = 0, $order = 'id ASC')
{
	static $category_list_all;
	
	if (!$category_list_all)
	{
		$category_list_all_query = $this->fetch_all('group_category', '', $order);
		
		if ($category_list_all_query)
		{
			foreach ($category_list_all_query AS $key => $val)
			{
				$category_list_all[$val['pid']][] = $val;
			}
		}
	}
	
	if (!$category_all = $category_list_all[$pid])
	{
		return array();
	}
	return $category_all;
}


/* 获取分类 HTML 数据 */
public function build_category_html($pid = 0, $selected_id = 0, $prefix = '', $child = true)
{
	$category_list = $this->fetch_category($pid);
	
	if (!$category_list)
	{
		return false;
	}
	
	if ($prefix)
	{
		$_prefix = $prefix . ' ';
	}
	
	foreach ($category_list AS $category_id => $val)
	{
		if ($selected_id == $val['id'])
		{
			$html .= '<option value="' . $category_id . '" selected="selected">' . $_prefix . $val['name'] . '</option>';
		}
		else
		{
			$html .= '<option value="' . $category_id . '">' . $_prefix . $val['name'] . '</option>';
		}
		
		if ($child AND $val['child'])
		{
			$prefix .= '-';
			
			$html .= $this->build_category_html($val['id'], $selected_id, $prefix);
		}
		else
		{
			unset($prefix);
		}
	}
		
	return $html;
}

//增加分类
public function add_category($name, $pid)
{
	return $this->insert('group_category', array(
		'name' => $name,
		'pid' => intval($pid),
	));
}

/* 获取某个分类数组信息 */
public function get_category_info($category_id)
{
	static $all_category;

	if (!$all_category)
	{
		if ($all_category_query = $this->fetch_all('group_category'))
		{
			foreach ($all_category_query AS $key => $val)
			{
				$all_category[$val['id']] = $val;
			}
		}
	}
	
	return $all_category[$category_id];
}

//更新分类
public function update_category($category_id, $update_data)
{
	return $this->update('group_category', $update_data, 'id = ' . intval($category_id));
}

//检查分类下是存在群组
public function is_group_exist($catid)
{
	return $this->model('group')->fetch_one('group_list', 'groupid', 'catid = ' . intval($catid) . ' OR pid = ' . intval($catid));
}

//删除分类
public function delete_category($catid)
{
	$childs = $this->model('group')->fetch_category_data($catid);
	
	if ($childs)
	{
		foreach($childs as $key => $val)
		{
			$this->delete_category($val['id']);
		}
	}
	return $this->delete('group_category', 'id = ' . intval($catid));
}

public function get_check_list($page, $per_page)
{
	$where = array();
	$where[] = 'status = 0';
	if ($check_list = $this->fetch_page('group_list', implode(' AND ', $where), 'groupid DESC', $page, $per_page))
	{
		$this->check_list_total = $this->found_rows();
		return $check_list;
	}
}

public function search_group_list($page, $per_page, $username, $keyword, $start_date = null, $end_date = null)
{
	$where = array();
	$where[] = ' status = 1 ';
	
	if ($username)
	{
		$v = $this->model('account')->get_user_info_by_username($username);
		$where[] = 'uid = ' . intval($v['uid']);
	}
	
	if ($keyword)
	{
		$where[] = "name like '%" . $keyword . "%'";
	}
	
	if ($start_date)
	{
		$where[] = 'time >= ' . strtotime($start_date);
	}
	
	if ($end_date)
	{
		$where[] = 'time <= ' . strtotime('+1 day', strtotime($end_date));
	}
	
	if ($group_info_list = $this->fetch_page('group_list', implode(' AND ', $where), 'groupid DESC', $page, $per_page))
	{
		$this->search_group_total = $this->found_rows();
		return $group_info_list;
	}
}

public function search_thread_list($page, $per_page, $groupid, $username, $keyword, $start_date = null, $end_date = null)
{
	$where = array();
	$where[] = ' 1 = 1 ';
	
	if ($groupid)
	{
		$where[] = "groupid = '" . $groupid . "'";
	}
	
	if ($username)
	{
		$v = $this->model('account')->get_user_info_by_username($username);
		$where[] = 'uid = ' . intval($v['uid']);
	}
	
	if ($keyword)
	{
		$where[] = "title like '%" . $keyword . "%'";
	}
	
	if ($start_date)
	{
		$where[] = 'time >= ' . strtotime($start_date);
	}
	
	if ($end_date)
	{
		$where[] = 'time <= ' . strtotime('+1 day', strtotime($end_date));
	}
	
	if ($thread_info_list = $this->fetch_page('group_thread', implode(' AND ', $where), 'threadid DESC', $page, $per_page))
	{
		$this->search_thread_total = $this->found_rows();
		return $thread_info_list;
	}
}

//通过审核
public function group_approval($groupid)
{
	if (!$info = $this->model('group')->get_group_info($groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('群组不存在或已删除！')));
	}
	$this->shutdown_query("UPDATE " . $this->get_table('group_list') . " SET status = 1 WHERE groupid = " . $groupid);
}

//拒绝审核，自动删除
public function group_decline($groupid)
{
	if (!$info = $this->model('group')->get_group_info($groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('群组不存在或已删除！')));
	}
	//删除群记录
	$this->delete('group_list', "groupid = " . intval($groupid)); 
	//删除群成员
	$this->delete('group_join', "groupid = " . intval($groupid));
	//删除图片
	@unlink(get_setting('upload_dir').'/group/' . $info['picurl']);
	//删除帖子
	$this->delete('group_thread', "groupid = " . intval($groupid)); 
}

//删除帖子
public function remove_thread($threadid)
{
	if (!$thread_info = $this->get_thread_info($threadid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('帖子不存在或已删除！')));
	}
	if (!$group_info = $this->get_group_info($thread_info['groupid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('异常错误！')));
	}
	//删除帖子
	$this->delete('group_thread', "threadid = " . intval($threadid)); 
	//更新发帖数量
	$this->group_data_op($thread_info['groupid'],'postnum','des',1);
	//删除评论
	$this->delete('group_thread_comments', "threadid = " . intval($threadid)); 
	//删除附件
	if($thread_info['has_attach'])
	{
		$attach = $this->fetch_all('attach', "item_type = 'thread' AND item_id = " . intval($threadid)); 
		foreach($attach as $attach_key => $attach_val)
		{
			foreach(AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
			{
				@unlink(get_setting('upload_dir').'/thread/' . date('Ymd', $attach[$attach_key]['add_time']) . '/' . $val['w'] . 'x' . $val['h'] . '_' . $attach[$attach_key]['file_location']);
			}
			@unlink(get_setting('upload_dir').'/thread/' . date('Ymd', $attach[$attach_key]['add_time']) . '/' . $attach[$attach_key]['file_location']);
		}
		//删除附件记录
		$this->delete('attach', "item_type = 'thread' AND item_id = " . intval($threadid)); 
	}
}

//获取所有分类信息，以数组储存
public function get_all_category_info($pid = NULL)
{
	$all_category = $this->fetch_all('group_category');
	if(!$pid)
	{
		foreach ($all_category as $key => $val)
		{
			if($all_category[$key]['pid']) unset($all_category[$key]);	
		}
	}
	else
	{
		foreach ($all_category as $key => $val)
		{
			if($all_category[$key]['pid'] != $pid) unset($all_category[$key]);	
		}
	}
	return $all_category;
}

//检查当前用户是否允许创建群组，并返回“是否允许创建群组”以及“用户名”
public function check_group_create($uid)
{
	$user_info = $this->model('account')->get_user_info_by_uid($uid); 
	$settings = $this->get_config();
	if($settings['createfee'])
	{
		if($user_info['integral'] < $settings['createfee'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('创建群组需要扣除 %s 个金币，您的金币不足',$settings['createfee'])));
		}
		else
		{
			$this->user_data_op($uid,'integral','des',$settings['createfee']);
		}
	}
	$arr['ischeck'] = $settings['ischeck'];
	$arr['username'] = $user_info['user_name'];
	return $arr;
}

//用户表字段操作
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

//增加群组
public function group_create($arrdata)
{
	return $this->insert('group_list', $arrdata);
}

//获取群组信息
public function get_group_info($groupid)
{
	return $this->model('group')->fetch_row('group_list', 'groupid = ' . intval($groupid));
}

//更新群组封面
public function update_group_pic($groupid,$picurl)
{			
	$this->update('group_list',array ('picurl' => $picurl), "groupid = " . $groupid);
}

//更新群组
public function group_update($groupid,$updata_data)
{			
	$this->update('group_list',$updata_data, "groupid = " . $groupid);
}

//前台获取群组
public function get_group_list($page, $per_page, $order_by, $where)
{
	return $this->fetch_page('group_list', $where, $order_by, $page, $per_page);
}

//根据给定条件获取群组
public function get_group_list_by_condition($where, $orderby, $limit)
{
	if ($group_list = $this->fetch_all('group_list', $where, $orderby, $limit))
	{				
		return $group_list;
	}	
	return array();
}

//浏览+1
public function add_group_pageview($groupid)
{
	$this->shutdown_query("UPDATE " . $this->get_table('group_list') . " SET pageview = pageview + 1
WHERE groupid = " . intval($groupid));
}

//浏览+1
public function add_thread_pageview($threadid)
{
	$this->shutdown_query("UPDATE " . $this->get_table('group_thread') . " SET pageview = pageview + 1
WHERE threadid = " . intval($threadid));
}

public function get_group_joinners($groupid)
{
	return $this->fetch_all('group_join', 'groupid = ' . intval($groupid));
}

//判断是否已经加入群组
public function is_join_group($uid,$groupid)
{
	$info = $this->fetch_row('group_join', 'uid = ' . intval($uid) . ' AND groupid = ' . intval($groupid));
	//0=尚未加入，1=待审核，2=已审核
	if(!$info)
	{
		$val = 0;
	}
	elseif($info['status'] == 0)
	{
		$val = 1;
	}
	elseif($info['status'] == 1)
	{
		$val = 2;
	}
	return $val;
}

//加入群组
public function group_join($arrdata,$isredirect = TRUE)
{
	if (!$info = $this->get_group_info($arrdata['groupid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该群组不存在或已删除！')));
	}
	$this->insert('group_join', $arrdata);
	if($isredirect)
	{
		if($info['ischeck'])
		{	
			H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/group/group_join_check/groupid-' . $arrdata['groupid']))), '1', NULL);
		}
		else
		{
			$this->group_data_op($arrdata['groupid'],'joinnum','add',1);
			H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/group/group_join/groupid-' . $arrdata['groupid']))), '1', NULL);
		}
	}
}

//退出群组
public function group_exit($uid,$groupid)
{
	$this->delete('group_join', "groupid = " . intval($groupid) . " AND uid = " . $uid); 
	$this->group_data_op($groupid,'joinnum','des',1);
	H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/group/group_exit/groupid-' . $groupid))), '1', NULL);
}

//群组表字段操作
public function group_data_op($groupid,$colum,$type,$num)
{
	if($type == 'add')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('group_list') . " SET $colum = $colum + $num WHERE groupid = " . $groupid);
	}
	elseif($type == 'des')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('group_list') . " SET $colum = $colum - $num WHERE groupid = " . $groupid);
	}
}

//获取待审核数量
public function get_check_num($groupid)
{
	$info = $this->fetch_all('group_join', ' status = 0 AND groupid = ' . intval($groupid));
	return count($info);
}

//入群申请
public function check_people($uid,$groupid)
{
	$this->update('group_join',array ('status' => 1), "groupid = " . $groupid . " AND uid = " . $uid);
	$this->group_data_op($groupid,'joinnum','add',1);
	H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/group/group_manage/groupid-' . $groupid))), '1', NULL);
}

//删除群成员
public function kick_people($uid,$groupid)
{	
	$this->delete('group_join', "groupid = " . intval($groupid) . " AND uid = " . $uid); 
	$this->group_data_op($groupid,'joinnum','des',1);
	H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/group/group_manage/groupid-' . $groupid))), '1', NULL);
}

public function get_group_joins_list($page, $per_page, $order_by, $where)
{
	$arrinfo = $this->query_all('SELECT * FROM ' . $this->get_table('group_join') . ' AS joins LEFT JOIN ' . $this->get_table('group_list') . " AS grouplist ON grouplist.groupid = joins.groupid WHERE " . implode(' AND ', $where) . " ORDER BY grouplist." . $order_by, calc_page_limit($page, $per_page));
	return $arrinfo;
}

//获取帖子信息
public function get_thread_info($threadid)
{
	return $this->fetch_row('group_thread', 'threadid = ' . intval($threadid));
}

//获取群组帖子信息
public function get_group_thread_info_limit($where, $orderby, $limit)
{
	if ($thread_list = $this->fetch_all('group_thread', $where, $orderby, $limit))
	{				
		return $thread_list;
	}	
	return array();
}

public function get_group_thread_info_page($page, $per_page, $order_by, $where)
{
	return $this->fetch_page('group_thread', $where, $order_by, $page, $per_page);
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
		'item_type' => 'thread'
	));
}

public function update_attach($item_id, $attach_access_key)
{
	if (! $attach_access_key)
	{
		return false;
	}
	
	if ($this->update('attach', array(
		'item_id' => intval($item_id)
	), "item_type = 'thread' AND item_id = 0 AND access_key = '" . $this->quote($attach_access_key) . "'"))
	{		
		return $this->shutdown_update('group_thread', array(
			'has_attach' => 1
		), ' threadid = ' . intval($item_id));
	}
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
		$this->shutdown_update('group_thread', array(
			'has_attach' => 0
		), 'threadid = ' . $attach['item_id']);
	}
	
	foreach(AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
	{
		@unlink(get_setting('upload_dir').'/thread/' . date('Ymd', $attach['add_time']) . '/' . $val['w'] . 'x' . $val['h'] . '_' . $attach['file_location']);
	}
	
	@unlink(get_setting('upload_dir').'/thread/' . date('Ymd', $attach['add_time']) . '/' . $attach['file_location']);
	
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
		
		if (! file_exists(get_setting('upload_dir') . '/thread/' . $date_dir . '/' . $data['file_location']))
		{
			$date_dir = gmdate('Ymd', ($data['add_time'] + 86400));
		}
		
		if (! file_exists(get_setting('upload_dir') . '/thread/' . $date_dir . '/' . $data['file_location']))
		{
			$date_dir = gmdate('Ymd', ($data['add_time'] - 86400));
		}
			
		$attach_list[$data['id']] = array(
			'id' => $data['id'], 
			'is_image' => $data['is_image'], 
			'file_name' => $data['file_name'], 
			'access_key' => $data['access_key'], 
			'attachment' => get_setting('upload_url') . '/thread/' . $date_dir . '/' . $data['file_location'],
		);
			
		if ($data['is_image'] == 1)
		{
			$attach_list[$data['id']]['thumb'] = get_setting('upload_url') . '/thread/' . $date_dir . '/' . $data['file_location'];
		}
	}
	return $attach_list;
}

public function get_attach($item_id, $size = 'square')
{
	$attach = $this->fetch_all('attach', "item_type = 'thread' AND item_id = " . intval($item_id), "is_image DESC, id ASC");
	
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

public function publish_thread($groupid, $title, $message, $uid, $attach_access_key = null)
{
	$user_info = $this->model('account')->get_user_info_by_uid($uid); 
	$username = $user_info['user_name'];
	
	$insert_data = array(
		'groupid' => intval($groupid),
		'uid' => intval($uid),
		'username' => $username,
		'title' => $title,
		'message' => $message,
		'time' => time(),
		'replytime' => time(),
	);
	
	if ($threadid = $this->insert('group_thread', $insert_data))
	{			
		if ($attach_access_key)
		{
			$this->update_attach($threadid, $attach_access_key);
		}
	}
	$this->group_data_op($groupid,'postnum','add',1);
	return $threadid;
}

//编辑帖子
public function edit_thread($threadid, $title, $message)
{
	return $this->update('group_thread', array(
		'title' => htmlspecialchars($title),
		'message' => htmlspecialchars($message)
	), 'threadid = ' . intval($threadid));
}

//回贴
public function save_comment($threadid, $message, $uid, $at_uid = null, $comment_id = null)
{
	$user_info = $this->model('account')->get_user_info_by_uid($uid);
	$username = $user_info['user_name'];
	if($at_uid)
	{
		$at_user_info = $this->model('account')->get_user_info_by_uid($at_uid);
		$at_user_name = $at_user_info['user_name'];
		
	}
	if($comment_id)
	{
		$comment_info = $this->get_comment_info($comment_id);
		$replytxt = mb_substr($comment_info['comment'], 0,40,'UTF-8');
	}
	$insert_data = array(
		'threadid' => intval($threadid),
		'uid' => intval($uid),
		'username' => $username,
		'at_uid' => intval($at_uid),
		'at_username' => $at_user_name,
		'replytxt' => $replytxt,
		'comment' => htmlspecialchars($message),
		'time' => time()
	);
	$replytime = time();
	$comment_id = $this->insert('group_thread_comments', $insert_data);
	$this->shutdown_query("UPDATE " . $this->get_table('group_thread') . " SET commentnum = commentnum + 1, reply_uid = $uid, reply_username = '$username', replytime = $replytime WHERE threadid = " . $threadid);
}

//获取回帖信息
public function get_comment_info($commentid)
{
	return $this->fetch_row('group_thread_comments', 'id = ' . intval($commentid));
}

//获取群组id
public function get_ids_info_by_commentid($commentid)
{
	$comment_info = $this->fetch_row('group_thread_comments', 'id = ' . intval($commentid));
	$thread_info = $this->get_thread_info($comment_info['threadid']);
	$group_info = $this->get_group_info($thread_info['groupid']);
	$arr['threadid'] = $comment_info['threadid'];
	$arr['groupid'] = $group_info['groupid'];
	return $arr;
}

//获取回帖列表
public function get_comments_page($page, $per_page, $threadid)
{
	return $this->fetch_page('group_thread_comments', 'threadid = ' . intval($threadid), 'time ASC', $page, $per_page);
}

//删除回帖
public function remove_comment($commentid)
{
	$ids = $this->get_ids_info_by_commentid($commentid);
	//删除回帖
	$this->delete('group_thread_comments', "id = " . intval($commentid)); 
	//更新回复数量
	$this->shutdown_query("UPDATE " . $this->get_table('group_thread') . " SET commentnum = commentnum - 1 WHERE threadid = " . $ids['threadid']);
}

//计算楼层
public function get_comment_level($commentid)
{
	return $this->query_row('SELECT COUNT(DISTINCT comments.id) AS count FROM ' . $this->get_table('group_thread_comments') . ' AS comments WHERE id < ' . $commentid);
}

//加精
public function recommend($threadid,$flag)
{
	$this->shutdown_query("UPDATE " . $this->get_table('group_thread') . " SET recommend = $flag WHERE threadid = " . $threadid);
}



}
?>