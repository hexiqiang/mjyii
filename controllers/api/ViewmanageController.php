<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/28
 * Time: 17:03
 */

namespace app\controllers\api;

use app\models\Gateway;
use app\models\Stream;
use app\models\Streamrecord;
use app\models\Viewjoincontrol;
use app\models\Viewmanage;
use app\models\Viewmanagejoin;
use app\models\Viewmanagejoindo;
use app\models\Viewmanagejoinmap;
use app\models\Viewmanagejoinvideo;
use Yii;
use yii\db\Query;

class ViewmanageController extends CommonController
{
    public function actionLists($offset, $limit, $pid)
    {
        $andfieldwhere = $this -> getMemberRule();


        $where = [];
        if ($pid){
            $wehre = ['pid' => $pid];
        }
        $query = new Query();
        $data['totalCount'] = $query -> from('mj_view_manage') -> where($where) -> andFilterWhere($andfieldwhere) -> count();
        $data['data'] = $query -> from('mj_view_manage')
            -> offset($offset)
            -> limit($limit)
            -> where($wehre)
            -> andFilterWhere($andfieldwhere)
            -> orderBy('id desc')
            -> all();
        $model = new Viewmanagejoin();
        foreach ($data['data'] as $k => $v){
            $data['data'][$k]['views'] = $query -> from($model::tableName()) -> where(['vid' => $v['id']]) -> orderBy('id desc') -> all();
        }
        $this->returnJson(0,'查询成功',$data);
    }

    public function actionAdd()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();

            $query = new Query();
            $result = $query -> from('mj_view_manage') -> where(['pid'=>$data['pid']]) -> select(['id']) -> one();

