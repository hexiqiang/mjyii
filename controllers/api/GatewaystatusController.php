<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/28
 * Time: 9:17
 */

namespace app\controllers\api;

use Yii;
use yii\db\Query;

class GatewaystatusController extends CommonController
{
    //根据网关id获取对应的在线状态列表
    public function actionGatewaystatus()
    {
        $params = Yii::$app -> request -> get();
        $offset = $params['offset'];
        $limit = $params['limit'];
        $gid = $params['gid'];
        $query = new Query();
        $data['totalCount'] = $query -> from('mj_gateway_status')-> where(['gid' => $gid]) -> select('id') -> count();
        $data['data'] = $query
            -> from('mj_gateway_status')
            -> offset($offset)
            -> limit($limit)
            -> where(['gid' => $gid])
            -> select(['id','offline_date','online_date'])
            -> orderBy('id desc')
            -> all();
        $this->returnJson(0, '查询成功',$data);
    }
}