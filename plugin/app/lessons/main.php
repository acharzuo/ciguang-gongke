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
    public function index_action()
    {
        $lessons = $this->model('lessons')->lessions_list($this->user_id, 1);

        TPL::assign('lessons', $lessons);
        TPL::output('lessons/index');
    }

    public function record_action()
    {
        $date = date('Ymd', time());
        $lesson = $this->model('lessons')->get_detail($this->user_id, $date);

        if (empty($lesson)) {
            $lessonMd = array(
                'date' => date('Y-m-d', time()),
                'songjing' => 0,
                'chanhui' => 0,
                'nianfo' => 0,
            );
        } else {
            $lesson = current($lesson);
            $lessonMd = array(
                'date' => date('Y-m-d', time()),
                'songjing' => $lesson['songjing'],
                'chanhui' => $lesson['chanhui'],
                'nianfo' => $lesson['nianfo'],
            );
        }

        TPL::assign('lesson', $lessonMd);
        TPL::output('lessons/record');
    }

    /**
     * 编辑课程
     */
    public function edit_lesson_action()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $uid = (int) $this->user_id;

        $lesson = $this->model('lessons')->get($uid, $id);
        $lesson = current($lesson);

        TPL::assign('lesson', $lesson);
        TPL::output('lessons/edit');
    }

    public function lesson24h_action()
    {

        $lessons = $this->model('lessons')->lessonOf24Hours();
        TPL::assign('lessons', $lessons);
        TPL::output('lessons/24hours');
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