<?php
/**
 * Created by PhpStorm.
 * User: 86159
 * Date: 2022/3/12
 * Time: 8:03
 */

namespace app\controllers\mq;
use app\mj\phpMQTT  as Mqtt;
use Yii;
use app\controllers\edp\Called;

class SubscribeController extends CommonController
{

    //接收数据
    public function actionIndex()
    {

        ignore_user_abort(true);
        set_time_limit(0);
        $num = 0;
        $client_id = 'mj2'; // 传入唯一的设备id
        $mqtt = $this -> mqtt($client_id);
        if(!$mqtt->connect(true, NULL, $this -> username, $this -> password)) {
            exit(1);
        }
        $mqtt->debug = true;
        $topics['mj/#'] = array('qos' => 0, 'function' => function($topic, $msg){
            $gateway = explode('/', $topic);
            $gateway = $gateway[1];
            $this -> saveRedis($msg,time(),$gateway);
            file_put_contents(Yii::$app->basePath . '/web/log/ab.txt',json_encode($gateway).PHP_EOL,FILE_APPEND);
        });
        $mqtt->subscribe($topics, 0);
        while($mqtt->proc()) {
            file_put_contents(Yii::$app->basePath . '/web/log/test.txt',$num);
            $num++;
        }
        $mqtt->close();
    }


    public function actionReadredis()
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $num = 0;
        do{
            $keys = $this -> readRedis();
            if ($keys){
                $data = $this -> getKeysValue($keys);
                $run = $this -> getValues($data);
            }
            file_put_contents(Yii::$app->basePath . '/web/log/mq.txt',$num);
            $num++;
            sleep(5);
        }while(true);
    }



}