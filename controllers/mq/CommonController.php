<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/14
 * Time: 8:29
 */

namespace app\controllers\mq;
use app\models\Gateway;
use app\models\Projects;
use app\models\Stream;
use yii\base\Controller;
use app\mj\phpMQTT as Mqtt;
use Yii;
use app\controllers\edp\Called;
use yii\db\Query;

class CommonController extends Controller
{
    private $server;
    private $port;
    public $username;
    public $password;
    public $redis;
    public $enableCsrfValidation = false;
    // 初始化mq的相关信息
    public function init()
    {
        $query = new Query();
        $re = $query -> from('mj_setting') -> select(['mqhost', 'mqport']) -> one();
        $this -> redis = Yii::$app -> redis;
        $this -> server = $re['mqhost'];
        $this -> port =  $re['mqport'];
        $this -> username = '';
        $this -> password = '';
    }



    //链接mqtt
    public function mqtt($client_id)
    {
        $mqtt = new Mqtt($this -> server, $this -> port, $client_id);
        return $mqtt;
    }
    
    
    //存储到redis数据值。
    public function saveRedis($mqMsg,$time,$gateway)
    {
        $redis = $this -> redis;
        $redis_key = 'mj_'.$time;
        $mqMsg = json_decode($mqMsg,true);
        $mqMsg['gateway_id'] = $gateway;
        $mqMsg = json_encode($mqMsg);
        file_put_contents(Yii::$app->basePath . '/web/log/keys.txt',$mqMsg.PHP_EOL,FILE_APPEND);
        $redis -> rpush($redis_key, $mqMsg);
    }
    
    
    //读取当前redis的键值
    public function readRedis()
    {
        $redis = $this -> redis;
        $keys = $redis -> keys('mj_*');
//        file_put_contents(Yii::$app->basePath . '/web/log/keys.txt',json_encode($keys).PHP_EOL,FILE_APPEND);
        if (count($keys) > 0){
            return $keys;
        }
    }

    //根据当前键列表获取对应的value
    public function getKeysValue($keys)
    {
        if (!empty($keys) && count($keys) > 0){
            $redis = $this -> redis;
            $datas = [];
            foreach ($keys as $k => $v){
                $len = $redis -> llen($v);
                $time = explode('_',$v); //获取时间戳
                $data = $redis -> lrange($v,0,$len);
                if (empty($data)) continue;
                foreach ($data as $ke => $va){
                    $redis -> rpop($v);
                    array_push($datas,['value' => json_decode($va,true),'get_date' => date('Y-m-d H:i:s',$time[1])]);
                }
            }
//            file_put_contents(Yii::$app->basePath . '/web/log/redis.txt',json_encode($datas).PHP_EOL,FILE_APPEND);
            return $datas; // 返回数据
        }else{
            return true;
        }
    }


    //获取redis的对应键值下的数据值
    public function getValues($data = [])
    {
        if (!empty($data)){
            $field = ['value', 'add_date', 'stream_name', 'get_date','gateway_id','sid', 'pid'];
            $values = [];
            $query = new Query();
            $call = new Called();
            $gateway = new Gateway();
            foreach ($data as $k => $v) {
                $sid = $this -> getSid($v['value']['gateway_id'], $v['value']['name']);
                $pid = $query -> from($gateway::tableName()) -> where(['gateway_id' => $v['value']['gateway_id']]) -> select(['pid']) -> one();
                array_push($values, [$v['value']['value'], date('Y-m-d H:i:s',time()), $v['value']['name'], $v['get_date'], $v['value']['gateway_id'],$sid, $pid['pid']]);
                $call -> Compare($v['value']['gateway_id'],$v['value']['name'],$v['value']['value'],$v['get_date']);
            }
//            print_r($data);
            $result = Yii::$app->db->createCommand()->batchInsert('mj_stream_record', $field, $values)->execute();
            Yii::$app->db->close();
        }
        return true;

    }


    //获取对应的数据流id
    public function getSid($gateway_id,$cloud_var)
    {
        $query = new Query();
        $stream = new Stream();
        $gateway = new Gateway();
        $gid = $query -> from($gateway::tableName()) -> where(['gateway_id' => $gateway_id]) -> select('id') -> one();
//        print_r($gid);
        $data = $query -> from($stream::tableName()) -> where(['gid' => $gid['id'], 'cloud_var' => trim($cloud_var)]) -> select('id') -> one();
//        print_r($data);
        $sid = $data['id'];
        return $sid;
    }
    //清空当前redis内的数据值
    public function clearRedis()
    {
        
    }
    
    //批量数据保存到数据库
    public function saveMysql($data)
    {
        
    }
    
    
    //
}