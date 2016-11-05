<?php
/**
 * Created by PhpStorm.
 * User: deliang
 * Date: 9/19/16
 * Time: 2:28 PM
 */
if (!defined('IN_ANWSION'))
{
    die;
}

class main extends AWS_CONTROLLER
{

    /**
     * action访问列表
     */
    public function get_access_rule()
    {

        // 所有都要登录
        $rule_action['rule_type'] = 'white';


        // 这个里面的不用登陆
        $rule_action['actions'] =  array(
                                    'index',
            'actionsday'
                                    );
        return $rule_action;
    }

    /**
     * 我的功课列表页面
     */
    public function list_action()
    {
        $lessons = $this->model('lessons')->lessons_list($this->user_id, 1);

        TPL::assign('lessons', $lessons);
        TPL::output('lessons/list');

    }

    /**
     * 功课新增页面
     */
    public function add_action()
    {

        $date = date('Ymd', time());
        $lesson = $this->model('lessons')->get_detail($this->user_id, $date);

        if (empty($lesson)) {
            $lessonMd = array(
                'date' => date('Y-m-d', time()),
                'songjing' => 0,
                'chanhui' => 0,
                'nianfo' => 0,
                'yuezangjing' => "",
                'yuezangpin' => 0,
            );
        } else {
            $lesson = current($lesson);
            $lessonMd = array(
                'date' => date('Y-m-d', time()),
                'songjing' => $lesson['songjing'],
                'chanhui' => $lesson['chanhui'],
                'nianfo' => $lesson['nianfo'],
                'yuezangjing' => $lesson['yuezangjing'],
                'yuezangpin' => $lesson['yuezangpin'],
            );
        }

        TPL::import_js(array(
            'js/laydate/laydate.js'
        ));

        TPL::assign('lesson', $lessonMd);
        TPL::output('lessons/add');
    }

    /**
     * 功课编辑课程
     */
    public function edit_lesson_action()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $uid = (int) $this->user_id;

        $lesson = $this->model('lessons')->get($uid, $id);
        $lesson = current($lesson);

        TPL::import_js(array(
            'js/laydate/laydate.js'
        ));

        TPL::assign('lesson', $lesson);
        TPL::output('lessons/edit');
    }


    /**
     * 最近功课(24小时功课列表)
     */
    public function index_action()
    {

        if(is_mobile()){
            HTTP::redirect("/m/");
        } else {
            $lessons = $this->model('lessons')->lessonOf24Hours();
            TPL::assign('lessons', $lessons);
            TPL::output('lessons/index');
        }


    }

    public function actionsday_action(){
        $lessons = $this->model('lessons')->actionsday();
        $data = array();
        foreach ($lessons as $k => $val) {
            if (!isset($lessons[$k - 1])) {
                $data[] = array(
                    'songjing' => $val['songjing'],
                    'chanhui' => $val['chanhui'],
                    'nianfo' => $val['nianfo'],
                    'date' => strtotime($val['date']) * 1000,
                );
            } else {
                $data[] = array(
                    'songjing' => $val['songjing'] + $data[$k - 1]['songjing'],
                    'chanhui' => $val['chanhui'] + $data[$k - 1]['chanhui'],
                    'nianfo' => $val['nianfo'] + $data[$k - 1]['nianfo'],
                    'date' => strtotime($val['date']) * 1000,
                );
            }

        }
        TPL::assign('lessons', json_encode($data));


        $mylessons = $this->model('lessons')->getAll($this->user_id);
        $mydata = array();
        foreach ($mylessons as $k => $val) {
            if (!isset($lessons[$k - 1])) {
                $mydata[] = array(
                    'songjing' => $val['songjing'],
                    'chanhui' => $val['chanhui'],
                    'nianfo' => $val['nianfo'],
                    'date' => strtotime($val['date']) * 1000,
                );
            } else {
                $mydata[] = array(
                    'songjing' => $val['songjing'] + $mydata[$k - 1]['songjing'],
                    'chanhui' => $val['chanhui'] + $mydata[$k - 1]['chanhui'],
                    'nianfo' => $val['nianfo'] + $mydata[$k - 1]['nianfo'],
                    'date' => strtotime($val['date']) * 1000,
                );
            }

        }
        TPL::assign('mylessons', json_encode($mydata));

        TPL::output('lessons/actionsday');

    }




    public function statistic_action()
    {
        $lessons = $this->model('lessons')->getAll($this->user_id);
        $data = array();
        foreach ($lessons as $k => $val) {
            if (!isset($lessons[$k - 1])) {
                $data[] = array(
                    'songjing' => $val['songjing'],
                    'chanhui' => $val['chanhui'],
                    'nianfo' => $val['nianfo'],
                    'date' => strtotime($val['date']) * 1000,
                );
            } else {
                $data[] = array(
                    'songjing' => $val['songjing'] + $data[$k - 1]['songjing'],
                    'chanhui' => $val['chanhui'] + $data[$k - 1]['chanhui'],
                    'nianfo' => $val['nianfo'] + $data[$k - 1]['nianfo'],
                    'date' => strtotime($val['date']) * 1000,
                );
            }

        }
        TPL::assign('lessons', json_encode($data));
        TPL::output('lessons/statistic');

    }
}