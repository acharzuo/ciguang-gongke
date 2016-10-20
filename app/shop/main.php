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
	$itemid = $_GET['id'];
	$item_info = $this->model('shop')->get_item_info($itemid);
	if ($item_info)
	{
		if(!$item_info) HTTP::redirect('/shop/');
		$this->model('shop')->add_item_pageview($itemid);
		$this->crumb(AWS_APP::lang()->_t('商品详情'), '/shop/' . $itemid);
		
		if ($item_info['has_attach'])
		{
			$item_info['attachs'] = $this->model('shop')->get_attach($item_info['id'], 'min');
			
			$item_info['attachs_ids'] = FORMAT::parse_attachs($item_info['description'], true);
		}
		$item_info['description'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($item_info['description'])));
		TPL::assign('item_info', $item_info);
		TPL::output('shop/detail');
	}
	else
	{
		$pid = $_GET['pid'];
		$catid = $_GET['catid'];
		$where = " isopen = '1' ";
		if($pid)
		{
			$where .= " AND pid = " . $pid;
			$child = $this->model('shop')->get_all_category_info($pid);
		}
		if($catid)$where .= " AND catid = " . $catid;
		
		$item_list = $this->model('shop')->get_item_list($_GET['page'], get_setting('contents_per_page'), 'time DESC', $where);
		$item_list_total = $this->model('shop')->found_rows();
		$hot_view_list = $this->model('shop')->get_item_list_by_condition('isopen = 1', $order = 'pageview DESC', 5);
		$hot_sell_list = $this->model('shop')->get_item_list_by_condition('isopen = 1', $order = 'sellnum DESC', 5);
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/shop/id-' . $item_info['id']), 
			'total_rows' => $item_list_total,
			'per_page' => 10
		))->create_links());
		$parent = $this->model('shop')->get_all_category_info();
		$this->crumb(AWS_APP::lang()->_t('商品列表'), '/shop/');
		TPL::assign('parent', $parent);
		TPL::assign('child', $child);
		TPL::assign('item_list', $item_list);
		TPL::assign('hot_view_list', $hot_view_list);
		TPL::assign('hot_sell_list', $hot_sell_list);
		TPL::output('shop/list');		
	}
}

public function publish_action()
{	
	if (!$this->user_info['permission']['is_moderator'] && !$this->user_info['permission']['is_administortar'])
	{
		HTTP::redirect('/shop/');
	}
	
	if ($_GET['id'])
	{
		if (!$item_info = $this->model('shop')->get_item_info($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('商品不存在或已删除'));
		}
		$picurl = $this->model('shop')->get_shop_pic_url('max', $item_info['picurl']);
		TPL::assign('picurl', $picurl);
		TPL::assign('cate_parent', $this->model('shop')->build_category_html(0,$item_info['pid'],null,false));
		TPL::assign('cate_child', $this->model('shop')->build_category_html($item_info['pid'],$item_info['catid'],null,false));
		TPL::assign('info', $item_info);
	}
	else
	{
		TPL::assign('cate_parent', $this->model('shop')->build_category_html(0,0,null,false));
	}
	
	if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] AND $_GET['id']) OR !$_GET['id'])
	{
		TPL::assign('attach_access_key', md5($this->user_id . time()));
	}
	
	TPL::import_js('js/publish_shop.js');
	if (get_setting('advanced_editor_enable') == 'Y')
	{
		import_editor_static_files();
	}

	if (get_setting('upload_enable') == 'Y')
	{
		// fileupload
		TPL::import_js('js/fileupload.js');
	}
	
	$this->crumb(AWS_APP::lang()->_t('发布商品'), '/shop/publish/');
	TPL::output('shop/publish');
}

function order_action()
{				
	$this->crumb(AWS_APP::lang()->_t('我的订单'), '/shop/order/');
	TPL::output('shop/order');
}

function address_action()
{				
	$this->crumb(AWS_APP::lang()->_t('我的收货地址'), '/shop/address/');
	$info = $this->model('shop')->check_user_address($this->user_id);
	TPL::assign('info', $info);
	TPL::output('shop/address');
}
	
	
}
