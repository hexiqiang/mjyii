<?php
/**
 * 调取第三方接口控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/21
 * Time: 14:12
 */

namespace app\controllers\api;

use app\mj\phpMQTT;
use app\models\Controlrecord;
use app\models\Gateway;
use app\models\Projects;
use app\models\Stream;
use Yii;
use yii\db\Query;

class ApiController extends CommonController
{
    //查询该设备下的数据流变量
    public function actionDatastreamofdev()
    {
        $gatewayid = Yii::$app -> request -> get('gateway_id');
        $master_apikey =  Yii::$app -> request -> get('master_apikey');
        $one = new OneNetApi($master_apikey);
        $data = $one -> datastream_of_dev($gatewayid);
        if ($data){
            $this->returnJson(0,'返回数据',$data);
        }else{
            $this->returnJson(-1,'succ,暂无数据流变量');
        }

    }
    
    // 获取单个设备的数据
    public function actionGetdevice($device_id)
    {
    }


    //根据时间段查询创建的设备
    public function actionGetdevices($begin, $end, $online=true,$tag=null, $key_words=null, $online=null)
    {
    }


    //根据设备ID获取数据流
    public function actionGetdatastreamofdev($device_id)
    {
    }


    //发送命令
    public function actionSend()
    {
        if (Yii::$app->request -> isPost){
            $data = Yii::$app -> request -> post();
            $gid = $data['gid'];
            $sid = $data['sid'];
            $query = new Query();
            $model = new  Gateway();
            $gateway = $query -> from($model::tableName()) -> where(['id' => $gid]) -> select(['gateway_id','master_apikey', 'net_type']) -> one();
            switch ($gateway['net_type']){
                case '中移动EDP':
                    $this -> sendEDP($data,$gateway,$gid,$sid);
                    break;
                case '网关MQTT':
                    $this -> sendMQTT($data,$gateway,$gid,$sid);
                    break;
            }
        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }

    //发送MQTT命令到
    public function sendMQTT($data,$gateway,$gid,$sid)
    {
        $client_id = "/".$gateway['gateway_id'] ; // 设置你的连接客户端id
        $query = new Query();
        $re = $query -> from('mj_setting') -> select(['mqhost', 'mqport']) -> one();
        $mqtt = new phpMQTT($re['mqhost'],$re['mqport'],$client_id);
        if ($mqtt->connect(true, NULL, '', '')) {
            //如果创建链接成功
            $mqtt->publish($client_id, $data['msg'], 0);
            // 发送到 xxx3809293670ctr 的主题 一个信息 内容为 setr=3xxxxxxxxx Qos 为 0
            $mqtt->close();    //发送后关闭链接
            $pk = $this -> saveControl($gid, $data['msg'],$sid);
            $this->returnJson(0,'命令发送成功');
        } else {
            echo "Time out!\n";
        }
    }

    //发送中移动的命令
    public function sendEDP($data, $gateway, $gid,$sid)
    {
        $msg = $data['msg'];
        $one = new OneNetApi($gateway['master_apikey']);
        $result = $one -> send_data_to_edp_mqtt_use_device_id($gateway['gateway_id'], $msg,['timeout' => 300]);
        if ($result){
            $pk = $this -> saveControl($gid, $msg,$sid);
            $res = $this -> getGatewayRes($gateway['master_apikey'],$result['cmd_uuid'], $pk, $gid);
            if ($res == 4){
                $this->returnJson(0,'命令发送成功,设备已响应');
            }if ($res == 2){
                $this->returnJson(0,'命令发送成功，设备没响应');
            }if ($res == 1){
                $this->returnJson(0,'命令发送成功，设备离线中');
            }
            file_put_contents(Yii::$app->basePath . '/web/log/order.txt',json_encode($result)  .PHP_EOL,FILE_APPEND);
        }else{
            $this->returnJson(-1,'命令发送失败');
        }
    }

    //查询命令的响应状态
//状态值	              状态描述	                      常见场景
//1	命令已创建         Command Created	             设备离线，调用发送命令API，带有timeout参数时
//2	命令已发往设备     Command Sent	                 设备在线，但是未做命令应答时
//4	设备正常响应       Command Response Received	 设备接收到命令且正常发送命令响应
    public function getGatewayRes($master_apikey, $cmd_uuid, $pk, $gid)
    {
        $one = new OneNetApi($master_apikey);
        $data = $one -> get_dev_status($cmd_uuid);
        $model = new Controlrecord();

        if ($data['status'] == 4){
            $field['control_status'] = '成功';
            Yii::$app -> db -> createCommand() -> update($model::tableName(), $field,'id='.$pk) -> execute();
        }elseif ($data['status'] == 2){
            $field['control_status'] = '成功';
            Yii::$app -> db -> createCommand() -> update($model::tableName(), $field,'id='.$pk) -> execute();
        }elseif ($data['status'] == 1){
            $field['control_status'] = '失败';
            $re = Yii::$app -> db -> createCommand() -> update($model::tableName(), $field,'id='.$pk) -> execute();
            if ($re){
                $model = new Gateway();
                $param['status'] = 0;
                Yii::$app -> db -> createCommand() -> update($model::tableName(), $param,'id='.$gid) -> execute();
            }
        }
        return $data['status'];
        file_put_contents(Yii::$app->basePath . '/web/log/order.txt',json_encode($data) . $gid . $param['status']  .PHP_EOL,FILE_APPEND);
    }
    
    // 保存发送控制命令操作
    public function saveControl($gid,$msg,$sid)
    {
        $model = new Gateway();
        $query = new Query();
        $result = $query -> from($model::tableName()) -> where(['id' => $gid]) -> select(['pid', 'gateway_name']) -> one();
        $model = new Projects();
        $query = new Query();
        $project = $query -> from($model::tableName()) -> where(['id' => $result['pid']]) -> select(['project_name']) -> one();
        $model = new Stream();
        $query = new Query();
        $stream = $query -> from($model::tableName()) -> where(['id' => $sid]) -> select(['stream_name']) -> one();
        $data = [
            'pid' => $result['pid'],
            'gid' => $gid,
            'sid' => $sid,
            'project_name' => $project['project_name'],
            'gateway_name' => $result['gateway_name'],
            'stream_name' => $stream['stream_name'],
            'add_time' => date('Y-m-d H:i:s',time()),
            'control_date' => date('Y-m-d H:i:s',time()),
            'post_orders' => $msg,
        ];
        $model = new Controlrecord();
        $result = Yii::$app -> db -> createCommand() -> insert($model::tableName(),$data) -> execute();
        if ($result){
            $pk = Yii::$app -> db -> getLastInsertID();
            return $pk;
        }
    }


    //发送mqtt命令
}