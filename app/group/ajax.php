<?php
define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{


public function get_access_rule()
{
	$rule_action['rule_type'] = 'white';
	
	$rule_action['actions'] = array();
	
	return $rule_action;
}

function setup()
{
	HTTP::no_cache_header();
}

//创建群组
public function group_create_action()
{		
	if (!$this->user_info['permission']['create_group']&& (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
	{			
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限创建群组')));
	}
	$isopen = intval($_POST['isopen']);
	$ischeck = intval($_POST['ischeck']);
	$pid = intval($_POST['pid']);
	$catid = intval($_POST['catid']);
	$name = $_POST['name'];
	$description = $_POST['description'];

	if(!$_FILES['attach']['name'])
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择封面图片')));
	
	if(!$pid || !$catid)
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择所属分类')));
	
	if(mb_strlen($name,'utf-8') < 1 || mb_strlen($name,'utf-8') > 10)
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('群名称长度为1~10个字')));
	
	if(mb_strlen($description,'utf-8') < 10 || mb_strlen($description,'utf-8') > 120)
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('群说明长度为10~120个字')));
	$arr = $this->model('group')->check_group_create($this->user_id);
	$isadmincheck = $arr['ischeck'];
	$username = $arr['username'];
	
	//上传图片
	if ($_FILES['attach']['name'])
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,png,gif',
			'upload_path' => get_setting('upload_dir') .  '/group/' . date('Ymd'),
			'is_image' => FALSE,
			'encrypt_name' => TRUE
		))->do_upload('attach');
		
		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
			{
				default:
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
				break;
				
				case 'upload_invalid_filetype':
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
					
				case 'upload_invalid_filesize':
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));
				
				break;
			}
		}
	
		if (! $upload_data = AWS_APP::upload()->data())
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
		}
		$picurl =  date('Ymd') . '/' . basename($upload_data['full_path']);
	}
	
	$status = $isadmincheck ? 0 : 1;

	$insert_data = array(
		'picurl' => $picurl,
		'pid' => $pid, 
		'catid' => $catid, 
		'isopen' => $isopen,
		'ischeck' => $ischeck,
		'name' => $name,
		'description' => $description,
		'uid' => $this->user_id,
		'username' => $username,
		'time' => time(),
		'status' => $status,
	);
	
	$groupid = $this->model('group')->group_create($insert_data);
	
	//创建者默认加入群组
	$insert_join_data = array(
		'groupid' => $groupid,
		'uid' => $this->user_id, 
		'username' => $username, 
		'jointime' => time(),
	);
	$this->model('group')->group_join($insert_join_data,$isredirect = FALSE);
	
	if($isadmincheck)
	{
		$url = get_js_url('/group/group_create_check/');
	}
	else
	{
		$url = get_js_url('/group/' . $groupid);
	}
	
	H::ajax_json_output(AWS_APP::RSM(array(
		'url' => $url
	), 1, null));
}

public function get_child_by_pid_action()
{
	$pid = intval($_GET['pid']);
	$content = $this->model('group')->build_category_html($pid, 0, null, false);
	H::ajax_json_output(AWS_APP::RSM(null, 1, $content));
}


public function upload_group_pic_action()
{
	if (!$info = $this->model('group')->get_group_info($_GET['id']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该群组不存在或已删除')));
	}
	
	AWS_APP::upload()->initialize(array(
		'allowed_types' => 'jpg,jpeg,png,gif',
		'upload_path' => get_setting('upload_dir') .  '/group/' . date('Ymd'),
		'is_image' => TRUE,
		'max_size' => get_setting('upload_avatar_size_limit')
	))->do_upload('aws_upload_file');
	
	if (AWS_APP::upload()->get_error())
	{
		switch (AWS_APP::upload()->get_error())
		{
			default:
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
			break;

			case 'upload_invalid_filetype':
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
			break;

			case 'upload_invalid_filesize':
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));
			break;
		}
	}

	if (! $upload_data = AWS_APP::upload()->data())
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
	}
	$picurl =  date('Ymd') . '/' . basename($upload_data['full_path']);
	
	$this->model('group')->update_group_pic($_GET['id'],  $picurl);
	@unlink(get_setting('upload_dir').'/group/' . $info['picurl']);
	
	echo htmlspecialchars(json_encode(array(
		'success' => true,
		'thumb' => get_setting('upload_url') . '/group/' . gmdate('Ymd') . '/' . basename($upload_data['full_path'])
	)), ENT_NOQUOTES);
}

