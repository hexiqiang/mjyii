<?php
/**
 * 报警记录控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/25
 * Time: 10:42
 */

namespace app\controllers\api;

use app\models\Callrecord;
use Yii;
use yii\db\Query;

class CallrecordController extends CommonController
{
    public function actionGetcallrecord()
    {
        $params = Yii::$app -> request -> get();
        $offset = $params['offset'];
        $limit = $params['limit'];
        $pid = isset($params['key']) ? $params['key'] : '';
        $start = isset($params['start']) ? $params['start'] : '';
        $end = isset($params['end']) ? $params['end'] : '';
        $model = new Callrecord();
        $data = $model -> Lists($offset,$limit,$pid,$start,$end);
        if ($data){
            $this->returnJson(0,'查询成功', $data);
        }else{
            $this->returnJson(-1,'暂无数据');
        }
    }
}