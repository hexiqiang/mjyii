<?php
/**
 * 视图管理关联
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/28
 * Time: 17:01
 */
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

class Viewmanagejoin extends ActiveRecord
{
    public static function tableName()
    {
        return 'mj_view_manage_join'; // TODO: Change the autogenerated stub
    }

    public function add($data)
    {
        $result = Yii::$app -> db -> createCommand() -> insert(self::tableName(),$data) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }
}