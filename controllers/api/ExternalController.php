<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/4
 * Time: 16:03
 */

namespace app\controllers\api;

use Yii;
use yii\db\Query;
use yii\web\Controller;
use app\mj\RedisQueue;
use app\mj\phpMQTT;
class ExternalController extends Controller
{
    private $redis;
    public function init()
    {
        $this -> redis = Yii::$app -> redis;
    }

    // 获取外部进入数据
    public function actionGetstream()
    {
        $data = Yii::$app -> request -> get();
        unset($data['r']);
        if ($data){
            $data = json_decode($data, true);
        }
    }


    public function createReboot($num)
    {
        $arr = [];
        for($i=0; $i<=$num; $i++){
            $str = 'reboot_' . $i;
            array_push($arr, $str);
        }
        return $arr;
    }

    public function createJson($reboot, $num){
        $arr = [];
        for($i=0; $i<=$num; $i++){
            $json = ['a_'.$i => '这是我的命令','b_'.$i => '这是第二个命令'];
            $str = $reboot;
            $data = [$str => $json, 'get_time' => date('Y-m-d H:i:s',time())];
            array_push($arr, $data);
        }
        return $arr;
    }


    // 发布消息
    public function actionPublish() {
        ignore_user_abort(true);
        $num=0;
        set_time_limit(0);
        //ini_set('max_execution_time',0); 用这句也行，效果和set_time_limit(0)一样
        $queue = $this -> createReboot(1);
        $data = $this -> createJson($queue[rand(0,1)],1);
        $data = json_encode($data);
        $this -> setMQList($data);
        $queryRedis = new RedisQueue();
        $result = $queryRedis->publish($queue[rand(0,1)], $data);
        do{
            file_put_contents(Yii::$app->basePath . '/web/log/reboot.txt',$num);
            $num++;
            sleep(5);
        }while(true);
        //模拟机器命令

//        return $result;
    }

    // 订阅消息
    public function actionSubscribe($queue) {
        $queryRedis = new RedisQueue();
        ini_set('default_socket_timeout', -1);
        $data = $queryRedis -> subscribe($queue);
    }



    // 使用redis的列表保存数据
    public function setMQList($data)
    {
        if ($data){
            $time = time();
            $this -> redis -> lpush('mj',$data);
        }
    }

    //每过5秒执行里面的数据
    public function actionSettenget()
    {
        //创建链接
//        Yii::$app -> db -> createCommand() -> insert('mj_test',['test_value' => $data,])
//        $result = $this -> redis -> lpop('mj');
//        ignore_user_abort(true);
//        $num=0;
//        set_time_limit(0);
//        //ini_set('max_execution_time',0); 用这句也行，效果和set_time_limit(0)一样
//        do{
//
//            file_put_contents(Yii::$app->basePath . '/web/log/test.txt',$num);
//            $num++;
//            sleep(3);
//        }while(true);
        $length = $this -> redis -> llen('mj');
        $data = $this -> redis -> LRANGE('mj',0, $length);
        foreach ($data as $k => $v){
            $arr = json_decode($v,true);
            foreach ($arr as $ke => $va){
                echo"<pre>";
                var_dump($va);
            }
//            print_r($arr);
        }
    }

    //发布mqtt的数据流
    public function actionPostmq()
    {
        $server = '119.23.106.90';     // change if necessary
        $port = 1883;                     // 通信端口
        $username = "";                   // 用户名(如果需要)
        $password = "";                   // 密码(如果需要
        $client_id = "site"; // 设置你的连接客户端id
        $mqtt = new phpMqtt($server, $port, $client_id); //实例化MQTT类
        if ($mqtt->connect(true, NULL, $username, $password)) {
            //如果创建链接成功
            $mqtt->publish("/topic", "{1:31231,d:321312312}", 0);
            // 发送到 xxx3809293670ctr 的主题 一个信息 内容为 setr=3xxxxxxxxx Qos 为 0
            $mqtt->close();    //发送后关闭链接
        } else {
            echo "Time out!\n";
        }

    }
    public function actionPutmq(){
        ignore_user_abort(true); // 后台运行
        set_time_limit(0); // 取消脚本运行时间的超时上限
        date_default_timezone_set('PRC'); //设置中国时区

        $server = '119.23.106.90';     // change if necessary
        $port = 1883;                     // change if necessary
        $username = '';                   // set your username
        $password = '';                   // set your password
        $client_id = 'mj'; // make sure this is unique for connecting to sever - you could use uniqid()

        $mqtt = new phpMQTT($server, $port, $client_id);
        if(!$mqtt->connect(true, NULL, $username, $password)) {
            exit(1);
        }

        $mqtt->debug = true;

        $topics['mj'] = array('qos' => 0, 'function' => 'procMsg');
        $mqtt->subscribe($topics, 0);

        while($mqtt->proc()) {

        }

        $mqtt->close();

        function procMsg($topic, $msg){
//            echo 'Msg Recieved: ' . date('r') . "\n";
//            echo "Topic: {$topic}\n\n";
//            echo "\t$msg\n\n";
            $this -> redis -> lpush('mj',$msg);
        }

    }
    //订阅mqtt的数据流
    public function actionGetmq()
    {
//        date_default_timezone_set('PRC'); //设置中国时区
        ignore_user_abort(true);
        $num=0;
        set_time_limit(0);

        do{
            $start = false;
            $server = '119.23.106.90';
            $port = 1883;                     // change if necessary
            $username = '';                   // set your username
            $password = '';                   // set your password
            $client_id = 'phpMQTT'; // make sure this is unique for connecting to sever - you could use uniqid()
            $mqtt = new phpMQTT($server, $port, $client_id);
            if(!$mqtt->connect(true, NULL, $username, $password)) {
                exit(1);
            }
            $data = $mqtt->subscribeAndWaitForMessage('/topic', 0);
            if ($data){
                $start = true;
            }
            $this -> redis -> lpush('mj',$data);

            $mqtt->close();
        }while($start);

        do{



            file_put_contents(Yii::$app->basePath . '/web/log/mq.txt',$num);
            $num++;
            sleep(10);
        }while(true);

    }

    // 获取数据后保存到对应的数据表
    public function actionSaveredis()
    {
        ignore_user_abort(true);
        $num=0;
        set_time_limit(0);
        do{
            $status = false;
            $redis = $this -> redis;
            $length = $redis -> llen('mj');
            if($length > 0) {
                $status = true;
                $dataJson = $redis->LRANGE('mj', 0, $length);
//            print_r($dataJson);
                $field = ['test_value', 'add_time', 'reboot', 'get_time'];
                $data = [];
                foreach ($dataJson as $k => $v) {
                    $redis->LPOP('mj');
                    $v = json_decode($v, true);
                    array_push($data, [$v['data']['value'], date('Y-m-d H:i:s', time()), $v['name'], $v['data']['date']]);
                }
                $result = Yii::$app->db->createCommand()->batchInsert('mj_test', $field, $data)->execute();
                Yii::$app->db->close();
            }
            file_put_contents(Yii::$app->basePath . '/web/log/mq.txt', $num);
            $num++;
            sleep(10);
        }while($status);
    }

}