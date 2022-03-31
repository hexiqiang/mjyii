<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/10
 * Time: 16:26
 */
set_time_limit(0);
//设置socket连接超时时间
ini_set('default_socket_timeout', -1);
//声明测试频道名称
$channelName = "sbc";
try {
    $redis = new Redis();
    //建立一个长链接
    $redis->pconnect('127.0.0.1', 6379);
    //阻塞获取消息
    $redis->subscribe(array($channelName), function ($redis, $chan, $msg){
        echo "channel:".$chan.",message:".$msg."\n";
    });
} catch (Exception $e){
    echo $e->getMessage();
}