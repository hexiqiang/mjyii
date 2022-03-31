<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/28
 * Time: 15:57
 */

namespace app\controllers\api;

use app\models\Viewmanagejoin;
use app\models\Viewmanagejoinvideo;
use Yii;
use yii\db\Query;

class ViewmanagejoinvideoController extends CommonController
{
    // 查询视图监控列表
    public function actionLists($vid)
    {
        $model = new Viewmanagejoinvideo();
        $result = $model -> lists($vid);
        $this->returnJson(0, 'success', $result);
    }
    //添加视图监控
    public function actionAdd()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $model = new Viewmanagejoin();
            $query = new Query();
            $result = $query -> from($model::tableName()) -> where(['id' => $data['vid']]) -> select('pid') -> one();
            $project = $query -> from('mj_project') -> where(['id' => $result['pid']]) -> select('project_name') -> one();
            $gateway = $query -> from('mj_gateway') -> where(['id' => $data['gid']]) -> select('gateway_name') -> one();
            $stream = $query -> from('mj_stream') -> where(['id' => $data['sid']]) -> select('stream_name') -> one();
            $data['pid'] = $result['pid'];
            $data['project_name'] = $project['project_name'];
            $data['gateway_name'] = $gateway['gateway_name'];
            $data['stream_name'] = $stream['stream_name'];
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $model = new Viewmanagejoinvideo();
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
            $model = new Viewmanagejoin();
            $query = new Query();
            $result = $query -> from($model::tableName()) -> where(['id' => $data['vid']]) -> select('pid') -> one();
            $project = $query -> from('mj_project') -> where(['id' => $result['pid']]) -> select('project_name') -> one();
            $gateway = $query -> from('mj_gateway') -> where(['id' => $data['gid']]) -> select('gateway_name') -> one();
            $stream = $query -> from('mj_stream') -> where(['id' => $data['sid']]) -> select('stream_name') -> one();
            $data['pid'] = $result['pid'];
            $data['project_name'] = $project['project_name'];
            $data['gateway_name'] = $gateway['gateway_name'];
            $data['stream_name'] = $stream['stream_name'];
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $model = new Viewmanagejoinvideo();
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
            $model = new Viewmanagejoinvideo();
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