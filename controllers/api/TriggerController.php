<?php
/**
 * 触发器控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/23
 * Time: 10:22
 */

namespace app\controllers\api;

use app\models\Call;
use app\models\Gateway;
use app\models\Trigger;
use Yii;
use yii\db\Query;

class TriggerController extends CommonController
{
    // 获取列表
    public function actionGettrigger()
    {
        $project = new Trigger();
        $cid = Yii::$app -> request -> get('cid');
        $data = $project -> getTrigger($cid);
        $this->returnJson(0, '查询成功',$data);
    }

    //添加
    public function actionAddtrigger()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (!empty($data['gid']) && !empty($data['sid']) && !empty($data['equation'])){
                $data['add_date'] = date('Y-m-d H:i:s',time());
                $data['edit_date'] = date('Y-m-d H:i:s',time());
                $query = new Query();
                $gateway_name = $query -> from('mj_gateway') -> select('gateway_name') -> where(['id' => $data['gid']]) -> one();
                $data['gateway_name'] = $gateway_name['gateway_name'];
                $stream_name = $query -> from('mj_stream') -> select('stream_name') -> where(['id' => $data['sid']]) -> one();
                $data['stream_name'] = $stream_name['stream_name'];
                $call_name = $query -> from('mj_call') -> select('call_name') -> where(['id' => $data['cid']]) -> one();
                $data['call_name'] = $call_name['call_name'];
                $project = new Trigger();
                $result = $project -> addTrigger($data);
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
    public function actionEdittrigger()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (!empty($data['gid']) && !empty($data['sid']) && !empty($data['equation'])){
                $data['edit_date'] = date('Y-m-d H:i:s',time());
                $query = new Query();
                $gateway_name = $query -> from('mj_gateway') -> select('gateway_name') -> where(['id' => $data['gid']]) -> one();
                $data['gateway_name'] = $gateway_name['gateway_name'];
                $stream_name = $query -> from('mj_stream') -> select('stream_name') -> where(['id' => $data['sid']]) -> one();
                $data['stream_name'] = $stream_name['stream_name'];
                $call_name = $query -> from('mj_call') -> select('call_name') -> where(['id' => $data['cid']]) -> one();
                $data['call_name'] = $call_name['call_name'];
                $trigger = new Trigger();
                $result = $trigger -> updateTrigger($data);
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

    //删除工程
    public function actionDeltrigger()
    {
        if (Yii::$app -> request -> isPost){
            $id = Yii::$app -> request -> post('id');
            $member = new Trigger();
            $result = $member -> delTrigger($id);
            if ($result){
                $this->returnJson(0,'删除成功');
            }else{
                $this->returnJson(-2,'删除失败');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //修改工程状态
    public function actionChangestatus()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app->request->post();
            $data['edit_date'] = date('Y-m-d H:i:s',time());
            $member = new Trigger();
            $result = $member -> updateTrigger($data);
            if ($result){
                $this->returnJson(0,'成功修改状态');
            }else{
                $this->returnJson(-2,'修改状态失败');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    // 查询当前的项目网关
    public function actionGetgateway()
    {
        $pid = Yii::$app -> request -> get('pid');
        $query = new Query();
        $data = $query -> from('mj_gateway') -> where(['pid' => $pid]) -> select(['id', 'gateway_name']) -> all();
        $this->returnJson(0,'查询成功',$data);
    }
    // 查询当前的项目网关下的数据流
    public function actionGetstream()
    {
        $gid = Yii::$app -> request -> get('gid');
        $query = new Query();
        $data = $query -> from('mj_stream') -> where(['gid' => $gid]) -> select(['id', 'stream_name']) -> all();
        $this->returnJson(0,'查询成功',$data);
    }
}