//编辑群组
public function group_edit_action()
{		
	$groupid = intval($_POST['groupid']);
	if (!$info = $this->model('group')->get_group_info($groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该群组不存在或已删除')));
	}
	
	if (($info['uid'] != $this->user_id) && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
	{			
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个群组')));
	}
	
	$isopen = intval($_POST['isopen']);
	$ischeck = intval($_POST['ischeck']);
	$pid = intval($_POST['pid']);
	$catid = intval($_POST['catid']);
	$name = $_POST['name'];
	$description = $_POST['description'];

	if(!$pid || !$catid)
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择所属分类')));
	
	if(mb_strlen($name,'utf-8') < 1 || mb_strlen($name,'utf-8') > 10)
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('群名称长度为1~10个字')));
	
	if(mb_strlen($description,'utf-8') < 10 || mb_strlen($description,'utf-8') > 120)
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('群说明长度为10~120个字')));
	
	$updata_data = array(
		'pid' => $pid, 
		'catid' => $catid, 
		'isopen' => $isopen,
		'ischeck' => $ischeck,
		'name' => $name,
		'description' => $description,
	);
	$this->model('group')->group_update($groupid,$updata_data);	
	$url = get_js_url('/group/' . $groupid);	
	H::ajax_json_output(AWS_APP::RSM(array(
		'url' => $url
	), 1, null));
}

//加入群组
public function join_action()
{
	$groupid = intval($_GET['groupid']);

	if (!$info = $this->model('group')->get_group_info($groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该群组不存在或已删除！')));
	}
	
	if ($this->model('group')->is_join_group($this->user_id,$groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您已经加入了该群组！')));
	}
	$user_info = $this->model('account')->get_user_info_by_uid($this->user_id); 
	$status = 1;
	if($info['ischeck']) $status = 0;
	$insert_data = array(
		'groupid' => $groupid,
		'uid' => $this->user_id, 
		'username' => $user_info['user_name'], 
		'jointime' => time(),
		'status' => $status,
	);
	$this->model('group')->group_join($insert_data);
}

//退出群组
public function exit_action()
{
	$groupid = intval($_GET['groupid']);

	if (!$info = $this->model('group')->get_group_info($groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该群组不存在或已删除！')));
	}
	
	if ($info['uid'] == $this->user_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您是群主，不能退出创建的群组！')));
	}
	
	if (!$this->model('group')->is_join_group($this->user_id,$groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您尚未加入该群组！')));
	}
	$this->model('group')->group_exit($this->user_id,$groupid);
}

//成员管理
public function group_manage_action()
{
	if ($log = $this->model('group')->fetch_all('group_join', 'groupid = ' . $_GET['groupid'], 'jointime DESC', (intval($_GET['page']) * 50) . ', 50'))
	{
		$url = get_setting('base_url') . '?/group/group_manage/';
		TPL::assign('log', $log);
		TPL::assign('url', $url);
	}
	TPL::output('group/ajax_group_manage');
}

//入群审核
public function check_people_action()
{
	$groupid = intval($_GET['groupid']);
	$uid = intval($_GET['uid']);

	if (!$info = $this->model('group')->get_group_info($groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该群组不存在或已删除！')));
	}
	
	if ($info['uid'] != $this->user_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起，您的权限不足！')));
	}
	
	$status = $this->model('group')->is_join_group($uid,$groupid);
	
	if ($status != 1)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('异常错误，请联系管理员！' . $status)));
	}
	$this->model('group')->check_people($uid,$groupid);
}

//删除群成员
public function kick_people_action()
{
	$groupid = intval($_GET['groupid']);
	$uid = intval($_GET['uid']);

	if (!$info = $this->model('group')->get_group_info($groupid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该群组不存在或已删除！')));
	}
	
	if ($info['uid'] != $this->user_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起，您的权限不足！')));
	}
	
	if ($info['uid'] == $uid)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起，您不能删除您自己！')));
	}
	
	$this->model('group')->kick_people($uid,$groupid);
}

/**************************************** 附件相关 begin ****************************************/
public function attach_upload_action()
{
	if (get_setting('upload_enable') != 'Y')
	{
		die;
	}
	
	AWS_APP::upload()->initialize(array(
		'allowed_types' => get_setting('allowed_upload_types'),
		'upload_path' => get_setting('upload_dir') . '/thread/' . date('Ymd'),
		'is_image' => FALSE,
		'max_size' => get_setting('upload_size_limit')
	));
	
	if (isset($_GET['aws_upload_file']))
	{
		AWS_APP::upload()->do_upload($_GET['aws_upload_file'], file_get_contents('php://input'));
	}
	else if (isset($_FILES['aws_upload_file']))
	{
		AWS_APP::upload()->do_upload('aws_upload_file');
	}
	else
	{
		return false;
	}
			
	if (AWS_APP::upload()->get_error())
	{
		switch (AWS_APP::upload()->get_error())
		{
			default:
				die("{'error':'错误代码: " . AWS_APP::upload()->get_error() . "'}");
			break;
			
			case 'upload_invalid_filetype':
				die("{'error':'文件类型无效'}");
			break;	
			
			case 'upload_invalid_filesize':
				die("{'error':'文件尺寸过大, 最大允许尺寸为 " . get_setting('upload_size_limit') .  " KB'}");
			break;
		}
	}
	
	if (! $upload_data = AWS_APP::upload()->data())
	{
		die("{'error':'上传失败, 请与管理员联系'}");
	}
	
	if ($upload_data['is_image'] == 1)
	{
		foreach(AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
		{			
			$thumb_file[$key] = $upload_data['file_path'] . $val['w'] . 'x' . $val['h'] . '_' . basename($upload_data['full_path']);
			
			AWS_APP::image()->initialize(array(
				'quality' => 90,
				'source_image' => $upload_data['full_path'],
				'new_image' => $thumb_file[$key],
				'width' => $val['w'],
				'height' => $val['h']
			))->resize();	
		}
	}
	
	$attach_id = $this->model('group')->add_attach($upload_data['orig_name'], $_GET['attach_access_key'], time(), basename($upload_data['full_path']), $upload_data['is_image']);
	
	$output = array(
		'success' => true,
		'delete_url' => get_js_url('/group/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
			'attach_id' => $attach_id, 
			'access_key' => $_GET['attach_access_key']
		)))),
		'attach_id' => $attach_id,
		'attach_tag' => 'attach'
		
	);
	
	$attach_info = $this->model('group')->get_attach_by_id($attach_id);
	
	if ($attach_info['thumb'])
	{
		$output['thumb'] = $attach_info['thumb'];
	}
	else
	{
		$output['class_name'] = $this->model('group')->get_file_class(basename($upload_data['full_path']));
	}
	
	echo htmlspecialchars(json_encode($output), ENT_NOQUOTES);
}

