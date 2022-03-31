<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/23
 * Time: 10:22
 */

namespace app\controllers\api;

use app\models\Gateway;
use Yii;
use yii\db\Query;

class GatewayController extends CommonController
{
    // 获取工程网关列表
    public function actionGetgateway()
    {
        $project = new Gateway();
        $pid = Yii::$app->request -> get('pid');
        $data = $project -> getGateway($pid);
        $this->returnJson(0, '查询成功',$data);
    }

    //添加工程网关
    public function actionAddgateway()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $data['edit_date'] = date('Y-m-d H:i:s',time());
            $project = new Gateway();
            if (!empty($data['gateway_name']) && !empty($data['gateway_id'])){
                $result = $project -> addGateway($data);
                if ($result > 0){
                    $insert = [
                        'gid' => $result,
                        'pid' => $data['pid'],
                        'online_date' => date('Y-m-d H:i:s',time())
                    ];
                    $re = Yii::$app -> db -> createCommand() -> insert('mj_gateway_status', $insert) -> execute();
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

    //编辑工程网关
    public function actionEditgateway()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (!empty($data['gateway_name']) && !empty($data['gateway_id'])){
                $project = new Gateway();
                $data['edit_date'] = date('Y-m-d H:i:s',time());
                $result = $project -> updateGateway($data);
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
    public function actionDelgateway()
    {
        if (Yii::$app -> request -> isPost){
            $id = Yii::$app -> request -> post('id');
            $member = new Gateway();
            $result = $member -> delGateway($id);
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
            $model = new Gateway();
            $result = $model -> updateGateway($data);
            $this -> changeGatewayTiming($data['id'], $data['status']);
            if ($result){
                $this->returnJson(0,'成功修改状态');
            }else{
                $this->returnJson(-2,'修改状态失败');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }


    //更新设备的开关时间
    public function changeGatewayTiming($gid, $status)
    {
        $model = new Gateway();
        $query = new Query();
        $pid = $query -> from($model::tableName()) -> where(['id' => $gid]) -> select(['pid']) -> one();
        $pid = $pid['pid'];
        //查询当前的网关设备是否有没有设置开关时间的数据
        $query = new Query();
        $online_date = null;
        $offline_date = null;
        if ($status == 1){
            $online_date = date('Y-m-d H:i:s',time());
        }elseif($status == 0){
            $offline_date = date('Y-m-d H:i:s',time());
        }
        $result = $query -> from('mj_gateway_status') -> where(['gid' => $gid, 'pid' => $pid]) -> orderBy('id desc') -> one();

        if ($result && $online_date < $result['offline_date'] && $status == 1){
            $insert = [
                'gid' => $gid,
                'pid' => $pid,
                'online_date' => $online_date
            ];
            $re = Yii::$app -> db -> createCommand() -> insert('mj_gateway_status', $insert) -> execute();
        }elseif($result && $offline_date > $result['online_date']){
            $update = [
                'offline_date' => $offline_date
            ];
            $re = Yii::$app -> db -> createCommand() -> update('mj_gateway_status', $update, 'id='.$result['id'] ) -> execute();
        }elseif($result && $offline_date > $result['online_date'] && empty($result['offset_date'])){
            $update = [
                'offline_date' => $offline_date
            ];
            $re = Yii::$app -> db -> createCommand() -> update('mj_gateway_status', $update, 'id='.$result['id'] ) -> execute();
        }elseif($status == 1){
            $insert = [
                'gid' => $gid,
                'pid' => $pid,
                'online_date' => $online_date
            ];
            $re = Yii::$app -> db -> createCommand() -> insert('mj_gateway_status', $insert) -> execute();
        }elseif($status == 0){
            $insert = [
                'gid' => $gid,
                'pid' => $pid,
                'offline_date' => $offline_date
            ];
            $re = Yii::$app -> db -> createCommand() -> insert('mj_gateway_status', $insert) -> execute();
        }
    }
}