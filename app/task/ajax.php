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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	
	
	
	
public function get_access_rule()
{
	$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
	$rule_action['actions'] = array();
	
	return $rule_action;
}

function setup()
{
	HTTP::no_cache_header();
}

public function attach_upload_action()
{
	if (get_setting('upload_enable') != 'Y')
	{
		die;
	}
	
	AWS_APP::upload()->initialize(array(
		'allowed_types' => get_setting('allowed_upload_types'),
		'upload_path' => get_setting('upload_dir') . '/task/' . date('Ymd'),
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
	
	$attach_id = $this->model('task')->add_attach($upload_data['orig_name'], $_GET['attach_access_key'], time(), basename($upload_data['full_path']), $upload_data['is_image']);
	
	$output = array(
		'success' => true,
		'delete_url' => get_js_url('/task/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
			'attach_id' => $attach_id, 
			'access_key' => $_GET['attach_access_key']
		)))),
		'attach_id' => $attach_id,
		'attach_tag' => 'attach'
	);
	
	$attach_info = $this->model('task')->get_attach_by_id($attach_id);
	
	if ($attach_info['thumb'])
	{
		$output['thumb'] = $attach_info['thumb'];
	}
	else
	{
		$output['class_name'] = $this->model('task')->get_file_class(basename($upload_data['full_path']));
	}
	
	echo htmlspecialchars(json_encode($output), ENT_NOQUOTES);
}

public function attach_edit_list_action()
{
	if (!$task_info = $this->model('task')->get_task_info($_POST['taskid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('无法获取附件列表')));
	}
	
	if ($task_info['uid'] != $this->user_id && (!$this->user_info['permission']['is_administortar'] || !$this->user_info['permission']['is_moderator']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个附件列表')));
	}
	
	if ($task_attach = $this->model('task')->get_attach($_POST['taskid']))
	{
		foreach ($task_attach as $attach_id => $val)
		{
			$task_attach[$attach_id]['class_name'] = $this->model('task')->get_file_class($val['file_name']);
			
			$task_attach[$attach_id]['delete_link'] = get_js_url('/task/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
				'attach_id' => $attach_id, 
				'access_key' => $val['access_key']
			))));
			
			$task_attach[$attach_id]['attach_id'] = $attach_id;
			$task_attach[$attach_id]['attach_tag'] = 'attach';
		}
	}
	
	H::ajax_json_output(AWS_APP::RSM(array(
		'attachs' => $task_attach
	), 1, null));
}

public function remove_attach_action()
{
	$attach_info = H::decode_hash(base64_decode($_GET['attach_id']));
	
	$this->model('task')->remove_attach($attach_info['attach_id'], $attach_info['access_key']);
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

public function publish_task_action()
{
	if (!$this->user_info['permission']['publish_task'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限发布任务')));
	}
	
	if (empty($_POST['title']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入任务名称')));
	}
	
	if ($_POST['rewardtype'] == 'point')
	{
		if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $_POST['rewardnum']))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('悬赏类型为积分时，悬赏数额必须为一个正整数！')));
	}
	elseif ($_POST['rewardtype'] == 'rmb')
	{
		if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $_POST['rewardnum']))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('悬赏类型为人民币时，悬赏数额必须为一个正数！')));
	}
	
	
	if (!$this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($_POST['message']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
	}
	
	if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
	{			
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
	}
	
	if ($_POST['topics'] AND get_setting('question_topics_limit') AND sizeof($_POST['topics']) > get_setting('question_topics_limit'))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
	}
	
	if (get_setting('new_question_force_add_topic') == 'Y' AND !$_POST['topics'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请为任务添加话题')));
	}
	
	$settings = $this->model('task')->get_config();
	if ($settings['isconfirm']) $status = 1; else $status = 2;

	$taskid = $this->model('task')->publish_task($_POST['title'], $_POST['rewardtype'], $_POST['rewardnum'], $_POST['message'], $this->user_id, $status, $_POST['topics'], $_POST['attach_access_key'], $this->user_info['permission']['create_topic']);

	if($status == 1) //需审核
	{
		$url = get_js_url('/task/publish_check/');
	}
	else
	{
		$url = get_js_url('/task/' . $taskid);
	}
	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), 1, null));
}

function edit_task_action()
{
	if (!$task_info = $this->model('task')->get_task_info($_POST['taskid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除')));
	}
	
	if (!$this->user_info['permission']['edit_task'] && (!$this->user_info['permission']['is_moderator'] || !$this->user_info['permission']['is_administortar']))
	{			
		if ($task_info['uid'] != $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个任务')));
		}
	}
	
	if (empty($_POST['title']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入任务名称')));
	}	
	
	if (!$this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($_POST['message']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
	}
	
	if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
	{			
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
	}
	
	if ($_POST['topics'] AND get_setting('question_topics_limit') AND sizeof($_POST['topics']) > get_setting('question_topics_limit'))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
	}
	
	if (get_setting('new_question_force_add_topic') == 'Y' AND !$_POST['topics'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请为任务添加话题')));
	}

	
	$taskid = $this->model('task')->edit_task($_POST['taskid'], $this->user_id, $_POST['title'], $_POST['message'], $_POST['topics'], $this->user_info['permission']['create_topic']);
	
	if ($_POST['attach_access_key'])
	{
		$this->model('task')->update_attach($task_info['taskid'], $_POST['attach_access_key']);
	}

	$url = get_js_url('/task/' . $task_info['taskid']);

	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), 1, null));
}

public function task_join_action()
{
	if (!$task_info = $this->model('task')->get_task_info($_POST['taskid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除')));
	}
	
	if ($task_info['uid'] == $this->user_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能报名参加自己发布的任务')));
	}
	
	if ($task_info['islock'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该任务报名已经结束')));
	}
	
	$message = trim($_POST['answer_content']);
	
	if (! $message)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('报名的时候总得说点什么吧')));
	}
	
	if (! $this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($message))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
	}

	$comment_id = $this->model('task')->task_join($_POST['taskid'], $message, $this->user_id, $_POST['at_uid'], $_POST['attach_access_key']);
	
	$url = get_js_url('/task/' . intval($_POST['taskid']));
	
	H::ajax_json_output(AWS_APP::RSM(array(
		'url' => $url
	), 1, null));
}

public function hire_action()
{
	$taskid = intval($_GET['taskid']);
	$joinuid = intval($_GET['joinuid']);

	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除')));
	}
	
	if ($task_info['uid'] != $this->user_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您无权进行操作')));
	}
	
	$this->model('task')->task_hire($taskid,$joinuid);
}

public function hand_over_action()
{
	$taskid = intval($_GET['taskid']);

	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除')));
	}
	
	if ($task_info['joinuid'] != $this->user_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您无权进行操作')));
	}
	
	$this->model('task')->hand_over($taskid,$this->user_id);
}

public function confirm_action()
{
	$taskid = intval($_GET['taskid']);

	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除')));
	}
	
	if ($task_info['uid'] != $this->user_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您无权进行操作')));
	}
	
	$this->model('task')->task_confirm($taskid);
}

public function task_comment_action()
{
	$taskid = intval($_POST['taskid']);
	$rate = intval($_POST['rate']);
	$comment = $_POST['comment'];

	if (!$task_info = $this->model('task')->get_task_info($taskid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('任务不存在或已删除')));
	}
	
	if ($task_info['uid'] != $this->user_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('您无权进行操作')));
	}
	
	if (!$rate)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择评分项！')));
	}
	
	if (trim($comment) == '')
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入评价内容！')));
	}
	
	$this->model('task')->task_comment($taskid,$rate,$comment) ;
}







}