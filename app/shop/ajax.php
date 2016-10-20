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

/**************************************** 附件相关 begin ****************************************/
public function attach_upload_action()
{
	if (get_setting('upload_enable') != 'Y')
	{
		die;
	}
	
	AWS_APP::upload()->initialize(array(
		'allowed_types' => get_setting('allowed_upload_types'),
		'upload_path' => get_setting('upload_dir') . '/shop/' . date('Ymd'),
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
	
	$attach_id = $this->model('shop')->add_attach($upload_data['orig_name'], $_GET['attach_access_key'], time(), basename($upload_data['full_path']), $upload_data['is_image']);
	
	$output = array(
		'success' => true,
		'delete_url' => get_js_url('/shop/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
			'attach_id' => $attach_id, 
			'access_key' => $_GET['attach_access_key']
		)))),
		'attach_id' => $attach_id,
		'attach_tag' => 'attach'
		
	);
	
	$attach_info = $this->model('shop')->get_attach_by_id($attach_id);
	
	if ($attach_info['thumb'])
	{
		$output['thumb'] = $attach_info['thumb'];
	}
	else
	{
		$output['class_name'] = $this->model('shop')->get_file_class(basename($upload_data['full_path']));
	}
	
	echo htmlspecialchars(json_encode($output), ENT_NOQUOTES);
}

public function attach_edit_list_action()
{
	if (!$item_info = $this->model('shop')->get_item_info($_POST['itemid']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('无法获取附件列表')));
	}
	
	if (!$this->user_info['permission']['is_administortar'] || !$this->user_info['permission']['is_moderator'])
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个附件列表')));
	}
	
	if ($article_attach = $this->model('shop')->get_attach($_POST['itemid']))
	{
		foreach ($article_attach as $attach_id => $val)
		{
			$article_attach[$attach_id]['class_name'] = $this->model('shop')->get_file_class($val['file_name']);
			
			$article_attach[$attach_id]['delete_link'] = get_js_url('/shop/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
				'attach_id' => $attach_id, 
				'access_key' => $val['access_key']
			))));
			
			$article_attach[$attach_id]['attach_id'] = $attach_id;
			$article_attach[$attach_id]['attach_tag'] = 'attach';
		}
	}
	H::ajax_json_output(AWS_APP::RSM(array(
		'attachs' => $article_attach
	), 1, null));
}

public function remove_attach_action()
{
	$attach_info = H::decode_hash(base64_decode($_GET['attach_id']));
	
	$this->model('shop')->remove_attach($attach_info['attach_id'], $attach_info['access_key']);
	
	H::ajax_json_output(AWS_APP::RSM(null, 1, null));
}
/**************************************** 附件相关 end ****************************************/

public function get_child_by_pid_action()
{
	$pid = intval($_GET['pid']);
	$content = $this->model('shop')->build_category_html($pid, 0, null, false);
	H::ajax_json_output(AWS_APP::RSM(null, 1, $content));
}

public function upload_item_pic_action()
{
	if (!$item_info = $this->model('shop')->get_item_info($_GET['id']))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该商品不存在')));
	}
	
	AWS_APP::upload()->initialize(array(
		'allowed_types' => 'jpg,jpeg,png,gif',
		'upload_path' => get_setting('upload_dir') .  '/shop/' . date('Ymd'),
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
	$this->model('shop')->update_item_pic($_GET['id'],   $picurl);
	
	@unlink(get_setting('upload_dir').'/shop/' . $item_info['picurl']);
	
	echo htmlspecialchars(json_encode(array(
		'success' => true,
		'thumb' => get_setting('upload_url') . '/shop/' . gmdate('Ymd') . '/' . basename($upload_data['full_path'])
	)), ENT_NOQUOTES);
}

public function buy_action()
{		
	$paytype = $_POST['paytype'];
	$itemid = $_POST['itemid'];
	if(!$itemid) H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('参数异常！')));
	if(!$paytype) H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择支付方式！')));
	if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $_POST['num']))
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('购买数量必须为一个正数！')));
	//生成支付订单
	$orderid = $this->model('shop')->item_buy($itemid,$this->user_id,$paytype,$_POST['num'],$_POST['info']);
	$chargelisturl = get_setting('base_url') . '/?/shop/order/';
	H::ajax_json_output(AWS_APP::RSM(array('url' => $chargelisturl), '1', NULL));
}


public function order_action()
{
	if ($log = $this->model('shop')->fetch_all('shop_order', 'uid = ' . $this->user_id, 'time DESC', (intval($_GET['page']) * 50) . ', 50'))
	{
		foreach($log as $key => $val)
		{
			$item_info = $this->model('shop')->get_item_info($log[$key]['itemid']);
			$log[$key]['itemname'] = $item_info['title'];
		}
		$url = get_setting('base_url') . '?/shop/order/';
		TPL::assign('log', $log);
		TPL::assign('url', $url);
	}
	TPL::output('shop/order_ajax');
}

