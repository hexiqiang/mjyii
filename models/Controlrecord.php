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

class Controlrecord extends ActiveRecord
{

    public static function tableName()
    {
        return 'mj_control_record';
    }

    // 查询列表
    public function Lists($offset, $limit, $pid=0, $gid=0, $status, $start=null, $end=null)
    {

        $where = [];
        $andFilterWhere = [];
        if ($pid && $gid && $status){
            if ($status != '全部'){
                $where=['pid' => $pid,'gid' => $gid, 'control_status' => $status];
            }else{
                $where=['pid' => $pid,'gid' => $gid];
            }

        }
        if ($start && $end){
            $andFilterWhere = ['between','control_date',$start .' ' . '00:00:00', $end .' ' . '23:59:59'];
        }
        $query = new Query();
        $data['totalCount'] = $query -> from('mj_control_record')-> where($where)-> andFilterWhere($andFilterWhere) -> count('id');
        $data['page'] = ceil($data['totalCount'] / 10);
        $data['data'] = $query -> from('mj_control_record')
            -> offset($offset)
            -> limit($limit)
            -> where($where)
            -> andFilterWhere($andFilterWhere)
            -> select(['project_name', 'gateway_name', 'post_orders', 'control_status', 'control_date', 'control_time', 'note'])
            -> orderBy('control_date desc')
            -> all();
        return  $data;

    }

}