<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/3
 * Time: 10:38
 */

namespace app\controllers\api;


use app\models\Callrecord;
use app\models\Controlrecord;
use app\models\Gateway;
use app\models\Joinrecord;
use app\models\Message;
use app\models\Projects;
use app\models\Stream;
use yii\db\Query;

class IndexController extends CommonController
{
    public function actionIndex()
    {
        $data['project'] = $this -> GetProject();
        $data['gateway'] = $this -> GetGateway();
        $data['callrecord'] = $this -> GetCallRecord();
        $data['controlrecord'] = $this -> GetControlRecord();
        $data['joinrecord'] = $this -> GetJoinRecord();
        $data['message'] = $this -> GetMessage();
        $data['call'] = $this -> getLastCall();
        $this->returnJson(0,'查询成功',$data);
    }

    //获取最后一条报警信息
    public function getLastCall()
    {
        $model = new Callrecord();
        $query = new Query();
        $where = $this -> getMemberRule();
        $data = $query -> from($model::tableName()) -> where($where) -> orderBy('id desc') -> one();
        return $data;
    }

    //查询当前的项目总数与在线数量
    public function GetProject()
    {
        $andfieldwhere = $this -> getMemberRule(true);
        $model = new Projects();
        $query = new Query();
        $data['count'] = $query -> from($model::tableName()) -> where($andfieldwhere) -> select(['id']) -> count();
        $data['online'] = $query -> from($model::tableName())
            -> where(['status' => 1])
            -> andFilterWhere($andfieldwhere)
            -> select(['id'])
            -> count();
        return $data;
    }

    //查询当前的项目总数与在线数量
    public function GetGateway()
    {
        $andfieldwhere = $this -> getMemberRule();
        $model = new Gateway();
        $query = new Query();
        $data['count'] = $query -> from($model::tableName()) -> where($andfieldwhere) -> select(['id']) -> count();
        $data['online'] = $query -> from($model::tableName()) -> andFilterWhere($andfieldwhere) -> where(['status' => 1]) -> select(['id']) -> count();
        return $data;
    }

    //查询当前的报警记录
    public function GetCallRecord()
    {
        $andfieldwhere = $this -> getMemberRule();
        $model = new Callrecord();
        $query = new Query();
        $where = $this -> getWhere(29,'call_date');
        $data['day30'] = $query -> from($model::tableName()) -> where($where)  -> andFilterWhere($andfieldwhere) -> select(['id']) -> count();
        $where = $this -> getWhere(2,'call_date');
        $data['day3'] = $query -> from($model::tableName()) -> where($where)  -> andFilterWhere($andfieldwhere) -> select(['id']) -> count();
        return $data;
    }


    //查询当前控制记录
    public function GetControlRecord()
    {
        $andfieldwhere = $this -> getMemberRule();
        $model = new Controlrecord();
        $query = new Query();
        $where = $this -> getWhere(29,'control_date');
        $data['day30'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($andfieldwhere) -> select(['id']) -> count();
        $where = $this -> getWhere(2,'control_date');
        $data['day3'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($andfieldwhere) -> select(['id']) -> count();
        return $data;
    }


    //查询当前联控记录
    public function GetJoinRecord()
    {
        $andfieldwhere = $this -> getMemberRule();
        $model = new Joinrecord();
        $query = new Query();
        $where = $this -> getWhere(29,'join_date');
        $data['day30'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($andfieldwhere) -> select(['id']) -> count();
        $where = $this -> getWhere(2,'join_date');
        $data['day3'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($andfieldwhere) -> select(['id']) -> count();
        return $data;
    }

    //获取当前的系统消息
    public function GetMessage()
    {
        $model = new Message();
        $query = new Query();
        $data = $query -> from($model::tableName()) -> where(['in','mid',['19',$this -> mid]]) -> orderBy('id desc') -> limit(10) -> select(['id', 'title', 'content', 'add_date']) -> all();
        return $data;
    }

    //查询在线的工程
    public function actionGetprojects($offset,$limit,$keyword=null,$online=null)
    {
        $andfieldwhere = $this -> getMemberRule(true);
        $model = new Projects();
        $gatemodel = new Gateway();
        $query = new Query();
        $wheres = ['status' => 1];
        $where = [];

        if ($keyword){
            $where = ['like','project_name',$keyword];
        }
        $data['totalCount'] = $query -> from($model::tableName())
            -> where($wheres)
            -> andWhere($where)
            -> andFilterWhere($andfieldwhere)
            -> count();
        $data['data'] = $query -> from($model::tableName())
            -> where($wheres)
            -> offset($offset)
            -> limit($limit)
            -> andWhere($where)
            -> andFilterWhere($andfieldwhere)
            -> select(['id', 'project_name', 'status'])
            -> all();
        $query1 = new Query();
        if ($online < 2 && $online != ''){
            if ($online == 1 || $online == 0){
                $where = ['status' => $online];
                $data['data'] = $query -> from($model::tableName()) -> where($where) -> offset($offset) -> limit($limit) -> select(['id', 'project_name', 'status']) -> all();
            }
        }

        $this->returnJson(0, '查询成功',$data);
    }



}