<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class thread extends AWS_CONTROLLER
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

//发帖
public function index_action()
{
	$threadid = intval($_GET['id']);
	if (!$threadid) HTTP::redirect('/group/');	
	if (!$thread_info = $this->model('group')->get_thread_info($threadid))
	{
		HTTP::redirect('/group/');
	}
	$this->model('group')->add_thread_pageview($threadid);
	$group_info = $this->model('group')->get_group_info($thread_info['groupid']);
	$join_status = $this->model('group')->is_join_group($this->user_id,$thread_info['groupid']);
	if(!$group_info['isopen'] && $join_status != 2) 
	{
		H::redirect_msg(AWS_APP::lang()->_t('该群组暂不对外开放，要查看帖子请先加入该群！'), get_setting('base_url') . '/?/group/' . $thread_info['groupid']); 	
	}
	if($group_info['uid'] == $this->user_id) $is_group_owner = TRUE; else $is_group_owner = FALSE;
	if ($thread_info['has_attach'])
	{
		$thread_info['attachs'] = $this->model('group')->get_attach($threadid, 'min');
		$thread_info['attachs_ids'] = FORMAT::parse_attachs($thread_info['message'], true);
	}
	$thread_info['user_info'] = $this->model('account')->get_user_info_by_uid($thread_info['uid']); 
	$thread_info['message'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($thread_info['message'])));
	
	$comments = $this->model('group')->get_comments_page($_GET['page'], get_setting('contents_per_page'), $threadid);
	$comments_total = $this->model('group')->found_rows();
		
	if($comments)
	{
		//楼层排序
		foreach($comments as $key => $val)
		{
			$level = $this->model('group')->get_comment_level($comments[$key]['id']);
			$comments[$key]['level'] = $level['count'] + 1;
		}
	}
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/group/thread/id-' . $threadid), 
			'total_rows' => $thread_info['commentnum'],
			'per_page' => 10
		))->create_links());
	$this->crumb(AWS_APP::lang()->_t($thread_info['title']), '/group/thread/' . $threadid);
	TPL::assign('info', $thread_info);
	TPL::assign('comments', $comments);
	TPL::assign('is_group_owner', $is_group_owner);
	TPL::assign('is_comment_owner', $is_comment_owner);
	TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $thread_info['uid']));
	TPL::output('group/thread');
}




}
