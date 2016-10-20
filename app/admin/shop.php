<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class shop extends AWS_ADMIN_CONTROLLER
{
		
public function category_action()
{
	$this->crumb(AWS_APP::lang()->_t('分类管理'), 'admin/shop/category/');
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1001));
	TPL::assign('list', json_decode($this->model('shop')->build_category_json(), true));
	TPL::assign('category_option', $this->model('shop')->build_category_html(0, 0, null, false));
	TPL::output('admin/shop/category');
}
	
//编辑分类页面载入
public function cate_edit_action()
{
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1001));
	$category = $this->model('shop')->get_category_info($_GET['catid']);
	TPL::assign('category', $category);
	TPL::assign('category_option', $this->model('shop')->build_category_html(0, $category['pid'], null, false));
	TPL::output('admin/shop/category_edit');
}

//新增、编辑分类
public function cate_save_action()
{
	define('IN_AJAX', TRUE);
	
	$category_id = intval($_GET['catid']);
	$parent_id = intval($_POST['parent_id']);
	
	$category_list = $this->model('shop')->fetch_category($category_id);
	
	if ($category_id > 0 AND $parent_id > 0 AND $category_list = $this->model('shop')->fetch_category($category_id))
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
		$category_id = $this->model('shop')->add_category($_POST['name'], $parent_id);
	}
	
	$category = $this->model('shop')->get_category_info($category_id);
	
	if ($category['id'] == $parent_id)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能设置当前分类为父级分类')));
	}
	
	$update_data = array(
		'name' => $_POST['name'], 
		'pid' => $parent_id,
	);
	
	$this->model('shop')->update_category($category_id, $update_data);
	
	H::ajax_json_output(AWS_APP::RSM(array(
		'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/shop/category/'
	), 1, null));
}
	
//删除分类
public function cate_remove_action()
{
	define('IN_AJAX', TRUE);
	
	$catid = intval($_POST['catid']);

	if ($this->model('shop')->item_exists($catid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类下存在商品, 请先删除商品, 再删除当前分类')));
	}
	
	$this->model('shop')->delete_category($catid);

	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}	

//商品列表
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
			
			if ($key == 'keyword' OR $key == 'username')
			{
				$val = rawurlencode($val);
			}
			
			$param[] = $key . '-' . $val;
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/?/admin/shop/list/' . implode('__', $param)
		), 1, null));
	}
	
	$shop_list = $this->model('shop')->search_shop_list($_GET['page'], $this->per_page, $_GET['pid'], $_GET['catid'], $_GET['parent_only'], $_GET['keyword'], base64_decode($_GET['start_date']), base64_decode($_GET['end_date']), $_GET['paytype']);
	
	$total_rows = $this->model('shop')->search_shop_total;
		
	$url_param = array();
	
	foreach($_GET as $key => $val)
	{
		if ($key != 'page')
		{
			$url_param[] = $key . '-' . $val;
		}
	}
	
	$search_url = 'admin/shop/list/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('商品列表'), "admin/shop/list/");	
	if ($_GET['pid'] && $_GET['catid'])
	{	
		TPL::assign('cate_parent', $this->model('shop')->build_category_html(0,$_GET['pid'],null,false));
		TPL::assign('cate_child', $this->model('shop')->build_category_html($_GET['pid'],$_GET['catid'],null,false));
	}
	else
	{
		TPL::assign('cate_parent', $this->model('shop')->build_category_html(0,0,null,false));
	}
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('keyword', $_GET['keyword']);
	TPL::assign('list', $shop_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1003));
	TPL::output('admin/shop/list');
}

//上下架商品
public function item_lock_action()
{
	define('IN_AJAX', TRUE);
	$itemid = intval($_POST['id']);
	$isopen = intval($_POST['isopen']);
	$this->model('shop')->item_lock($itemid,$isopen);
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

//批处理
public function shop_batch_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['itemids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要操作的商品')));
	}
	
	foreach ($_POST['itemids'] AS $key => $itemids)
	{
		$this->model('shop')->remove_item($itemids);
	}
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}


