<?php
/**
 * Created by PhpStorm.
 * User: 86159
 * Date: 2022/3/12
 * Time: 8:31
 */

namespace app\controllers\mq;


use yii\base\Controller;
use app\mj\Mqtt;
class SubscribewritemsgController extends Controller
{
    public function actionIndex()
    {

        $server = '115.159.36.77';     // change if necessary
        $port = 1883;                     // change if necessary
        $username = '';                   // set your username
        $password = '';                   // set your password
        $client_id = 'mj2'; // make sure this is unique for connecting to sever - you could use uniqid()

        $mqtt = new Mqtt($server, $port, $client_id);
        if(!$mqtt->connect(true, NULL, $username, $password)) {
            exit(1);
        }

        echo $mqtt->subscribeAndWaitForMessage('mj', 0);

        $mqtt->close();
    }
}