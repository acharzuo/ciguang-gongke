<?php

if (!defined('IN_ANWSION'))
{
	die;
}

class shop_class extends AWS_MODEL
{
	var $search_shop_total = 0;
	var $search_order_total = 0;
	
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
		$category_list_all_query = $this->fetch_all('shop_category', '', $order);
		
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
	return $this->insert('shop_category', array(
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
		if ($all_category_query = $this->fetch_all('shop_category'))
		{
			foreach ($all_category_query AS $key => $val)
			{
				$all_category[$val['id']] = $val;
			}
		}
	}
	
	return $all_category[$category_id];
}

//获取所有分类信息，以数组储存
public function get_all_category_info($pid = NULL)
{
	$all_category = $this->fetch_all('shop_category');
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

//更新分类
public function update_category($category_id, $update_data)
{
	return $this->update('shop_category', $update_data, 'id = ' . intval($category_id));
}

//获取商品信息
public function get_item_info($itemid)
{
	return $this->model('shop')->fetch_row('shop_list', 'id = ' . intval($itemid));
}

//获取订单信息
public function get_order_info($orderid)
{
	return $this->model('shop')->fetch_row('shop_order', 'orderid = ' . intval($orderid));
}

//检查分类下是否有商品
public function item_exists($catid)
{
	return $this->model('shop')->fetch_one('shop_list', 'id', 'catid = ' . intval($catid) . ' OR pid = ' . intval($catid));
}

//删除分类
public function delete_category($catid)
{
	$childs = $this->model('shop')->fetch_category_data($catid);
	
	if ($childs)
	{
		foreach($childs as $key => $val)
		{
			$this->delete_category($val['id']);
		}
	}
	return $this->delete('shop_category', 'id = ' . intval($catid));
}

//增加商品
public function add_item($arrdata, $attach_access_key = null)
{
	if ($itemid = $this->insert('shop_list', $arrdata))
	{			
		if ($attach_access_key)
		{
			$this->update_attach($itemid, $attach_access_key);
		}
	}
	return $itemid;
}

//后台商品列表
public function search_shop_list($page, $per_page, $pid, $catid, $parent_only, $keyword, $start_date = null, $end_date = null, $paytype = null)
{
	$where = array();
	
	if ($pid && $catid)
	{
		if($parent_only)
		{
			$where[] = 'pid = ' . $pid;
		}
		else
		{
			$where[] = 'catid = ' . $catid;
		}
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
	
	if ($paytype)
	{
		if($paytype == 1) $where[] = "paytype = 1 OR paytype = 3";
		if($paytype == 2) $where[] = "paytype = 2 OR paytype = 3";
	}
	
	if ($shop_info_list = $this->fetch_page('shop_list', implode(' AND ', $where), 'id DESC', $page, $per_page))
	{
		$this->search_shop_total = $this->found_rows();
		return $shop_info_list;
	}
}

public function item_lock($itemid,$isopen)
{			
	return $this->update('shop_list', 
			array
			(
				'isopen' => $isopen
			), 
			"id = " . $itemid);
}

public function update_item($itemid,$update_data)
{			
	$this->update('shop_list', $update_data, "id = " . $itemid);
}

public function remove_item($itemid)
{			
	return $this->delete('shop_list', 'id = ' . intval($itemid));	
}

public function update_item_pic($itemid,$picurl)
{			
	$this->update('shop_list',array ('picurl' => $picurl), "id = " . $itemid);
}


/**
 * 获取模块指定尺寸的完整url地址
 * @param  string $size	  指定尺寸
 * @param  string $pic_file 某一尺寸的图片文件名
 * @return string           取出主题图片或主题默认图片的完整url地址
 */
function get_shop_pic_url($size = null, $pic_file = null)
{
	if ($sized_file = AWS_APP::model('shop')->get_sized_file($size, $pic_file))
	{
		return get_setting('upload_url') . '/shop/' . $sized_file;
	}
	
	if (! $size)
	{
		return G_STATIC_URL . '/common/topic-max-img.png';
	}
	
	return G_STATIC_URL . '/common/topic-' . $size . '-img.png';
}

//获取文件大小
public function get_sized_file($size = null, $pic_file = null)
{
	if (! $pic_file)
	{
		return false;
	}
	
	if (! $size)
	{
		return str_replace('_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'] . '.', '.', $pic_file);
	}
	
	return str_replace(AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'] . '.', AWS_APP::config()->get('image')->topic_thumbnail[$size]['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail[$size]['h'] . '.', $pic_file);
}

public function get_item_list($page, $per_page, $order_by, $where)
{
	return $this->fetch_page('shop_list', $where, $order_by, $page, $per_page);
}

//根据给定条件获取商品信息
public function get_item_list_by_condition($where, $orderby, $limit)
{
	if ($item_list = $this->fetch_all('shop_list', $where, $orderby, $limit))
	{				
		return $item_list;
	}	
	return array();
}


//浏览+1
public function add_item_pageview($itemid)
{
	$this->shutdown_query("UPDATE " . $this->get_table('shop_list') . " SET pageview = pageview + 1
WHERE id = " . intval($itemid));
}

//购买
public function item_buy($itemid,$uid,$paytype,$num,$info)
{
	$need_point = 0;
	$need_rmb = 0;
	//获取商品信息
	if(!$item_info = $this->get_item_info($itemid)) 
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该商品不存在或已删除！')));
	}
	if(!$item_info['isopen'])
	{ 
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该商品已下架，不允许购买！')));
	}
	if($item_info['stock'] < $num)
	{ 
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该商品库存不足！')));
	}
	$user_info = $this->model('account')->get_user_info_by_uid($uid);
	if($paytype == 1)
	{
		$need_point = $item_info['pointprice'] * $num;
		if($user_info['integral'] < $need_point)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的积分不足以支持本次购买！')));
		}
		$destype = 'integral';
		$desnum = $need_point;
	}
	elseif($paytype == 2)
	{
		$need_rmb = $item_info['rmbprice'] * $num;
		if($user_info['rmb'] < $need_rmb)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的现金账户余额不足！')));
		}
		$destype = 'rmb';
		$desnum = $need_rmb;
	}
	//生成订单
	$insert_data = array(
		'itemid' => $itemid,
		'orderno' => time().rand(11111,9999), 
		'uid' => $uid, 
		'username' => $user_info['user_name'],
		'num' => $num,
		'paytype' => $paytype,
		'payrmb' => $need_rmb,
		'paypoint' => $need_point,
		'info' => $info,
		'time' => time(),
	);
	$this->insert('shop_order', $insert_data);
	//扣除用户积分或rmb
	$this->des_user_data($uid,$destype,$desnum);
	//增加已售数量、减少库存
	$this->op_item_data($itemid,$num);
}

