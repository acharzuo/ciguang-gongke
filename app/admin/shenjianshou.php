<?php

class shenjianshou extends AWS_ADMIN_CONTROLLER {

    private $ta_version = "3.1.3";
    private $ta_config_file;
    private $ta_config = array(
        //设置
        'ta_password' => "shenjianshou.cn",
    );

    public function __construct() {
        parent::__construct();
        $this->ta_config_file = AWS_PATH . 'config/ta_config.php';
    }

    public function index_action() {
        $this->settings_action();
    }

    public function settings_action() {
        if (file_exists($this->ta_config_file)) {
            require $this->ta_config_file;
        }

        $this->crumb(AWS_APP::lang()->_t('神箭手'), 'admin/shenjianshou/settings/');
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(901));

        if (isset($config['ta_config'])) {
            $ta_config = $config['ta_config'];
        } else {
            $ta_config = $this->ta_config;
        }
        $basic_web_address = str_replace('\\','/',$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));

        TPL::assign('ta_version', $this->ta_version);
        TPL::assign('basic_web_address', $basic_web_address);
        TPL::assign('ta_config', $ta_config);
        TPL::output('admin/shenjianshou/settings');
    }

    public function ajax_ta_action() {
        $ta_password = empty($_POST['ta_password']) ? "shenjianshou.cn" : $_POST['ta_password'];

        $code = <<<config_code
<?php
/**
 * 神箭手问答配置文件
 */
\$config['ta_config'] = array(
    'ta_password' => "{$ta_password}"
);
config_code;

        file_put_contents($this->ta_config_file, $code);
        //$proxy_url = base_url() . "/" . G_INDEX_SCRIPT . "proxy/&token=" . urlencode($ta_password);
        H::ajax_json_output(AWS_APP::RSM("", -1, AWS_APP::lang()->_t('保存设置成功')));
    }

}
