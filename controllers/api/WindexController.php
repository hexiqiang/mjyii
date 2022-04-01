<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/3
 * Time: 10:38
 */

namespace app\controllers\api;


use app\models\Call;
use app\models\Callrecord;
use app\models\Controlrecord;
use app\models\Gateway;
use app\models\Joinrecord;
use app\models\Member;
use app\models\Message;
use app\models\Projects;
use app\models\Stream;
use app\models\Streamrecord;
use app\models\Streamtiming;
use app\models\Viewjoincontrol;
use app\models\Viewmanage;
use app\models\Viewmanagejoin;
use app\models\Viewmanagejoindo;
use app\models\Viewmanagejoinmap;
use app\models\Viewmanagejoinvideo;
use yii\db\Query;
use Yii;

class WindexController extends CommonController
{
    //获取首页内容
    public function actionIndex()
    {
        $pid = Yii::$app -> request -> get('pid');
        $data['callrecord'] = $this -> GetCallRecord($pid);
        $data['controlrecord'] = $this -> GetControlRecord($pid);
        $data['joinrecord'] = $this -> GetJoinRecord($pid);
        $this->returnJson(0,'查询成功',$data);
    }

    //获取全部网关
    public function actionGetgateways($offset, $limit, $pid=null)
    {
        $andfieldwhere = $this -> getMemberRule();
        $whereField = [];
        if ($pid){
            $whereField = ['pid' => $pid];
        }
        $model = new Gateway();
        $query = new Query();
        $data = $query -> from($model::tableName()) -> orderBy('id desc') -> where($whereField) -> andFilterWhere($andfieldwhere) -> select(['id','status','gateway_name']) -> offset($offset) -> limit($limit) -> all();
        $model = new Callrecord();
        $query = new Query();
        foreach ($data as $k => $v){
            $result = $query -> from($model::tableName()) -> where(['gid' => $v['id']])  -> andFilterWhere($andfieldwhere) -> orderBy('id desc') -> select(['call_status']) -> one();
            $data[$k]['status'] = $result['call_status'] == '报警中' ? '2' : $data[$k]['status'];

        }
        $this->returnJson(0,'查询成功',$data);
    }


