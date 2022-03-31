<?php
/**
 * 报警模型
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/23
 * Time: 10:25
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

class Joinrecord extends ActiveRecord
{

    public static function tableName()
    {
        return 'mj_join_record';
    }

    // 查询列表
    public function Lists($offset, $limit, $pid, $jid, $status, $start=null, $end=null)
    {
        $where = [];
        $andFilterWhere = [];
        if ($pid && $jid && $status){
            $where=['pid' => $pid,'jid' => $jid, 'join_status' => $status];
        }
        if ($start && $end){
            $andFilterWhere = ['between','join_date',$start .' ' . '00:00:00', $end .' ' . '23:59:59'];
        }
        $query = new Query();
        $data['totalCount'] = $query -> from(self::tableName())-> where($where)-> andFilterWhere($andFilterWhere) -> count('id');
        $data['page'] = ceil($data['totalCount'] / 10);
        $data['data'] = $query -> from(self::tableName())
            -> offset($offset)
            -> limit($limit)
            -> where($where)
            -> andFilterWhere($andFilterWhere)
            -> select(['project_name', 'gateway_name', 'join_name', 'join_status', 'join_date'])
            -> orderBy('join_date desc')
            -> all();
        return $data;

    }

}