<?php
/**
 * 联控触发条件模型
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/28
 * Time: 10:47
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Joinsproject extends ActiveRecord
{
    public static function tableName()
    {
        return 'mj_join_project'; // TODO: Change the autogenerated stub
    }

    //批量插入数据
    public function add($data)
    {
        $res= Yii::$app -> db -> createCommand()->insert(self::tableName(), $data)->execute();//执行批量添加
    }

    //批量插入数据
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



}