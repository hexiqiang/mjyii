<?php
namespace app\mj;
use yii\base\Component;
use yii\base\Exception;
use Yii;
class RedisQueue extends Component
{
    private $redis;

    public function init()
    {
        $this -> redis= Yii::$app -> redis;
    }

    public function publish($queue, $data) {
        if(is_string($queue)) {
//            $this -> redis -> set('mj'.$queue, $data);
            return $this->redis->publish($queue, serialize($data));
        }

        if(!is_array($queue)) {
            throw new Exception('invalid queue');
        }

        try {
            foreach ($queue as $item) {
                $this->redis->publish($queue, serialize($data));
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return true;
    }
//
    public function subscribe($queue) {
        set_time_limit(0);
//设置socket连接超时时间
        ini_set('default_socket_timeout', -1);
//声明测试频道名称
        $channelName = "sbc";
        try {
            $redis = new \Redis();
            //建立一个长链接
            $redis->pconnect('127.0.0.1', 6379);
            //阻塞获取消息
            $redis->subscribe(array($queue), function ($redis, $chan, $msg){
                echo "channel:".$chan.",message:".$msg."\n";
            });
        } catch (Exception $e){
            echo $e->getMessage();
        }
    }

}