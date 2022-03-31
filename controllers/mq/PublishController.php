<?php
/**
 * Created by PhpStorm.
 * User: 86159
 * Date: 2022/3/12
 * Time: 8:03
 */

namespace app\controllers\mq;
use yii\db\Query;

class PublishController extends CommonController
{
    public function actionIndex()
    {
        // 发送给订阅号信息,创建socket,无sam队列
        //$client_id = 'mj1'; // make sure this is unique for connecting to sever - you could use uniqid()

        $value = rand(80,150);
        $stream = ['Dai2','Dai3',''];
        $gateway = ['458467894','458496894','123654'];
        $num = rand(0,2);
        $client_id = "/$gateway[$num]/$stream[$num]"; // 设置你的连接客户端id
        $mqtt = $this -> mqtt($client_id); //实例化MQTT类
        if ($mqtt->connect(true, NULL, $this -> username, $this -> password)) {
            //如果创建链接成功
            $mqtt->publish("mj/$gateway[$num]", '{"name" :"' . $stream[$num]  . '","value" : "' . $value . '"}', 0);
            // 发送到 xxx3809293670ctr 的主题 一个信息 内容为 setr=3xxxxxxxxx Qos 为 0
            $mqtt->close();    //发送后关闭链接
        } else {
            echo "Time out!\n";
        }
    }



}