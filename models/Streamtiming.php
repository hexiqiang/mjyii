<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/23
 * Time: 17:33
 */

namespace app\models;


use yii\db\ActiveRecord;
use Yii;
class Streamtiming extends ActiveRecord
{

    public static function tableName()
    {
        return 'mj_stream_timing'; // TODO: Change the autogenerated stub
    }


    public function add($data)
    {
        $result = Yii::$app -> db -> createCommand() -> insert(self::tableName(), $data) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }
}