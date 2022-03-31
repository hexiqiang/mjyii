<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/2
 * Time: 15:35
 */

namespace app\controllers\api;

use app\models\Userrule;
use Yii;
use yii\db\Query;

class UserruleController extends CommonController
{
    public function actionGetnavs()
    {
        $uid = Yii::$app -> request -> get('mid');
        $query = new Query();
        $model = new Userrule();
        $nav = $query -> from($model::tableName()) -> where(['mid' => $uid])->one();
        if ($nav){
            $navid = json_decode($nav['nid'],true);
            $data = $query -> from('mj_navs') -> where(['in','id',$navid]) -> select(['id','name']) -> all();
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
            if ($data['nid']){
                $data['nid'] = json_encode($data['nid']);
                $query = new Query();
                $model = new Userrule();
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