public function des_user_data($uid,$destype,$desnum)
{
	if($destype == 'integral')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('users') . " SET integral = integral - $desnum WHERE uid = " . $uid);
	}
	elseif($destype == 'rmb')
	{
		$this->shutdown_query("UPDATE " . $this->get_table('users') . " SET rmb = rmb - $desnum WHERE uid = " . $uid);
	}
}

public function op_item_data($itemid,$num)
{
	//增加已售数量
	$this->shutdown_query("UPDATE " . $this->get_table('shop_list') . " SET sellnum = sellnum + $num WHERE id = " . $itemid);
	//减少库存
	$this->shutdown_query("UPDATE " . $this->get_table('shop_list') . " SET stock = stock - $num WHERE id = " . $itemid);
}

//后台订单列表
public function search_order_list($page, $per_page, $itemid, $orderno, $start_date = null, $end_date = null, $username, $paytype= null)
{
	$where = array();
	
	if ($itemid)
	{
		$where[] = 'itemid = ' . intval($itemid);
	}
	
	if ($orderno)
	{
		$where[] = 'orderno = ' . $orderno;
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
		$user_info = $this->model('account')->get_user_info_by_username($username);
		$where[] = 'uid = ' . intval($user_info['uid']);
	}
	
	if ($paytype)
	{
		if($paytype == 1) $where[] = "paytype = 1";
		if($paytype == 2) $where[] = "paytype = 2";
	}
	
	if ($order_info_list = $this->fetch_page('shop_order', implode(' AND ', $where), 'orderid DESC', $page, $per_page))
	{
		$this->search_order_total = $this->found_rows();
		return $order_info_list;
	}
}

//检查订单是否已经付款
public function check_order_status_1($orderid)
{			
	$status = $this->fetch_one('shop_order', 'status', 'orderid = ' . intval($orderid));
	if($status == 1) return true; else return false;
}

