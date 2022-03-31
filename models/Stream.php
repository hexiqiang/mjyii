<?php
/**
 * 工程模型
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/23
 * Time: 10:25
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

class Stream extends ActiveRecord
{

    public static function tableName()
    {
        return 'mj_stream';
    }

    // 查询列表
    public function getStream($gid)
    {
        $query = new Query();
        $data = $query -> from(self::tableName())
            -> where(['gid' => $gid])
            -> orderBy('id desc')
            -> all();
        foreach ($data as $k => $v){
            unset($data[$k]['add_date']);
        }
        return $data;
    }

    // 添加数据
    public function addStream($data)
    {
        $result = Yii::$app -> db -> createCommand() -> insert(self::tableName(), $data) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

    //保存编辑数据
    public function updateStream($data)
    {
        $result = Yii::$app -> db -> createCommand() -> update(self::tableName(), $data, 'id='.$data['id']) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

    //删除数据流
    public function delStream($id)
    {
        $result = Yii::$app -> db -> createCommand() -> delete(self::tableName(), "id=".$id) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }
}