public function attach_edit_list_action()
{
	if (!$thread_info = $this->model('group')->get_thread_info($_POST['threadid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('无法获取附件列表')));
	}
	
	if ($thread_info['uid'] != $this->user_id && (!$this->user_info['permission']['is_administortar'] || !$this->user_info['permission']['is_moderator']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个附件列表')));
	}
	
	if ($thread_attach = $this->model('group')->get_attach($_POST['threadid']))
	{
		foreach ($thread_attach as $attach_id => $val)
		{
			$thread_attach[$attach_id]['class_name'] = $this->model('group')->get_file_class($val['file_name']);
			
			$thread_attach[$attach_id]['delete_link'] = get_js_url('/group/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
				'attach_id' => $attach_id, 
				'access_key' => $val['access_key']
			))));
			
			$thread_attach[$attach_id]['attach_id'] = $attach_id;
			$thread_attach[$attach_id]['attach_tag'] = 'attach';
		}
	}
	
	H::ajax_json_output(AWS_APP::RSM(array(
		'attachs' => $thread_attach
	), 1, null));
}

public function remove_attach_action()
{
	$attach_info = H::decode_hash(base64_decode($_GET['attach_id']));
	
	$this->model('group')->remove_attach($attach_info['attach_id'], $attach_info['access_key']);
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}
/**************************************** 附件相关 end ****************************************/

public function publish_thread_action()
{
	if(!$group_info = $this->model('group')->get_group_info(intval($_POST['groupid'])))
	{
		H::redirect_msg(AWS_APP::lang()->_t('来路错误！'), '/group/');
	}
	$status = $this->model('group')->is_join_group($this->user_id,intval($_POST['groupid']));
	if($status != 2) H::redirect_msg(AWS_APP::lang()->_t('只有本组用户才能发帖！'), '/group/');
	
	if (empty($_POST['title']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入帖子标题')));
	}
	
	if (empty($_POST['message']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入帖子内容')));
	}

	if (!$this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($_POST['message']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
	}

	$threadid = $this->model('group')->publish_thread($_POST['groupid'], $_POST['title'], $_POST['message'], $this->user_id, $_POST['attach_access_key']);

	$url = get_js_url('/group/thread/' . $threadid);
	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), 1, null));

}