//删除订单
public function remove_order($orderid)
{			
	$this->delete('shop_order', 'orderid = ' . intval($orderid));	
}

//检查收货地址
public function check_user_address($uid)
{			
	$address_info = $this->fetch_row('shop_address', 'uid = ' . intval($uid));
	return $address_info;
}

//取消订单，进行相关滚回
public function cancel_order($orderid)
{			
	$info = $this->fetch_row('shop_order', 'orderid = ' . intval($orderid));
	if(!$info) H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该订单不存在或已删除！')));
	$paytype = $info['paytype'];
	$addpoint = $info['paypoint'];
	$addrmb = $info['payrmb'];
	$num = $info['num'];
	$uid = $info['uid'];
	$itemid = $info['itemid'];
	//退款
	if($info['paytype'] == 1) //积分
	{
		$this->shutdown_query("UPDATE " . $this->get_table('users') . " SET integral = integral + $addpoint WHERE uid = " . $uid);
	}
	elseif($info['paytype'] == 2)
	{
		$this->shutdown_query("UPDATE " . $this->get_table('users') . " SET rmb = rmb + $addrmb WHERE uid = " . $uid);
	}
	//增加已售数量
	$this->shutdown_query("UPDATE " . $this->get_table('shop_list') . " SET sellnum = sellnum - $num WHERE id = " . $itemid);
	//减少库存
	$this->shutdown_query("UPDATE " . $this->get_table('shop_list') . " SET stock = stock + $num WHERE id = " . $itemid);
}

public function set_address($uid,$province,$city,$address,$postcode,$realname,$mobile)
{
	$insert_data = array(
		'uid' => $uid,
		'province' => $province, 
		'city' => $city, 
		'address' => $address,
		'postcode' => $postcode,
		'realname' => $realname,
		'mobile' => $mobile,
	);
	$update_data = array(
		'province' => $province, 
		'city' => $city, 
		'address' => $address,
		'postcode' => $postcode,
		'realname' => $realname,
		'mobile' => $mobile,
	);
	if($this->check_user_address($uid))
	{
		$this->update('shop_address',$update_data, "uid = " . $uid);
	}
	else
	{
		$this->insert('shop_address', $insert_data);
	}
}

/*---------------------------- 附件上传相关 begin ----------------------------*/
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
		'item_type' => 'shop'
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
	), "item_type = 'shop' AND item_id = 0 AND access_key = '" . $this->quote($attach_access_key) . "'"))
	{		
		return $this->shutdown_update('shop_list', array(
			'has_attach' => 1
		), ' id = ' . intval($item_id));
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
		$this->shutdown_update('shop_list', array(
			'has_attach' => 0
		), 'id = ' . $attach['item_id']);
	}
	
	foreach(AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
	{
		@unlink(get_setting('upload_dir').'/shop/' . date('Ymd', $attach['add_time']) . '/' . $val['w'] . 'x' . $val['h'] . '_' . $attach['file_location']);
	}
	
	@unlink(get_setting('upload_dir').'/shop/' . date('Ymd', $attach['add_time']) . '/' . $attach['file_location']);
	
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
		
		if (! file_exists(get_setting('upload_dir') . '/shop/' . $date_dir . '/' . $data['file_location']))
		{
			$date_dir = gmdate('Ymd', ($data['add_time'] + 86400));
		}
		
		if (! file_exists(get_setting('upload_dir') . '/shop/' . $date_dir . '/' . $data['file_location']))
		{
			$date_dir = gmdate('Ymd', ($data['add_time'] - 86400));
		}
			
		$attach_list[$data['id']] = array(
			'id' => $data['id'], 
			'is_image' => $data['is_image'], 
			'file_name' => $data['file_name'], 
			'access_key' => $data['access_key'], 
			'attachment' => get_setting('upload_url') . '/shop/' . $date_dir . '/' . $data['file_location'],
		);
			
		if ($data['is_image'] == 1)
		{
			$attach_list[$data['id']]['thumb'] = get_setting('upload_url') . '/shop/' . $date_dir . '/' . $data['file_location'];
		}
	}
	return $attach_list;
}

public function get_attach($item_id, $size = 'square')
{
	$attach = $this->fetch_all('attach', "item_type = 'shop' AND item_id = " . intval($item_id), "is_image DESC, id ASC");
	
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
/*---------------------------- 附件上传相关 end ----------------------------*/

}
?>