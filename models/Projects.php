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

class Projects extends ActiveRecord
{
    public $project_name;
    public $master_apikey;

    public static function tableName()
    {
        return 'mj_project';
    }
    public function rules()
    {
        return [
            // username and password are both required
            [['project_name', 'master_apikey'],'on' => ['create'], 'required','requiredValue'=>'必填值不能为空','message'=>'请填写对应的项'],
        ];
    }

    // 查询工程列表
    public function getProjects($offset, $limit, $where, $mid, $project_id)
    {
        $query = new Query();
        $andfieldwhere = [];
        if ($mid != 19){
            $andfieldwhere = ['in','id',$project_id];
        }
        $data['totalCount'] = $query -> from(self::tableName()) -> where($andfieldwhere) -> count();
        $data['page'] = ceil($data['totalCount'] / 10 );
        $data['data'] = $query -> from(self::tableName())
            -> offset($offset)
            -> limit($limit)
            -> where($where)
            -> andFilterWhere($andfieldwhere)
            -> orderBy('id desc')
            -> all();
        foreach ($data['data'] as $k => $v){
            $data['data'][$k]['status'] = $v['status'] == 1 ? true :false;
            unset($data['data'][$k]['add_date']);
        }
        return $data;
    }

    // 添加工程数据
    public function addProject($data)
    {
        $result = Yii::$app -> db -> createCommand() -> insert(self::tableName(), $data) -> execute();
        $pk = Yii::$app -> db -> getLastInsertID();
        if ($pk){
            return $pk;
        }else{
            return false;
        }
    }

    //保存编辑工程数据
    public function updateProject($data)
    {
        $result = Yii::$app -> db -> createCommand() -> update(self::tableName(), $data, 'id='.$data['id']) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

    //删除账号
    public function delMember($id)
    {
        $result = Yii::$app -> db -> createCommand() -> delete(self::tableName(), "id=".$id) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }
}