<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/29
 * Time: 9:16
 */
namespace app\mj;

use yii\BaseYii;

class Push extends BaseYii implements \yiiplus\websocket\ChannelInterface
{
    public function execute($fd, $data)
    {
        return [
            $fd, // 第一个参数返回客户端ID，多个以数组形式返回
            $data // 第二个参数返回需要返回给客户端的消息
        ];
    }

    public function close($fd)
    {
        return;
    }
}