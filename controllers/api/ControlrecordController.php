<?php
/**
 * 控制记录控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/25
 * Time: 10:42
 */

namespace app\controllers\api;
use app\models\Controlrecord;
use Yii;
use yii\db\Query;

class ControlrecordController extends CommonController
{
    public function actionGetcontrolrecord()
    {
        $params = Yii::$app -> request -> get();
        $pid = isset($params['pid']) ? $params['pid'] : '';
        $gid = isset($params['gid']) ? $params['gid'] : '';
        $status = $params['control_status'];
        $start = isset($params['start']) ? $params['start'] : '';
        $end = isset($params['end']) ? $params['end'] : '';
        $offset = $params['offset'];
        $limit = $params['limit'];
        $model = new Controlrecord();
        $data = $model -> Lists($offset,$limit,$pid,$gid,$status,$start,$end);
        if ($data){
            $this->returnJson(0,'查询成功',$data);
        }else{
            $this -> returnJson(-1,'暂无数据');
        }
    }
}