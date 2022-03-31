<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/14
 * Time: 15:04
 */

namespace app\controllers\edp;
use app\models\Gateway;
use app\models\Stream;
use Yii;
use app\mj\Util;
use yii\db\Query;
class EdpController extends CommonController
{
    public function actionGetdata()
    {
        $redis = $this -> redis;
        /**
         * 第一步需要获取HTTP body的数据
         * Step1, get the HTTP body's data
         */
//        $call = new Called();
//        $field = Yii::$app -> request -> get();
//        $call -> Compare($field['gateway_id'],$field['cloud_val'],$field['value'],$field['date']);
        $raw_input = file_get_contents('php://input');
        if ($raw_input){
            /**
             * 第二步直接解析body，如果是第一次验证签名则raw_input为空，由resolveBody方法自动判断，依赖$_GET
             * Step2, directly to resolve the body, if it is the first time to verify the signature, the raw_input is empty, by the resolveBody method to automatically determine, it's relied on $ _GET
             */
            $resolved_body = Util::resolveBody($raw_input);
            /**
             * 最后得到的$resolved_body就是推送过后的数据
             * At last, var $resolved_body is the data that is pushed
             */
//             Util::l($resolved_body);
            file_put_contents(Yii::$app->basePath . '/web/log/edp.txt',json_encode($resolved_body).PHP_EOL,FILE_APPEND);
            $key = 'set_'.time();//设置集合的键名
//            {"at":1647399554000,"type":1,"ds_id":"Dai1","value":80,"dev_id":890754551}
            $redis -> sadd($key, json_encode($resolved_body));
            $this -> saveMysql();
            $call = new Called();
            $call -> Compare($resolved_body['dev_id'],$resolved_body['ds_id'],$resolved_body['value'],date('Y-m-d H:i:s',$resolved_body['at']/1000));
        }else{
            $data = Yii::$app -> request -> get();
            return $data['msg'];
        }

    }


    public function getSetData()
    {
        $redis = $this -> redis;
        $data = $redis -> keys('set_*');
        return $data;
    }

    public function saveData()
    {
        $keys = $this -> getSetData();
        if (!empty($keys) && count($keys) > 0){
            $redis = $this -> redis;
            $datas = [];
            foreach ($keys as $k => $v){
                $data = $redis -> smembers($v);
                $time = explode('_',$v); //获取时间戳
                if (empty($data)) continue;
                foreach ($data as $ke => $va){
                    $redis -> spop($v);
                    array_push($datas,json_decode($va,true));
                }
            }
//            file_put_contents(Yii::$app->basePath . '/web/log/redis.txt',json_encode($datas).PHP_EOL,FILE_APPEND);
//            echo "<pre>";
//            print_r($datas);
            return $datas; // 返回数据
        }
    }

    public function saveMysql()
    {
        $data = $this -> saveData();
        if (!empty($data) && count($data) > 0){
            $field = ['type','stream_name','gateway_id','value','get_date','sid','add_date', 'pid'];
            $values = [];
            $gateway = new Gateway();
            $query = new Query();
            foreach ($data as $k => $v){
                $sid = $this -> getSid($v['dev_id'], $v['ds_id']);
                $pid = $query -> from($gateway::tableName()) -> where(['gateway_id' => $v['dev_id']]) -> select(['pid']) -> one();
                array_push($values, [$v['type'], $v['ds_id'], $v['dev_id'], $v['value'], date('Y-m-d H:i:s',$v['at']/1000), $sid, date('Y-m-d H:i:s',time()), $pid['pid']]);
            }
//            file_put_contents(Yii::$app->basePath . '/web/log/redis.txt',json_encode($values).PHP_EOL,FILE_APPEND);
            $result = Yii::$app->db->createCommand()->batchInsert('mj_stream_record', $field, $values)->execute();
            Yii::$app->db->close();
        }
        return 'HTTP 200';
    }

    public function getSid($gateway_id, $cloud_var)
    {

        $query = new Query();
        $stream = new Stream();
        $gateway = new Gateway();
        $gid = $query -> from($gateway::tableName()) -> where(['gateway_id' => $gateway_id]) -> select('id') -> one();
        $data = $query -> from($stream::tableName()) -> where(['gid' => $gid['id'], 'cloud_var' => $cloud_var]) -> select('id') -> one();
        $sid = $data['id'];
        return $sid;
    }
}