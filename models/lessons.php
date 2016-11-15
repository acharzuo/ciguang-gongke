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


    /**
     * 获取单个用户的指定时间的功课
     * @param $uid
     * @param $data
     * @return array
     */
    public function get_detail($uid, $data)
    {
        return $this->fetch_all($this->_table_name, 'uid=' . $uid . ' AND date=' . $data);
    }

    /**
     * 获取指定ID的
     * @param $uid
     * @param $id
     * @return array
     */
    public function get($uid, $id)
    {
        $uid = (int) $uid;
        $id = (int) $id;

        return $this->fetch_all($this->_table_name, 'uid=' . $uid . ' AND id=' . $id);
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
            'nianfoshichang' => $lessons['nianfoshichang'],
            'dizangchan' => $lessons['dizangchan'],
            'votes' => 2,   // 默认给大家点个赞
            'add_time' => time()
        );

       $ret = $this->insert($this->_table_name, $data);

        // 添加动态
        if($ret){
            ACTION_LOG::save_action($uid, $ret, ACTION_LOG::CATEGORY_LESSON, ACTION_LOG::ADD_LESSON_VOTE);

        }

        return $ret;
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
            'yuezangpin',
            'nianfoshichang',
            'dizangchan'
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

        $ret = $this->update($this->_table_name, $lessons, 'uid=' . $uid . ' AND date=' . $date);

        // 添加动态
        if($ret){
            ACTION_LOG::save_action($uid, $ret, ACTION_LOG::CATEGORY_LESSON, ACTION_LOG::ADD_LESSON_VOTE);

        }

        return $ret;
    }


    /**
     * 一个活动时的汇总
     * @param int $beginDate
     * @param int $lastDate
     */
    public function  actionsum($beginDate = 0 , $lastDate = 0){

        $sql = "select ";

        // 计算和, 念佛时长(分) ,按照 每分钟100声佛号最后再兑换为千声计算
        $sql .= " sum(songjing) songjing, sum(chanhui) + sum(dizangchan) chanhui, ceil(sum(nianfo)/1000 + sum(nianfoshichng) / 10) nianfo ";
        $sql .= " from " . $this->prefix . $this->_table_name ;
        $where = " 1 = 1 ";
        if ($beginDate) {
            $where .= " and date >= " . $beginDate;
        }
        if ($lastDate) {
            $where .= " and date <= " . $lastDate;
        }

        if (AWS_APP::config()->get('system')->debug)
        {
            AWS_APP::debug_log('活动汇总SQL:', $sql);
            AWS_APP::debug_log('活动汇总Where:', $where);

        }

        $res = $this->query_all($sql, null, null ,$where);

        return $res;

    }

    /**
     * 一个活动时的汇总
     * @param int $beginDate
     * @param int $lastDate
     */
    public function  actionsday($beginDate = 0 , $lastDate = 0){


        $sql = " SELECT ";
        $sql .= " date                    date, ";
        $sql .= " sum(songjing)            songjing, ";
        $sql .= " sum(chanhui)           chanhui, ";
        $sql .= " ceil(sum(nianfo) / 1000 + sum(nianfoshichang) / 10 ) nianfo ";
        $sql .= " FROM " . $this->prefix . $this->_table_name  ;

        $sql .= " WHERE 1 = 1 ";
        if ($beginDate) {
            $sql .= " and date >= " . $beginDate ;
        }
        if ($lastDate) {
            $sql .= " and date <= " . $lastDate ;
        }



        $sql .= " GROUP BY date ";
        $sql .= " ORDER BY date asc ";



        if (AWS_APP::config()->get('system')->debug)
        {
            AWS_APP::debug_log('活动汇总SQL:', $sql);

        }

        $res = $this->query_all($sql);

        return $res;

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

    /**
     * 查询最近的100条功课明细,并显示点赞数量,以及登录人是否对此功课点赞
     * @return array
     */
    public function index($uid)
    {
        $date = date('Y-m-d H:i:s', strtotime('-1 day'));

        // 获取首页要显示的数据, 当前显示最近100条数据
        $res = $this->fetch_all($this->_table_name, null , "add_time desc",  100);   // 查询最新的500条记录

        $uids = array();
        $ids = array();

        // 获取所有功课的人的uid,id为下面获取用户信息做准备
        foreach ($res as $k => $v) {
            $uids[$v['uid']] = $v['uid'];
            $ids[$v['id']] = $v['id'];
        }

        // 获取用户列表
        $users = $this->model('account')->get_user_info_by_uids(array_keys($uids));
        $votes= $this->model('lessons')->get_lessons_vote_by_ids('lessons', $ids, 1, $uid);

        // 将用户信息/点赞用户信息加入的返回的数据中
        foreach ($res as $id => $val) {
                $res[$id]['user_info'] = $users[$res[$id]['uid']];
                $res[$id]['vote_info'] = $votes[$res[$id]['id']];
        }

        return $res;
    }

    /**
     *
     * 批量获取指定用户对于功课的赞叹信息
     * @param $type
     * @param $item_ids
     * @param null $rating
     * @param null $uid
     * @return bool
     */
    public function get_lessons_vote_by_ids($type, $item_ids, $rating = null, $uid = null)
    {
        if (!is_array($item_ids))
        {
            return false;
        }

        if (sizeof($item_ids) == 0)
        {
            return false;
        }

        array_walk_recursive($item_ids, 'intval_string');

        $where[] = "`type` = '" . $this->quote($type) . "'";
        $where[] = 'item_id IN(' . implode(',', $item_ids) . ')';

        if ($rating)
        {
            $where[] = 'rating = ' . intval($rating);
        }

        if ($uid)
        {
            $where[] = 'uid = ' . intval($uid);
        }

        if ($lessons_votes = $this->fetch_all('lessons_vote', implode(' AND ', $where)))
        {
            foreach ($lessons_votes AS $key => $val)
            {
                $result[$val['item_id']] = $val;
            }
        }

        return $result;
    }


    /**
     * 批量获取功课中投票用户的信息
     * @param $type
     * @param $item_ids
     * @param null $rating
     * @param null $limit
     * @return bool
     */
    public function get_lessons_vote_users_by_ids($type, $item_ids, $rating = null, $limit = null)
    {
        if (! is_array($item_ids))
        {
            return false;
        }

        if (sizeof($item_ids) == 0)
        {
            return false;
        }

        array_walk_recursive($item_ids, 'intval_string');

        $where[] = "`type` = '" . $this->quote($type) . "'";
        $where[] = 'item_id IN(' . implode(',', $item_ids) . ')';

        if ($rating)
        {
            $where[] = 'rating = ' . intval($rating);
        }

        if ($lessons_votes = $this->fetch_all('lessons_vote', implode(' AND ', $where)))
        {
            foreach ($lessons_votes AS $key => $val)
            {
                $uids[$val['uid']] = $val['uid'];
            }

            $users_info = $this->model('account')->get_user_info_by_uids($uids);

            foreach ($lessons_votes AS $key => $val)
            {
                $vote_users[$val['item_id']][$val['uid']] = $users_info[$val['uid']];
            }

            return $vote_users;
        }
    }

    /**
     *
     * 获取点赞的用户的ID
     * @param $type  类型:lessons
     * @param $item_id 项目ID: LessonID
     * @param null $rating
     * @param null $limit
     * @return mixed
     */
    public function get_lessons_vote_users_by_id($type, $item_id, $rating = null, $limit = null)
    {
        $where[] = "`type` = '" . $this->quote($type) . "'";
        $where[] = 'item_id = ' . intval($item_id);

        if ($rating)
        {
            $where[] = 'rating = ' . intval($rating);
        }

        if ($article_votes = $this->fetch_all('lessons_vote', implode(' AND ', $where)))
        {
            foreach ($article_votes AS $key => $val)
            {
                $uids[$val['uid']] = $val['uid'];
            }

            return $this->model('account')->get_user_info_by_uids($uids);
        }
    }


    public function getAll($uid)
    {
        $uid = (int) $uid;
        $result = $this->fetch_all($this->_table_name, " uid=" . $uid, " date ASC");
        return $result;
    }

    /**
     * 根据功课ID获取功课内容,并缓存到全局中
     * @param $lesson_id 功课ID
     * @return 功课信息
     */
    public function get_lesson_by_id($lesson_id)
    {
        if (!is_digits($lesson_id))
        {
            return false;
        }

        static $lessons;

        if (!$lessons[$lesson_id])
        {
            $lessons[$lesson_id] = $this->fetch_row('user_lessons', 'id = ' . $lesson_id);
        }

        return $lessons[$lesson_id];
    }

    /**
     * 功课点赞!
     * @param $type
     * @param $item_id
     * @param $rating
     * @param $uid
     * @param $reputation_factor
     * @param $item_uid
     * @return bool
     * @throws Exception
     * @throws Zend_Exception
     */
    public function  lesson_vote($type, $item_id, $rating, $uid, $reputation_factor, $item_uid)
    {
        $this->delete('lessons_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . ' AND uid = ' . intval($uid));



        if ($rating)
        {
            if ($article_vote = $this->fetch_row('lessons_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . " AND rating = " . intval($rating) . ' AND uid = ' . intval($uid)))
            {
                $this->update('lessons_vote', array(
                    'rating' => intval($rating),
                    'time' => time(),
                    'reputation_factor' => $reputation_factor
                ), 'id = ' . intval($article_vote['id']));
            }
            else
            {
                $this->insert('lessons_vote', array(
                    'type' => $type,
                    'item_id' => intval($item_id),
                    'rating' => intval($rating),
                    'time' => time(),
                    'uid' => intval($uid),
                    'item_uid' => intval($item_uid),
                    'reputation_factor' => $reputation_factor
                ));
            }
        }

        switch ($type)
        {
            case 'lessons':
                $this->update('user_lessons', array(
                    'votes' => $this->count('lessons_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . " AND rating = 1")
                ), 'id = ' . intval($item_id));

                switch ($rating)
                {
                    case 1:
                        ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_LESSON, ACTION_LOG::ADD_LESSON_VOTE);
                        break;

                    case -1:
                        ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_LESSON . ' AND associate_action = ' . ACTION_LOG::ADD_LESSON_VOTE . ' AND uid = ' . intval($uid) . ' AND associate_id = ' . intval($item_id));
                        break;
                }
                break;
        }
        $this->model('account')->sum_user_agree_count($item_uid);

        return true;
    }


}
