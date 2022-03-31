<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/23
 * Time: 10:22
 */

namespace app\controllers\api;

use app\models\Stream;
use Yii;

class StreamController extends CommonController
{
    // 获取列表
    public function actionGetstream()
    {
        $project = new Stream();
        $gid = Yii::$app->request -> get('gid');
        $data = $project -> getStream($gid);
        $this->returnJson(0, '查询成功',$data);
    }

    //添加
    public function actionAddstream()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $data['edit_date'] = date('Y-m-d H:i:s',time());
            $project = new Stream();
            if (!empty($data['stream_name']) && !empty($data['cloud_var'])){
                $result = $project -> addStream($data);
                if ($result){
                    $this->returnJson(0, '添加成功');
                }else{
                    $this->returnJson(-3, '服务器忙！添加失败');
                }
            }else{
                $this->returnJson(-2, '请认真填写星号的栏目');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //编辑
    public function actionEditstream()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (!empty($data['gateway_name']) && !empty($data['gateway_id'])){
                $project = new Stream();
                $data['edit_date'] = date('Y-m-d H:i:s',time());
                $result = $project -> updateStream($data);
                if ($result){
                    $this->returnJson(0, '编辑成功');
                }else{
                    $this->returnJson(-3, '服务器忙！编辑失败');
                }
            }else{
                $this->returnJson(-2, '请认真填写星号的栏目');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //删除
    public function actionDelstream()
    {
        if (Yii::$app -> request -> isPost){
            $id = Yii::$app -> request -> post('id');
            $member = new Stream();
            $result = $member -> delStream($id);
            if ($result){
                $this->returnJson(0,'删除成功');
            }else{
                $this->returnJson(-2,'删除失败');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //修改状态
    public function actionChangestatus()
    {
        if (Yii::$app -> request -> isPost){

        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

}