//获取订单备注信息
public function get_order_info_action()
{
	$orderid = $_POST['id'];
	$info = $this->model('shop')->fetch_one('shop_order', 'info', 'orderid = ' . intval($orderid));
	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t($info)));
}

public function address_action()
{		
	$province = $_POST['province'];
	$city = $_POST['city'];
	$address = $_POST['address'];
	$postcode = $_POST['postcode'];
	$realname = $_POST['realname'];
	$mobile = $_POST['mobile'];
	if($province == '' || $city == '') H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择所属城市！')));
	if(trim($address) == '') H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写详细地址！')));
	if(!preg_match('/^[0-9]+$/', $postcode))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的邮政编码！')));
	}
	if(trim($realname) == '') H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写联系人名称！')));
	if(!preg_match('/^[0-9\-\+]+$/', $mobile))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的联系电话！')));
	}
	$this->model('shop')->set_address($this->user_id,$province,$city,$address,$postcode,$realname,$mobile);
	$turl = get_setting('base_url') . '/?/shop/address/';
	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), '1', NULL));
}

public function item_add_action()
{	
	if (!$this->user_info['permission']['is_moderator'] && !$this->user_info['permission']['is_administortar'])
	{
		H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/shop/')), 1, null));
	}
	
	$pid = intval($_POST['pid']);
	$catid = intval($_POST['catid']);
	$title = $_POST['title'];
	$paytype = intval($_POST['paytype']);
	$rmbprice = $_POST['rmbprice'];
	$pointprice = $_POST['pointprice'];
	$stock = $_POST['stock'];
	$description = $_POST['description'];
	
	if (!$pid || !$catid)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择商品分类')));
	}
	
	if (trim($title) == '')
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入商品名称')));
	}
	
	if ($paytype == 1 || $paytype == 3)
	{
		if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $pointprice))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('金币必须为一个正整数！')));
	}
	
	if ($paytype == 2 || $paytype == 3)
	{
		if(!preg_match('/^\d+(?=\.{0,1}\d+$|$)/', $rmbprice))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('人民币必须为一个正数！')));
	}
	
	if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $stock))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('商品库存必须为一个正整数！')));
	}	

	//上传图片
	if ($_FILES['attach']['name'])
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,png,gif',
			'upload_path' => get_setting('upload_dir') .  '/shop/' . date('Ymd'),
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
				break;
			}
		}
	
		if (! $upload_data = AWS_APP::upload()->data())
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
		}
		$picurl =  date('Ymd') . '/' . basename($upload_data['full_path']);
	}
	
	$insert_data = array(
		'picurl' => $picurl,
		'pid' => $pid, 
		'catid' => $catid, 
		'title' => $title,
		'paytype' => $paytype,
		'rmbprice' => $rmbprice,
		'pointprice' => $pointprice,
		'description' => $description,
		'stock' => $stock,
		'time' => time(),
	);
	
	$itemid = $this->model('shop')->add_item($insert_data,$_POST['attach_access_key']);

	$url = get_js_url('/shop/' . $itemid);
	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), 1, null));

}

public function item_edit_action()
{	
	if (!$this->user_info['permission']['is_moderator'] && !$this->user_info['permission']['is_administortar'])
	{
		H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/shop/')), 1, null));
	}
	
	$itemid = intval($_POST['itemid']);
	$pid = intval($_POST['pid']);
	$catid = intval($_POST['catid']);
	$title = $_POST['title'];
	$paytype = intval($_POST['paytype']);
	$rmbprice = $_POST['rmbprice'];
	$pointprice = $_POST['pointprice'];
	$stock = $_POST['stock'];
	$description = $_POST['description'];
	
	if (!$item_info = $this->model('shop')->get_item_info($itemid))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('商品不存在或已删除')));
	}

	if (!$pid || !$catid)
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择商品分类')));
	}
	
	if (trim($title) == '')
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入商品名称')));
	}
	
	if ($paytype == 1 || $paytype == 3)
	{
		if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $pointprice))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('金币必须为一个正整数！')));
	}
	
	if ($paytype == 2 || $paytype == 3)
	{
		if(!preg_match('/^\d+(?=\.{0,1}\d+$|$)/', $rmbprice))
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('人民币必须为一个正数！')));
	}
	
	if(!preg_match('/^[0-9]*[1-9][0-9]*$/', $stock))
	{
		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('商品库存必须为一个正整数！')));
	}
		
	$update_data = array(
		'pid' => $pid, 
		'catid' => $catid, 
		'title' => $title,
		'paytype' => $paytype,
		'rmbprice' => $rmbprice,
		'pointprice' => $pointprice,
		'stock' => $stock,
		'description' => $description,
	);
	
	if ($_POST['attach_access_key'])
	{
		$this->model('shop')->update_attach($itemid, $_POST['attach_access_key']);
	}
	
	$this->model('shop')->update_item($itemid,$update_data);

	$url = get_js_url('/shop/' . $itemid);
	H::ajax_json_output(AWS_APP::RSM(array('url' => $url), 1, null));

}

	
}