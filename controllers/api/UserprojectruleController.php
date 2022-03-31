<?php
/**
 * 用户绑定项目控制的操作开关
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/2
 * Time: 15:35
 */

namespace app\controllers\api;

use app\models\Userprojectrule;
use Yii;
use yii\db\Query;

class UserprojectruleController extends CommonController
{
    public function actionGetlist()
    {
        $uid = Yii::$app -> request -> get('mid');
        $query = new Query();
        $model = new Userprojectrule();
        $pro = $query -> from($model::tableName()) -> where(['mid' => $uid])->one();
        if ($pro){
            $pid = json_decode($pro['pid'],true);
            $data = $query -> from('mj_project') -> where(['in','id',$pid]) -> select(['id','project_name']) -> all();
            if ($data){
                $this->returnJson(0, '查询成功',$data);
            }else{
                $this->returnJson(-2, '没有数据');
            }
        }else{
            $this->returnJson(-1,'暂无数据');
        }

    }
    public function actionAdd()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if ($data['pid']){
                $data['pid'] = json_encode($data['pid']);
//                print_r($data);return;
                $query = new Query();
                $model = new Userprojectrule();
                $result = $query -> from($model::tableName()) -> where(['mid' => $data['mid']]) -> one();
                if ($result){
                    $data['id'] = $result['id'];
                    $result = $model -> edit($data);
                    if ($result){
                        $this->returnJson(0, '设置成功');
                    }else{
                        $this->returnJson(-2, '设置失败');
                    }
                }else{
                    $result = $model -> add($data);
                    if ($result){
                        $this->returnJson(0, '设置成功');
                    }else{
                        $this->returnJson(-2, '设置失败');
                    }
                }
            }
        }else{
            $this -> returnJson(-1,'请提交合法数据');
        }
    }
}