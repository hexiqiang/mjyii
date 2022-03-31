<?php
/**
 * 联控发送命令模型
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/28
 * Time: 10:47
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Joinsorder extends ActiveRecord
{
    public static function tableName()
    {
        return 'mj_join_order'; // TODO: Change the autogenerated stub
    }

    public function add($data)
    {
        $result = Yii::$app ->db -> createCommand() -> insert(self::tableName(),$data) -> execute();
    }

    public function adds($key, $data)
    {
        $res= Yii::$app -> db -> createCommand()->batchInsert(self::tableName(), $key, $data)->execute();//执行批量添加
    }

    public function edit($data)
    {
        if ($data['id']){
            $result = Yii::$app ->db -> createCommand() -> update(self::tableName(),$data,'id='.$data['id']);
            if ($result) {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function del()
    {

    }

}