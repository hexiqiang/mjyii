<?php
/**
 * 联控记录控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/25
 * Time: 10:43
 */

namespace app\controllers\api;
use app\models\Joinrecord;
use Yii;
use yii\db\Query;

class JoinrecordController extends CommonController
{
    public function actionGetjoinrecord()
    {
        $params = Yii::$app -> request -> get();
        $pid = isset($params['pid']) ? $params['pid'] : '';
        $jid = isset($params['jid']) ? $params['jid'] : '';
        $status = $params['joinstatus'];
        $start = isset($params['start']) ? $params['start'] : '';
        $end = isset($params['end']) ? $params['end'] : '';
        $offset = $params['offset'];
        $limit = $params['limit'];
        $model = new Joinrecord();
        $data = $model -> Lists($offset, $limit, $pid, $jid, $status, $start, $end);
        if ($data){
            $this->returnJson(0,'查询成功',$data);
        }else{
            $this -> returnJson(-1,'暂无数据');
        }
    }
}