function edit_thread_action()
{
	if (!$thread_info = $this->model('group')->get_thread_info($_POST['threadid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('帖子不存在或已删除')));
	}
	
	$group_info = $this->model('group')->get_group_info($thread_info['groupid']);
	if($group_info['uid'] == $this->user_id) $isowner = TRUE; else $isowner = FALSE;
		
	if ($thread_info['uid'] != $this->user_id && !$isowner && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
	{			
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个帖子')));
	}
	
	if (!intval($_POST['groupid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('来路错误，请返回！')));
	}
	
	if (empty($_POST['title']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入帖子标题')));
	}
	
	if (empty($_POST['message']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入帖子内容')));
	}

	if (!$this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($_POST['message']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
	}

	
	$threadid = $this->model('group')->edit_thread($_POST['threadid'], $_POST['title'], $_POST['message']);
	
	if ($_POST['attach_access_key'])
	{
		$this->model('group')->update_attach($thread_info['threadid'], $_POST['attach_access_key']);
	}

	$url = get_js_url('/group/thread/' . $thread_info['threadid']);

	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), 1, null));
}

//成员管理
public function my_thread_action()
{
	if ($log = $this->model('group')->fetch_all('group_thread', 'uid = ' . $this->user_id, 'time DESC', (intval($_GET['page']) * 50) . ', 50'))
	{
		if($log)
		{
			foreach($log as $key => $val)
			{
				$group_info = $this->model('group')->get_group_info($log[$key]['groupid']);
				$log[$key]['groupname'] = $group_info['name'];
			}
		}
		$url = get_setting('base_url') . '?/group/my_thread/';
		TPL::assign('log', $log);
		TPL::assign('url', $url);
	}
	TPL::output('group/ajax_my_thread');
}

//删除帖子
public function remove_thread_action()
{
	$threadid = $_GET['threadid'];

	if(!$thread_info = $this->model('group')->get_thread_info($threadid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('帖子不存在或已删除')));	
	}
	
	$group_info = $this->model('group')->get_group_info($thread_info['groupid']);
	if($group_info['uid'] == $this->user_id) $isowner = TRUE; else $isowner = FALSE;
	
	if (($thread_info['uid'] != $this->user_id) && !$isowner && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
	{			
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限删除这个帖子')));
	}
	
	$this->model('group')->remove_thread($threadid);
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

public function save_comment_action()
{
	if (!$info = $this->model('group')->get_thread_info($_POST['threadid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('帖子不存在或已删除')));
	}
	
	$message = trim($_POST['message'], "\r\n\t");
	
	if (! $message)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('随便说点什么吧')));
	}
	
	if (! $this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($message))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
	}

	$comment_id = $this->model('group')->save_comment($_POST['threadid'], $message, $this->user_id, $_POST['at_uid'], $_POST['commentid']);
	
	$url = get_js_url('/group/thread/' . intval($_POST['threadid']));
	
	H::ajax_json_output(AWS_APP::RSM(array(
		'url' => $url
	), 1, null));
}

//删除回复
public function remove_comment_action()
{
	$commentid = $_POST['commentid'];
	
	if(!$comment_info = $this->model('group')->get_comment_info($commentid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('评论不存在或已删除')));	
	}
	
	$ids = $this->model('group')->get_ids_info_by_commentid($commentid);
	$group_info = $this->model('group')->get_group_info($ids['groupid']);
	if($group_info['uid'] == $this->user_id) $isowner = TRUE; else $isowner = FALSE;
	
	if (($comment_info['uid'] != $this->user_id) && !$isowner && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
	{			
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限删除这个评论')));
	}
	
	$this->model('group')->remove_comment($commentid);
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

//加精
public function recommend_action()
{
	$threadid = $_GET['threadid'];
	
	if(!$thread_info = $this->model('group')->get_thread_info($threadid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('帖子不存在或已删除')));	
	}
	
	$group_info = $this->model('group')->get_group_info($thread_info['groupid']);
	if($group_info['uid'] == $this->user_id) $isowner = TRUE; else $isowner = FALSE;
	
	if (($thread_info['uid'] != $this->user_id) && !$isowner && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
	{			
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限操作这个帖子')));
	}
	
	if($thread_info['recommend']) $flag = 0; else $flag = 1;
	
	$this->model('group')->recommend($threadid,$flag);
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}



	
}