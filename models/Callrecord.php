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

class Callrecord extends ActiveRecord
{

    public static function tableName()
    {
        return 'mj_call_record';
    }

    // 查询列表
    public function Lists($offset, $limit, $pid=0, $start=null, $end=null)
    {

        $where = [];
        $andFilterWhere = [];
        if ($pid && $start && $end){
            $where=['pid' => $pid];
            $andFilterWhere = ['between','call_date',$start .' ' . '00:00:00', $end .' ' . '23:59:59'];
        }
        $query = new Query();
        $data['totalCount'] = $query -> from(self::tableName())-> where($where)-> andFilterWhere($andFilterWhere) -> count('id');
        $data['page'] = ceil($data['totalCount'] / 10);
        $data['data'] = $query -> from('mj_call_record')
            -> offset($offset)
            -> limit($limit)
            -> where($where)
            -> andFilterWhere($andFilterWhere)
            -> select(['project_name', 'call_date', 'gateway_name', 'call_message', 'call_status', 'call_type', 'call_note', 'call_name'])
            -> orderBy('call_date desc')
            -> all();
        return $data;

    }

    public function edit($data)
    {
        $result = Yii::$app -> db -> createCommand() -> update(self::tableName(),$data,'id='.$data['id']) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

}