<?php
/**
 * Created by PhpStorm.
 * User: deliang
 * Date: 9/19/16
 * Time: 2:44 PM
 */
if (!defined('IN_ANWSION'))
{
    die;
}

class lessons_class extends AWS_MODEL
{

    protected $_table_name = 'user_lessons';

    public function get_detail($uid, $data)
    {
        return $this->fetch_all($this->_table_name, 'uid=' . $uid . ' AND date=' . $data);
    }

    public function get($uid, $id)
    {
        $uid = (int) $uid;
        $id = (int) $id;

        return $this->fetch_all($this->_table_name, 'uid=' . $uid . ' AND id=' . $id);
    }

    function index_action()
    {
        HTTP::redirect('/lessons/');
    }

    /**
     * 添加功课
     *
     * @param $uid
     * @param array $lessons
     * @return int
     * @throws Zend_Exception
     */
    public function add_lessons($uid, array $lessons)
    {
        $data = array(
            'uid' => $uid,
            'songjing' => $lessons['songjing'],
            'chanhui' => $lessons['chanhui'],
            'nianfo' => $lessons['nianfo'],
            'yuezangjing' => htmlspecialchars($lessons['yuezangjing']),
            'yuezangpin' => $lessons['yuezangpin'],
            'date' => $lessons['date'],
            'create_time' => date('Y-m-d H:i:s', time()),
        );

        return $this->insert($this->_table_name, $data);
    }

    /**
     * 修改功课
     *
     * @param $uid
     * @param $date
     * @param array $lessons
     * @return bool
     */
    public function update_lessons($uid, $date, array $lessons)
    {
        $uid = (int) $uid;
        $date = (int) $date;

        $md = array(
            'songjing',
            'chanhui',
            'nianfo',
            'yuezangjing',
            'yuezangpin'
        );
        $md = array_combine($md, $md);

        foreach ($lessons as $k => $v) {
            if (!isset($md[$k])) {
                unset($lessons[$k]);
            }
        }

        if (empty($lessons)) {
            return false;
        }

        return $this->update($this->_table_name, $lessons, 'uid=' . $uid . ' AND date=' . $date);
    }

    /**
     * 获取功课的列表
     *
     * @param $uid
     * @param int $page
     * @return array
     */
    public function lessons_list($uid, $page = 1)
    {
        //todo 需要改造 不能一次去很多的数据
        $uid = (int) $uid;
        return $this->fetch_page($this->_table_name, 'uid='.$uid, 'id', $page);
    }

    public function lessonOf24Hours()
    {
        $date = date('Y-m-d H:i:s', strtotime('-1 day'));
        $res = $this->fetch_all($this->_table_name, null , null,  500);   // 查询最新的500条记录
//        $result = array();

        $uids = array();

        foreach ($res as $k => $v) {
            $uids[$v['uid']] = $v['uid'];
//            if (!isset($result[$v['uid']])) {
//                $result[$v['uid']] = array(
//                    'uid' => $v['uid'],
//                    'songjing' => $v['songjing'],
//                    'chanhui' => $v['chanhui'],
//                    'nianfo' => $v['nianfo'],
//                    'yuezangjing' => $v['yuezangjing'],
//                    'yuezangpin' => $v['yuezangpin'],
//                );
//            } else {
//                $result[$v['uid']] = array(
//                    'uid' => $v['uid'],
//                    'songjing' => $v['songjing'] + $result[$v['uid']]['songjing'],
//                    'chanhui' => $v['chanhui'] + $result[$v['uid']]['chanhui'],
//                    'nianfo' => $v['nianfo'] + $result[$v['uid']]['nianfo'],
//                    'yuezangjing' => $v['yuezangjing'] ,
//                    'yuezangpin' => $v['yuezangpin'] + $result[$v['uid']]['yuezangpin'],
//                );
//            }
        }

        if (AWS_APP::config()->get('system')->debug)
        {
            AWS_APP::debug_log('console', var_dump($res));
        }

        $users = $this->model('account')->get_user_info_by_uids(array_keys($uids));
        if (AWS_APP::config()->get('system')->debug)
        {
            AWS_APP::debug_log('console', var_dump($users));
        }

        foreach ($res as $id => $val) {
           // if (isset($users[$uid]['user_name'])) {
                $res[$id]['user_name'] = $users[$res[$id]['uid']]['user_name'];
          //  } else {
          //      $res[$uid]['user_name'] = "佚名";
         //   }
            if (AWS_APP::config()->get('system')->debug)
            {
                AWS_APP::debug_log('console', $uid . "---Userf:" . $users[$uid]['user_name']);
            }
        }

        return $res;
    }

    public function getAll($uid)
    {
        $uid = (int) $uid;
        $result = $this->fetch_all($this->_table_name, " uid=" . $uid, " date ASC");
        return $result;
    }

}
