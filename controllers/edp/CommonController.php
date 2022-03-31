<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/14
 * Time: 15:02
 */
namespace app\controllers\edp;

use yii\base\Controller;
use Yii;
class CommonController extends Controller
{
    public $redis;
    // 初始化mq的相关信息
    public function init()
    {
        $this -> redis = Yii::$app -> redis;
    }
}