    //查询当前的报警记录
    public function GetCallRecord($pid=null)
    {
        $andfieldwhere = $this -> getMemberRule();
        $whereField = [];
        if ($pid){
            $whereField = ['pid' => $pid];
        }
        $model = new Callrecord();
        $query = new Query();
        $where = $this -> getWhere(5,'call_date');
        $data['day5'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($whereField) -> andFilterWhere($andfieldwhere) -> select(['id']) -> count();
        $where = $this -> getWhere(2,'call_date');
        $data['day3'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($whereField) -> andFilterWhere($andfieldwhere) -> select(['id']) -> count();
        return $data;
    }


    //查询当前控制记录
    public function GetControlRecord($pid=null)
    {
        $whereField = [];
        if ($pid){
            $whereField = ['pid' => $pid];
        }
        $model = new Controlrecord();
        $query = new Query();
        $where = $this -> getWhere(5,'control_date');
        $data['day5'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($whereField) -> select(['id']) -> count();
        $where = $this -> getWhere(2,'control_date');
        $data['day3'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($whereField) -> select(['id']) -> count();
        return $data;
    }


    //查询当前联控记录
    public function GetJoinRecord($pid=null)
    {
        $whereField = [];
        if ($pid){
            $whereField = ['pid' => $pid];
        }
        $model = new Joinrecord();
        $query = new Query();
        $where = $this -> getWhere(5,'join_date');
        $data['day5'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($whereField) -> select(['id']) -> count();
        $where = $this -> getWhere(2,'join_date');
        $data['day3'] = $query -> from($model::tableName()) -> where($where) -> andFilterWhere($whereField) -> select(['id']) -> count();
        return $data;
    }

    //获取预警信息
    public function actionGetcalldata($offset, $limit, $sid=null, $gid=null, $pid=null, $tid=null)
    {
        $andfieldwhere = $this->getMemberRule();
        $model = new Callrecord();
        $query = new Query();
        $where = [];
        if ($sid){
            $where = ['sid' => $sid];
        }
        if ($gid){
            $where = ['gid' => $gid];
        }
        if ($pid){
            $where = ['pid' => $pid];
        }
        if ($tid){
            $where = ['tid' => $tid];
        }
        $data = $query -> from($model::tableName())
            -> where($where)
            -> andFilterWhere($andfieldwhere)
            -> offset($offset)
            -> limit($limit)
            -> orderBy('id desc')
            -> select(['call_message','id','call_name', 'cid', 'tid', 'call_date','call_type','call_status'])
            -> all();

        foreach ($data as $k => $v){
            if ($v['call_status'] == '报警中'){
                $data[$k]['switch'] = true;
            }else{
                $data[$k]['switch'] = false;
            }
        }
        $this->returnJson(0,'查询成功',$data);
    }
    //关闭报警信息
    public function actionCallstatus()
    {
        if (Yii::$app -> request -> isPut){
            $data = Yii::$app -> request -> post();
            $model = new Callrecord();
            $result = $model -> edit($data);
            if ($result){
                $this->returnJson(0,'报警恢复');
            }else{
                $this->returnJson(-2,'服务器忙');
            }
        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }

    //获取用户资料
    public function actionGetuserdata($mid)
    {
        $model =new Member();
        $query = new Query();
        $field = ['member', 'show_password', 'phone', 'email', 'note','id'];
        $data = $query -> from($model::tableName()) -> where(['id' => $mid]) -> select($field) -> one();
        $data['note'] = $data['note'] ? $data['note'] : '';
        $data['phone'] = $data['phone'] ? $data['phone'] : '';
        $data['email'] = $data['email'] ? $data['email'] : '';
        $this->returnJson(0,'success', $data);
    }
    
    //编辑用户资料
    public function actionEdituser()
    {
        if (Yii::$app -> request -> isPut){
            $data = Yii::$app -> request -> post();
            if ($data['password']){
                $data['show_password'] = $data['password'];
                $data['password'] = md5($data['password']);
            }
            $data['edit_time'] = date('Y-m-d H:i:s',time());
            $member = new Member();
            $field = $member -> getMember($data['member'],$data['id']);
            if ($field){
                $this->returnJson(-3,'账号已存在');
                return;
            }
            $model = new Member();
            $result = $model -> saveEditMember($data);
            if ($result){
                $this->returnJson(0,'编辑成功');
            }else{
                $this->returnJson(-2,'请提交要修改的信息');
            }
        }else{
            $this->returnJson(-1,'提交的数据不合法');
        }
    }
    
    //获取管理员发布的信息
    public function actionGetsystemmessage($offset, $limit, $type)
    {
        $model = new Message();
        $query = new Query();
        if ($type == 'admin'){
            $where = ['member' => $type];
        }else{
            $where = ['!=' , 'member', 'admin'];
        }

        $data = $query -> from($model::tableName()) -> where($where) -> orderBy('id desc') -> offset($offset) -> limit($limit) -> all();
        $this->returnJson(0,'success',$data);
    }
    //发布系统消息
    public function actionSavemessage()
    {
        if(Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $model = new Message();
            $result = $model -> add($data);
            if ($result){
                $this -> returnJson(0,'发布成功');
            }else{
                $this -> returnJson(-2,'服务器忙，请稍后再试');
            }
        }else{
            $this -> returnJson(-1,'请提交合法数据');
        }
    }


    //获取工程的对应视图组件
    public function actionGetprojectviews($pid)
    {
        $model = new Viewmanage();
        $query = new Query();
        $result = $query -> from($model::tableName()) -> where(['pid' => $pid]) -> select(['id']) -> one();
        if ($result){
            $model = new Viewmanagejoin();
            $query = new Query();
            // 查询到对应的工程包含的视图名称
            $result = $query -> from($model::tableName()) -> where(['vid' => $result['id'], 'pid' => $pid]) -> select(['id', 'view_title']) -> all();
            if ($result){
                $model = new Viewjoincontrol();
                $query = new Query();
                $domodel = new Viewmanagejoindo();
                $doquery = new Query();
                //获取对应视图的对应的组件
                $gateway = new Gateway();
                $query1 = new Query();
                $stream = new Streamrecord();
                $streamQuery = new Query();
                $video = new Viewmanagejoinvideo();
                $videoquery = new Query();
                $map = new Viewmanagejoinmap();
                $mapquery = new Query();
                foreach ($result as $k => $v){
                    $res = $query -> from($model::tableName()) -> where(['vid' => $v['id']]) -> all();
                    $re = $doquery -> from($domodel::tableName()) -> where(['vid' => $v['id'], 'pid' => $pid]) -> andFilterWhere(['>', 'doing_type',0]) -> select(['stream_name', 'sid','doing_type','gid','do_name']) -> all();
                    foreach ($re as $key => $va){
                        $st = $query1 -> from($gateway::tableName()) -> where(['id' => $va['gid']]) -> select('status') -> one();
                        $re[$key]['status'] = $st['status'];
                    }
                    $result[$k]['views'] = $res;
                    $result[$k]['stream'] = $streamQuery -> from($stream::tableName()) -> where(['pid' => $pid]) -> select(['get_date', 'value']) -> orderBy('get_date desc')-> one();
                    $result[$k]['control'] = $re;
                    $result[$k]['video'] = $videoquery -> from($video::tableName()) -> where(['vid' => $v['id']]) -> all();
                    $m = $mapquery -> from($map::tableName()) -> where(['vid' => $v['id']]) -> one();
                    $m['map_position'] = json_decode($m['map_position'],true);
                    $result[$k]['map'] = $m;
                }


                $this->returnJson('0','success',$result);
            }else{
                $this->returnJson('-1','暂无视图组件');
            }
        } else{
            $this->returnJson('-1','暂无视图组件');
        }
    }

    //获取本月每日统计报表的数据
    public function actionGetdaystreamrecorddata($pid)
    {

        $projectQuery = new Query();
        $project = $projectQuery -> from('mj_project') -> where(['id' => $pid]) -> select(['project_name'])-> one();
        $gatewayQuery = new Query();
        $model = new Gateway();
        $gateway = $gatewayQuery -> from($model::tableName()) -> where(['pid' => $pid]) -> select(['id','gateway_name','gateway_id']) -> all();
        $streamQuery = new Query();
        $model = new Stream();
        $stream = [
            'days' => [],
            'data' => [],
            'detail' => []
        ];
        foreach ($gateway as $k => $v){
            $s = $streamQuery -> from($model::tableName()) -> where(['gid'=> $v['id']]) -> select(['id','stream_name','comp']) -> all();
            foreach ($s as $ke => $va){
                array_push($stream['detail'],['id' => $va['id'],'stream_name' => $va['stream_name'],'comp' => $va['comp'],'gateway_name' => $v['gateway_name'],'gateway_id' => $v['gateway_id'],'project_name' => $project['project_name']]);
            }
        }
        foreach ($stream['detail'] as $key => $val){
            $days = $this -> getDaysData($val['gateway_id'],$val['id']);
            $stream['data'][$key]['list'] = [];
            foreach ($days as $k => $v){
                if ($key == 0){
                    array_push($stream['days'],$k + 1);
                }
                $stream['data'][$key]['name'] = $val['stream_name'];
                array_push($stream['data'][$key]['list'],['count' => $v['count']]);
            }
        }
        $this->returnJson(0,'success',$stream);
    }
    //获去工程的每月数据
    public function actionGetstreamrecorddata($pid)
    {
        $projectQuery = new Query();
        $project = $projectQuery -> from('mj_project') -> where(['id' => $pid]) -> select(['project_name'])-> one();
        $gatewayQuery = new Query();
        $model = new Gateway();
        $gateway = $gatewayQuery -> from($model::tableName()) -> where(['pid' => $pid]) -> select(['id','gateway_name','gateway_id']) -> all();
        $streamQuery = new Query();
        $model = new Stream();
        $stream = [
            'days' => [],
            'data' => [],
            'detail' => []
        ];
        foreach ($gateway as $k => $v){
            $s = $streamQuery -> from($model::tableName()) -> where(['gid'=> $v['id']]) -> select(['id','stream_name','comp']) -> all();
            foreach ($s as $ke => $va){
                array_push($stream['detail'],['id' => $va['id'],'stream_name' => $va['stream_name'],'comp' => $va['comp'],'gateway_name' => $v['gateway_name'],'gateway_id' => $v['gateway_id'],'project_name' => $project['project_name']]);
            }
        }
        foreach ($stream['detail'] as $key => $val){
            $data = $this -> getMonthData($val['gateway_id'],$val['id']);
            $stream['data'][$key]['list'] = [];
            foreach ($data as $k => $v){
                if ($key == 0){
                    array_push($stream['days'],$k + 1);
                }
                $stream['data'][$key]['name'] = $val['stream_name'];
                array_push($stream['data'][$key]['list'],['count' => $v['count']]);
            }
        }
        $this->returnJson(0,'success',$stream);
    }
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

    //查询工程的对应的数据流历史
    public function actionProjectstreamrecords($pid, $offset, $limit)
    {
        $andfieldwhere = $this -> getMemberRule();
        //先查询该工程下的全部网关
        $model = new Callrecord();
        $query = new Query();
        $data = $query -> from($model::tableName()) -> where(['pid' => $pid]) -> andFilterWhere($andfieldwhere) -> orderBy('add_date desc') -> offset($offset) -> limit($limit) -> all();
        $this->returnJson(0,'success',$data);
    }

    //开启关闭数据流
    public function actionChangestatus()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app->request->post();
            $data['edit_date'] = date('Y-m-d H:i:s',time());
            $member = new Gateway();
            $order = $data['order'];
            unset($data['order']);
            $result = $member -> updateGateway($data);
            $this -> changeGatewayTiming($data['id'], $data['status'], $order);
            if ($result){
                $msg = '开启成功';
                if ($data['status'] == 0){
                    $msg = '关闭成功';
                }
                $this->returnJson(0,$msg);
            }else{
                $this->returnJson(-2,'修改状态失败');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //更新设备的开关时间
    public function changeGatewayTiming($gid, $status, $order)
    {
        $model = new Gateway();
        $query = new Query();
        $pid = $query -> from($model::tableName()) -> where(['id' => $gid]) -> select(['pid']) -> one();
        $pid = $pid['pid'];
        //查询当前的网关设备是否有没有设置开关时间的数据
        $query = new Query();
        $online_date = null;
        $offline_date = null;
        if ($status == 1){
            $online_date = date('Y-m-d H:i:s',time());
        }elseif($status == 0){
            $offline_date = date('Y-m-d H:i:s',time());
        }
        $result = $query -> from('mj_gateway_status') -> where(['gid' => $gid, 'pid' => $pid]) -> orderBy('id desc') -> one();

        if ($result && $online_date < $result['offline_date'] && $status == 1){
            $insert = [
                'gid' => $gid,
                'pid' => $pid,
                'online_date' => $online_date,
                'online_order' => $order
            ];
            $re = Yii::$app -> db -> createCommand() -> insert('mj_gateway_status', $insert) -> execute();
        }elseif($result && $offline_date > $result['online_date']){
            $update = [
                'offline_date' => $offline_date,
                'offline_order' => $order
            ];
            $re = Yii::$app -> db -> createCommand() -> update('mj_gateway_status', $update, 'id='.$result['id'] ) -> execute();
        }elseif($result && $offline_date > $result['online_date'] && empty($result['offset_date'])){
            $update = [
                'offline_date' => $offline_date,
                'offline_order' => $order
            ];
            $re = Yii::$app -> db -> createCommand() -> update('mj_gateway_status', $update, 'id='.$result['id'] ) -> execute();
        }elseif($status == 1){
            $insert = [
                'gid' => $gid,
                'pid' => $pid,
                'online_date' => $online_date,
                'online_order' => $order
            ];
            $re = Yii::$app -> db -> createCommand() -> insert('mj_gateway_status', $insert) -> execute();
        }elseif($status == 0){
            $insert = [
                'gid' => $gid,
                'pid' => $pid,
                'offline_date' => $offline_date,
                'offline_order' => $order
            ];
            $re = Yii::$app -> db -> createCommand() -> insert('mj_gateway_status', $insert) -> execute();
        }
    }

    //获取网关的状态历史
    public function actionGatewaystatus($offset, $limit, $gid)
    {
        $andfieldwhere = $this -> getMemberRule();
        $query = new Query();
        $data = $query
            -> from('mj_gateway_status')
            -> offset($offset)
            -> limit($limit)
            -> where(['gid' => $gid])
            -> andFilterWhere($andfieldwhere)
            -> orderBy(['online_date desc','offline desc'])
            -> select(['id', 'offline_date', 'online_date', 'offline_order', 'online_order'])
            -> orderBy('id desc')
            -> all();
        $this->returnJson(0, '查询成功',$data);
    }

    //提交视图文本
    public function actionSendnote()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $note = $data['note'];
            $nid = $data['nid'];
            if (trim($note)){
                $this->returnJson(-2,'请输入你要提交的内容');
            }
            $model = new Viewjoincontrol();
            $result = Yii::$app -> db -> createCommand() -> update($model::tableName(),['note' => $note],'id='.$nid) -> execute();
            if ($result){
                $this->returnJson(0, '提交成功');
            }else{
                $this->returnJson(-2, '服务器忙请稍后再试');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //根据视频监控ID获取监控链接
    public function actionGetvideourl($id)
    {
        $model = new Viewmanagejoinvideo();
        $query = new Query();
        $data = $query -> from($model::tableName()) -> where(['id' => $id]) -> one();
        $this->returnJson(0,'success',$data);
    }


    //根据地图ID获取监控链接
    public function actionGetmap($id)
    {
        $model = new Viewmanagejoinmap();
        $query = new Query();
        $data = $query -> from($model::tableName()) -> where(['id' => $id]) -> one();
        $data['map_position'] = json_decode($data['map_position'],true);
        $this->returnJson(0,'success',$data);
    }


    //获取小程序的openid
    public function actionLogin()
    {
        if (Yii::$app -> request -> isPost){
            $code = Yii::$app -> request -> post('code');
            $mid = Yii::$app -> request -> post('mid');
            $token = $this -> getToken();
            $query = new Query();
            $re = $query -> from('mj_setting') -> select(['appid', 'appsecret']) -> one();
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$re['appid']."&secret=".$re['appsecret']."&js_code=".$code."&grant_type=authorization_code";
//            print_r($url);
            $data = $this->curl_data($url);
            $openid = $data -> openid;
//            print_r($openid);
            $query = new Query();
            $result = $query -> from('mj_member_openid') -> where(['openid' => $openid]) -> one();
//            print_r($result);
            if (!$result){
                $data = ['mid' => $mid, 'openid' => $openid];
                $re = Yii::$app -> db -> createCommand() -> insert('mj_member_openid',$data) -> execute();
            }
        }
    }
}