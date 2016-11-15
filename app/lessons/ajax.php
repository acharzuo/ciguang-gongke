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

    /*
     * 读取权限
     */
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';

        $rule_action['actions'] = array(
            'lessons_vote'
        );

        return $rule_action;
    }

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
            'dizangchan' => isset($_POST['dizangchan']) ? $_POST['dizangchan'] : 0,
            'nianfoshichang' => isset($_POST['nianfoshichang']) ? $_POST['nianfoshichang'] : 0,
            'date' => $date,
        );

        // 功课内容必须填写一项,
        if(!($lesson['songjing'] || $lesson['chanhui']
            || $lesson['nianfo'] || $lesson['dizangchan']
            || $lesson['nianfoshichang'] || $lesson['yuezangpin'])) {
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

    public function edit_action()
    {
        $uid = $this->user_id;

        $date = isset($_POST['date']) ? $_POST['date'] : 0;
        $songjing = isset($_POST['songjing']) ? (int) $_POST['songjing'] : 0;
        $chanhui = isset($_POST['chanhui']) ? (int) $_POST['chanhui'] : 0;
        $nianfo = isset($_POST['nianfo']) ? (int) $_POST['nianfo'] : 0;
        $yuezangjing = isset($_POST['yuezangjing']) ? $_POST['yuezangjing'] : "";
        $yuezangpin = isset($_POST['yuezangpin']) ? (int) $_POST['yuezangpin'] : 0;
        $dizangchan = isset($_POST['dizangchan']) ? (int) $_POST['dizangchan'] : 0;
        $nianfoshichang = isset($_POST['nianfoshichang']) ? (int) $_POST['nianfoshichang'] : 0;

        $update = array(
            'songjing' => $songjing,
            'chanhui' => $chanhui,
            'nianfo' => $nianfo,
            'yuezangjing' => htmlspecialchars($yuezangjing),
            'yuezangpin' => $yuezangpin,
            'dizangchan' => $dizangchan,
            'nianfoshichang' => $nianfoshichang,
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

    /**
     * 功课的投票(点赞)
     */
    public function lessons_vote_action() {

        // 根据功课类型获取功课信息
        switch ($_POST['type'])
        {
            case 'lessons':
                $item_info = $this->model('lessons')->get_lesson_by_id($_POST['item_id']);
                break;
        }



        if (!$item_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
        }

//        if ($item_info['uid'] == $this->user_id)
//        {
//            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('对自己就不要点赞了,久之同学已经帮您点过了! 赞 !!!')));
//        }

        // 这个玩意是什么,暂时不知道,就留着吧! 好像用户组的玩意
        $reputation_factor = $this->model('account')->get_user_group_by_id($this->user_info['reputation_group'], 'reputation_factor');



        $res = $this->model('lessons')->lesson_vote($_POST['type'], $_POST['item_id'], $_POST['rating'], $this->user_id, $reputation_factor, $item_info['uid']);


        H::ajax_json_output(AWS_APP::RSM(null, 1, null));

    }


}