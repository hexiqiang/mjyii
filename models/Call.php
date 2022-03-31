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

class Call extends ActiveRecord
{

    public static function tableName()
    {
        return 'mj_call';
    }

    // 查询列表
    public function getCall($offset, $limit, $where=[],$andFieldwhere=[])
    {
        $query = new Query();
        $data['totalCount'] = $query -> from(self::tableName()) -> where($where) -> andFilterWhere($andFieldwhere) -> count();
        $data['page'] = ceil($data['totalCount'] / 10 );
        $data['data'] = $query -> from(self::tableName())
            -> offset($offset)
            -> limit($limit)
            -> where($where)
            -> andFilterWhere($andFieldwhere)
            -> orderBy('id desc')
            -> all();
        foreach ($data['data'] as $k => $v){
            $data['data'][$k]['status'] = $v['status'] == 1 ? true :false;
            $data['data'][$k]['call_phone'] = json_decode($v['call_phone'], true);
            $data['data'][$k]['call_type'] = json_decode($v['call_type'], true);
            unset($data['data'][$k]['add_date']);
        }
        return $data;
    }

    // 添加数据
    public function addCall($data)
    {
        $result = Yii::$app -> db -> createCommand() -> insert(self::tableName(), $data) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

    //保存编辑数据
    public function updateCall($data)
    {
        $data['status'] = $data['status'] == true ? 1: 0;
        $result = Yii::$app -> db -> createCommand() -> update(self::tableName(), $data, 'id='.$data['id']) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

    //删除账号
    public function delCall($id)
    {
        $result = Yii::$app -> db -> createCommand() -> delete(self::tableName(), "id=".$id) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }
}