            if ($result['id']){
                $pk = $result['id'];
                $viewdata = $this -> returnData($pk, $data);
            }else{
                $pro = $query -> from('mj_project') -> where(['id'=>$data['pid']]) -> select(['project_name']) -> one();
                $params['project_name'] = $pro['project_name'];
                $params['pid'] = $data['pid'];
                $model = new Viewmanage();
                $pk = $model -> add($params);
                if ($pk > 0){
                    $viewdata = $this -> returnData($pk, $data);
                }else{
                    $this->returnJson(-3,'添加失败');
                }
            }
            $model = new Viewmanagejoin();
            $result = $model -> add($viewdata);
            if ($result){
                $this->returnJson(0,'添加成功');
            }else{
                $this->returnJson(-2,'服务器忙请稍后再试');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    public function returnData($pk, $data)
    {
        $viewdata = [
            'view_title' => $data['view_title'],
            'vid' => $pk,
            'note' => $data['note'],
            'pid' => $data['pid'],
            'edit_date' => date('Y-m-d H:i:s',time())
        ];
        return $viewdata;
    }

    public function actionEdit()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $model = new Viewmanagejoin();
            $result = Yii::$app -> db -> createCommand() -> update($model::tableName(),$data,'id='.$data['id']) -> execute();
            if ($result){
                $this->returnJson(0,'编辑成功');
            }else{
                $this->returnJson(-2,'服务器忙请稍后再试');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    public function actionDel()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $model = new Viewmanagejoin();
            $result = Yii::$app -> db -> createCommand() -> delete($model::tableName(),['id' => $data['id']]) -> execute();
            if ($result){
                $this->returnJson(0,'删除成功');
            }else {
                $this->returnJson(-2,'删除失败');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //根据工程ID查询对应的视图
    public function actionSearch()
    {
        $pid = Yii::$app -> request -> get('pid');
        $model = new Viewmanagejoin();
        $query = new Query();
        $data = $query -> from($model::tableName()) -> where(['pid' => $pid]) -> orderBy('id desc') -> select(['view_title', 'id']) -> all();
        $model1 = new Viewjoincontrol();
        $stream = $this -> getStreamRecordData($pid);
        $daystream = $this -> getDayStreamRecordData($pid);
        $video = new Viewmanagejoinvideo();
        $model2 = new Viewmanagejoinmap();
        foreach ($data as $k => $v){
            $data[$k]['views'] = $query -> from($model1::tableName())-> select('*') -> where(['vid' => $v['id']]) -> all();
            $data[$k]['data'] = $this -> getsetting($v['id']);
            $data[$k]['stream'] = $stream;
            $data[$k]['daystream'] = $daystream;
            $note = $query -> from('mj_view_join_control') -> where(['vid' => $v['id'],'cid' => 4]) -> select('note') -> one();
            $data[$k]['note'] =  $note['note'];
            $url = $query -> from('mj_view_join_control') -> where(['vid' => $v['id'],'cid' => 6]) -> select('url') -> one();
            $data[$k]['url'] =  json_decode($url['url'],true);
            $data[$k]['video'] = $video -> lists($v['id']);
            $data[$k]['map_position'] = $model2 -> lists($v['id']);
            $data[$k]['rule'] = $this -> getMemberJuri();
        }
        if ($data){
            $this->returnJson(0, '查询成功', $data);
        }else{
            $this->returnJson(-1, '请添加视图组件');
        }
    }

    //根据工程ID获取对应的每月数据流的相关数据
    public function getStreamRecordData($pid)
    {
        $projectQuery = new Query();
        $project = $projectQuery -> from('mj_project') -> where(['id' => $pid]) -> select(['project_name'])-> one();
        $gatewayQuery = new Query();
        $model = new Gateway();
        $gateway = $gatewayQuery -> from($model::tableName()) -> where(['pid' => $pid]) -> select(['id','gateway_name','gateway_id']) -> all();
        $streamQuery = new Query();
        $model = new Stream();
        $streamid = [];
        foreach ($gateway as $k => $v){
            $s = $streamQuery -> from($model::tableName()) -> where(['gid'=> $v['id']]) -> select(['id','stream_name','comp']) -> all();
            foreach ($s as $ke => $va){
                array_push($streamid,['id' => $va['id'],'stream_name' => $va['stream_name'],'comp' => $va['comp'],'gateway_name' => $v['gateway_name'],'gateway_id' => $v['gateway_id'],'project_name' => $project['project_name']]);
            }
        }
        foreach ($streamid as $key => $val){
            $data = $this -> getMonthData($val['gateway_id'],$val['id']);
            foreach ($data as $k => $v){
                $streamid[$key][$k+1] = $v['count'];
            }
        }
        return $streamid;
    }
    //根据工程ID获取对应的每日数据流的相关数据
    public function getDayStreamRecordData($pid)
    {
        $projectQuery = new Query();
        $project = $projectQuery -> from('mj_project') -> where(['id' => $pid]) -> select(['project_name'])-> one();
        $gatewayQuery = new Query();
        $model = new Gateway();
        $gateway = $gatewayQuery -> from($model::tableName()) -> where(['pid' => $pid]) -> select(['id','gateway_name','gateway_id']) -> all();
        $streamQuery = new Query();
        $model = new Stream();
        $streamid = [];
        foreach ($gateway as $k => $v){
            $s = $streamQuery -> from($model::tableName()) -> where(['gid'=> $v['id']]) -> select(['id','stream_name','comp']) -> all();
            foreach ($s as $ke => $va){
                array_push($streamid,['id' => $va['id'],'stream_name' => $va['stream_name'],'comp' => $va['comp'],'gateway_name' => $v['gateway_name'],'gateway_id' => $v['gateway_id'],'project_name' => $project['project_name']]);
            }
        }
        foreach ($streamid as $key => $val){
            $days = $this -> getDaysData($val['gateway_id'],$val['id']);
            foreach ($days as $k => $v){
                $streamid[$key][$k+1] = $v['count'];
            }
        }
        return $streamid;
    }

    //数据统计每月
    public function getMonthData($gateway_id, $sid, $year=null)
    {

        $month = $this -> getMonth();
        $model = new Streamrecord();
        foreach ($month as $k => $v){
            $count = $model -> getMonthData($gateway_id, $sid, $v['month'],$year);
            $month[$k]['count'] = $count;
        }
        return $month;
    }

    //数据统计本月每日的数据
    public function getDaysData($gateway_id, $sid)
    {
        $day = $this -> getDays();
        $model = new Streamrecord();
        foreach ($day as $k => $v){
            $day[$k]['count'] = '';
            $count = $model -> getDayData($gateway_id, $sid, $v['day']);
            $day[$k]['count'] = $count;
        }
        return $day;
    }

    //根据视图ID查询对应的工程返回对应的网关数据
    public function actionGetprojectviewgateway($vid)
    {
        // 查询视图的对应工程ID
        $model = new Viewmanagejoin();
        $query = new Query();
        $result = $query -> from($model::tableName()) -> where(['id' => $vid]) -> select('pid') -> one();
        // 查询工程对应的网关
        $gateway = new Gateway();
        $query1 = new Query();
        $data = $query1 -> from($gateway::tableName()) -> where(['pid' => $result['pid']]) -> select(['id','gateway_name']) -> all();
        $this->returnJson(0,'查询成功',$data);
    }

    public function actionPostdata()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $model = new Viewmanagejoin();
            $query = new Query();
            $result = $query -> from($model::tableName()) -> where(['id' => $data['vid']]) -> select('pid') -> one();
            $project = $query -> from('mj_project') -> where(['id' => $result['pid']]) -> select('project_name') -> one();
            $gateway = $query -> from('mj_gateway') -> where(['id' => $data['gid']]) -> select('gateway_name') -> one();
            $stream = $query -> from('mj_stream') -> where(['id' => $data['sid']]) -> select('stream_name') -> one();
            $data['pid'] = $result['pid'];
            $data['project_name'] = $project['project_name'];
            $data['gateway_name'] = $gateway['gateway_name'];
            $data['stream_name'] = $stream['stream_name'];
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $model = new Viewmanagejoindo();
            $result = $model -> add($data);
            if ($result){
                $result = $this -> getsetting($data['vid']);
                $this->returnJson(0,'设置成功',$result);
            }else{
                $this -> returnJson(-1,'服务器忙请稍后再试');
            }
        }else{
            $this -> returnJson(-1,'请提交合法数据');
        }
    }

    public function actionEditdata()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $model = new Viewmanagejoin();
            $query = new Query();
            $result = $query -> from($model::tableName()) -> where(['id' => $data['vid']]) -> select('pid') -> one();
            $project = $query -> from('mj_project') -> where(['id' => $result['pid']]) -> select('project_name') -> one();
            $gateway = $query -> from('mj_gateway') -> where(['id' => $data['gid']]) -> select('gateway_name') -> one();
            $stream = $query -> from('mj_stream') -> where(['id' => $data['sid']]) -> select('stream_name') -> one();
            $data['pid'] = $result['pid'];
            $data['project_name'] = $project['project_name'];
            $data['gateway_name'] = $gateway['gateway_name'];
            $data['stream_name'] = $stream['stream_name'];
            $data['add_date'] = date('Y-m-d H:i:s',time());
            unset($data['record']);
            unset($data['comp']);
            $model = new Viewmanagejoindo();
            $result = $model -> edit($data);
            if ($result){
                $result = $this -> getsetting($data['vid']);
                $this->returnJson(0,'设置成功',$result);
            }else{
                $this -> returnJson(-1,'服务器忙请稍后再试');
            }
        }else{
            $this -> returnJson(-1,'请提交合法数据');
        }
    }

    // 查询当前组件的设置项
    public function actionGetsetting($vid)
    {
        $data = $this -> getsetting($vid);
        $this->returnJson(0,'success',$data);
    }
    //查询{
    public function getsetting($vid)
    {
        $model = new Viewmanagejoindo();
        $query = new Query();
        $data['show_data'] = $query -> from($model::tableName()) -> where(['vid' => $vid, 'is_data_show' => 1]) -> all();
        $data['show_data'] = $this -> getLastData($data['show_data']);
        $data['doing_type'] = $query -> from($model::tableName()) -> where(['vid' => $vid]) -> andWhere(['>','doing_type',0]) -> all();
        $data['doing_type'] = $this -> getLastData($data['doing_type']);

        return $data;
    }

    public function getLastData($data)
    {
        $model = new Streamrecord();
        $query = new Query();
        $stream = new Stream();
        $query1 = new Query();
        $gateway = new Gateway();
        $query2 = new Query();
        foreach ($data as $k => $v){
            $last_data = $query -> from($model::tableName()) -> where(['sid' => $v['sid']]) -> orderBy('get_date desc') -> select(['value']) -> one();
            $comp = $query1 -> from($stream::tableName()) -> where(['id' => $v['sid']])  -> select(['comp']) -> one();
            $data[$k]['record'] = $last_data['value'];
            $data[$k]['comp'] = $comp['comp'];
            $st = $query2 -> from($gateway::tableName()) -> where(['id' => $v['gid']]) -> select('status') -> one();
            $data[$k]['status'] = (int)$st['status'];
        }
        return $data;
    }

    //接收视图的数据提交
    public function actionPostnote()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $query = new Query();
            $model = new Viewjoincontrol();
            $id = $query -> from($model::tableName()) -> where(['vid' => $data['vid'],'cid' => $data['cid']]) -> select(['id']) -> one();
            $result = Yii::$app -> db -> createCommand() -> update($model::tableName(),$data,'id='.$id['id']) -> execute();
            if ($result){
                $this->returnJson(0, '提交成功');
            }else{
                $this->returnJson(-2, '服务器忙请稍后再试');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }


    //保存自定义链接

    public function actionPosturl()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $vid = $data['vid'];
            $cid = $data['cid'];
            $where['vid'] = $vid;
            $where['cid'] = $cid;
            $field['url'] = json_encode($data['data']);
            $result = Yii::$app -> db -> createCommand() -> update('mj_view_join_control',$field,$where) -> execute();
            if ($result){
                $this->returnJson(0, '提交成功', $data['data']);
            }else{
                $this->returnJson(-2, '服务器忙请稍后再试');
            }
        }else{
            $this -> returnJson(-1,'请提交合法数据');
        }
    }
    
    
    //删除组件下的子组件
    public function actionDelassembly()
    {
        if (Yii::$app -> request -> isPost){
            $id = Yii::$app -> request -> post('id');
            $model = new Viewmanagejoindo();
            $re = Yii::$app -> db -> createCommand() -> delete($model::tableName(), 'id='.$id) -> execute();
            if ($re){
                $this -> returnJson(0,'删除成功');
            }else{
                $this -> returnJson(-2,'服务器忙请稍后再试');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

}