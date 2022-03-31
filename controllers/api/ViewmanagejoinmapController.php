<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/28
 * Time: 15:57
 */

namespace app\controllers\api;

use app\models\Viewmanagejoin;
use app\models\Viewmanagejoinmap;
use Yii;
use yii\db\Query;

class ViewmanagejoinmapController extends CommonController
{
    // 查询视图监控列表
    public function actionLists($vid)
    {
        $model = new Viewmanagejoinmap();
        $result = $model -> lists($vid);
        $this->returnJson(0, 'success', $result);
    }
    //添加视图监控
    public function actionAdd()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $data['map_position'] = json_encode($data['map_position']);
            $model = new Viewmanagejoinmap();
            $result = $model -> add($data);
            if ($result){
                $data = $model -> lists($data['vid']);
                $this -> returnJson(0,'success', $data);
            }else{
                $this -> returnJson(-1,'数据有误');
            }
        }else{
            $this->returnJson(-1,'请提交合法数据');
        }

    }

    //编辑视图监控
    public function actionEdit()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $model = new Viewmanagejoinmap();
            $data['map_position'] = json_encode($data['map_position']);
            $result = $model -> edit($data);
            if ($result){
                $data = $model -> lists($data['vid']);
                $this -> returnJson(0,'success', $data);
            }else{
                $this -> returnJson(-1,'数据有误');
            }
        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }


    //删除试图监控
    public function actionDel()
    {
        if (Yii::$app -> request -> isPost){
            $id = Yii::$app -> request -> post('id');
            $model = new Viewmanagejoinmap();
            $result = $model -> del($id);
            if ($result){
                $this->returnJson(0,'删除成功');
            }else{
                $this-> returnJson(-2,'服务器忙请稍后再试');
            }

        }else{
            $this -> returnJson(-1,'请提交合法数据');
        }
    }
}