public function order_action()
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
			'url' => get_setting('base_url') . '/?/admin/shop/order/' . implode('__', $param)
		), 1, null));
	}
	
	$charge_list = $this->model('shop')->search_order_list($_GET['page'], $this->per_page, $_GET['itemid'], $_GET['orderno'], base64_decode($_GET['start_date']), base64_decode($_GET['end_date']), $_GET['username'], $_GET['paytype']);
	
	$total_rows = $this->model('shop')->search_order_total;
	if($charge_list)
	{
		
		foreach($charge_list as $ckey => $cval)
		{
			$item_info = $this->model('shop')->get_item_info($charge_list[$ckey]['itemid']);
			$charge_list[$ckey]['itemname'] = $item_info['title'];
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
	
	$search_url = 'admin/shop/order/' . implode('__', $url_param);
	
	TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		'base_url' => get_setting('base_url') . '/?/' . $search_url, 
		'total_rows' => $total_rows, 
		'per_page' => $this->per_page
	))->create_links());
		
	$this->crumb(AWS_APP::lang()->_t('订单管理'), "admin/shop/order/");	
	TPL::assign('total_num', $total_rows);
	TPL::assign('search_url', $search_url);
	TPL::assign('keyword', $_GET['keyword']);
	TPL::assign('list', $charge_list);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1004));
	TPL::output('admin/shop/order');
}

//商品订单批处理
public function order_batch_action()
{
	define('IN_AJAX', TRUE);
	
	if (! $_POST['orderids'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择需要操作的订单')));
	}
	foreach ($_POST['orderids'] AS $key => $orderid)
	{
		//对于已付款订单status=1，不进行删除
		if($this->model('shop')->check_order_status_1($orderid)) continue;
		$this->model('shop')->remove_order($orderid);
	}
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}

//商品详情
public function order_detail_action()
{
	if(!$orderid = intval($_GET['id'])) H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('无效的订单ID')));
	$info = $this->model('shop')->fetch_row('shop_order', 'orderid = ' . intval($orderid));
	$v = $this->model('shop')->fetch_row('shop_address', 'uid = ' . $info['uid']);
	$address = $v['province'] . '-' . $v['city'] . '-' . $v['address'] . '<br/>邮编：' . $v['postcode'] . '<br/>联系人：' . $v['realname'] . '<br/>联系电话：' . $v['mobile'];
	$item_info = $this->model('shop')->get_item_info($info['itemid']);
	$item_name = $item_info['title'];
	TPL::assign('info', $info);
	TPL::assign('item_name', $item_name);
	TPL::assign('address', $address);
	TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(1004));
	TPL::output('admin/shop/order_detail');
}

//商品订单处理
public function order_edit_action()
{
	define('IN_AJAX', TRUE);
	$orderid = intval($_POST['orderid']);
	$status = intval($_POST['status']);
	$info = $_POST['info'];
	if ($order_info = $this->model('shop')->get_order_info($orderid))
	{
		if (!$status)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择要进行的操作')));
		}
		
		if (trim($info) == '' || trim($info) == 'NULL')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入备注信息，比如订单号，取消原因等。')));
		}
		
		$update_data = array(
		'status' => $status, 
		'info' => $info,
		);
		$this->model('shop')->update('shop_order',$update_data, "orderid = " . $orderid);
		
		//如果是取消订单，进行相关滚回
		if($status == 3)
		{
			$this->model('shop')->cancel_order($orderid);
		}
		$submsg = $status == 2 ? '已发货  ' : '已取消  ';
		$message = '您的订单状态发生改变：  ' . $submsg . $info . '%s';
		$this->model('message')->send_message(1, $order_info['uid'], AWS_APP::lang()->_t($message, get_js_url('/shop/order/')));
	}
	H::ajax_json_output(AWS_APP::RSM(array(
		'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/shop/order/id-' . $orderid), 1, null));
}




}