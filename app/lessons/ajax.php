<?php
/**
 * Created by PhpStorm.
 * User: deliang
 * Date: 9/19/16
 * Time: 2:25 PM
 */
define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
    die;
}

class ajax extends AWS_CONTROLLER
{

    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function add_lessons_action()
    {
        $uid = (int) $this->user_id;
        $date = isset($_POST['date']) ? $_POST['date'] : 0;

        if (empty($date)) {
            $date = date('Ymd', time());
        } else {
            if (!strtotime($date)) {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('时间格式不正确')));

            }
            $date = str_replace('-', '', $date);
        }

        $lesson = array(
            'songjing' => isset($_POST['songjing']) ? $_POST['songjing'] : 0,
            'chanhui' => isset($_POST['chanhui']) ? $_POST['chanhui'] : 0,
            'nianfo' => isset($_POST['nianfo']) ? $_POST['nianfo'] : 0,
            'yuezangjing' => htmlspecialchars(isset($_POST['yuezangjing']) ? $_POST['yuezangjing'] : 0),
            'yuezangpin' => isset($_POST['yuezangpin']) ? $_POST['yuezangpin'] : 0,
            'date' => $date,
        );

        // 功课内容必须填写一项,
        if(!($lesson['songjing'] || $lesson['chanhui'] || $lesson['nianfo'])) {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('功课要做,还有填上具体的数字哦!')));
        }

        $lessons = $this->model('lessons')->get_detail($uid, $date);
        if (empty($lessons)) {
            $res = $this->model('lessons')->add_lessons($uid, $lesson);
        } else {
            $res = $this->model('lessons')->update_lessons($uid, $date, $lesson);
        }

        if (!$res) {
            H::ajax_json_output(AWS_APP::RSM(null, 1, '添加失败'));
        } else {
            H::ajax_json_output(AWS_APP::RSM(null, 0, '添加成功'));
        }
    }

    public function edit_lessons_action()
    {
        $uid = $this->user_id;

        $date = isset($_POST['date']) ? $_POST['date'] : 0;
        $songjing = isset($_POST['songjing']) ? (int) $_POST['songjing'] : 0;
        $chanhui = isset($_POST['chanhui']) ? (int) $_POST['chanhui'] : 0;
        $nianfo = isset($_POST['nianfo']) ? (int) $_POST['nianfo'] : 0;
        $yuezangjing = isset($_POST['yuezangjing']) ? $_POST['yuezangjing'] : "";
        $yuezangpin = isset($_POST['yuezangpin']) ? (int) $_POST['yuezangpin'] : 0;

        $update = array(
            'songjing' => $songjing,
            'chanhui' => $chanhui,
            'nianfo' => $nianfo,
            'yuezangjing' => htmlspecialchars($yuezangjing),
            'yuezangpin' => $yuezangpin,
        );

        $date = str_replace('-', '', $date);
        if (empty($date)) {
            H::ajax_json_output(AWS_APP::RSM(null, 1, '时间格式不对'));
        }

        $res = $this->model('lessons')->update_lessons($uid, $date, $update);
        if (!$res) {
            H::ajax_json_output(AWS_APP::RSM(null, 1, '更新失败'));
        } else {
            H::ajax_json_output(AWS_APP::RSM(null, 0, '更新成功'));
        }
    }


}