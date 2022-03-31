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

class Gateway extends ActiveRecord
{
    public $gateway_name;
    public $gateway_id;

    public static function tableName()
    {
        return 'mj_gateway';
    }
    public function rules()
    {
        return [
            // username and password are both required
            [['gateway_name', 'gateway_id'],'on' => ['create'], 'required', 'requiredValue'=>'必填值不能为空','message'=>'请填写对应的项'],
        ];
    }

    // 查询网关列表
    public function getGateway($pid)
    {
        $query = new Query();
        $data = $query -> from(self::tableName())
            -> where(['pid' => $pid])
            -> orderBy('id desc')
            -> all();
        foreach ($data as $k => $v){
            $data[$k]['status'] = $v['status'] == 1 ? true :false;
            unset($data[$k]['add_date']);
        }
        return $data;
    }

    // 添加工程数据
    public function addGateway($data)
    {
        $result = Yii::$app -> db -> createCommand() -> insert(self::tableName(), $data) -> execute();
        if ($result){
            $pk = Yii::$app -> db -> getLastInsertID();
            return $pk;
        }else{
            return false;
        }
    }

    //保存编辑工程数据
    public function updateGateway($data)
    {
        $result = Yii::$app -> db -> createCommand() -> update(self::tableName(), $data, 'id='.$data['id']) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

    //删除账号
    public function delGateway($id)
    {
        $result = Yii::$app -> db -> createCommand() -> delete(self::tableName(), "id=".$id) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }



    //批量插入数据
    public function insertAllGateway($key, $data)
    {
        $res= Yii::$app -> db -> createCommand()->batchInsert(self::tableName(), $key, $data)->execute();//执行批量添加
    }
}