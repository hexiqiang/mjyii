<?php
/**
 * 报警控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/23
 * Time: 10:22
 */

namespace app\controllers\api;

use app\models\Call;
use Yii;
use yii\db\Query;

class CallController extends CommonController
{
    // 获取列表
    public function actionGetcall()
    {
        $andFieldwhere = $this -> getMemberRule();
        $offset = Yii::$app->request->get('offset');
        $limit = Yii::$app->request->get('limit');
        $keyword = Yii::$app->request->get('keyword');
        $where = [];
        if ($keyword){
            $where = ['like','call_name',$keyword];
        }
        $project = new Call();
        $data = $project -> getCall($offset,$limit,$where,$andFieldwhere);
        $this->returnJson(0, '查询成功',$data);
    }

    //添加
    public function actionAddcall()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $data['edit_date'] = date('Y-m-d H:i:s',time());
            $data['call_phone'] = json_encode($data['call_phone']);
            $data['call_type'] = json_encode($data['call_type']);
            $query = new Query();
            $project_name = $query -> from('mj_project') -> select('project_name') -> where(['id' => $data['pid']]) -> one();
            $data['project_name'] = $project_name['project_name'];
            $project = new Call();
            if (!empty($data['call_name']) && !empty($data['pid'])){
                $result = $project -> addCall($data);
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
    public function actionEditcall()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (!empty($data['call_name']) && !empty($data['pid'])){
                $data['call_phone'] = json_encode($data['call_phone']);
                $data['call_type'] = json_encode($data['call_type']);
                $data['edit_date'] = date('Y-m-d H:i:s',time());
                $query = new Query();
                $project_name = $query -> from('mj_project') -> select('project_name') -> where(['id' => $data['pid']]) -> one();
                $data['project_name'] = $project_name['project_name'];
                $project = new Call();
                $result = $project -> updateCall($data);
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
    public function actionDelcall()
    {
        if (Yii::$app -> request -> isPost){
            $id = Yii::$app -> request -> post('id');
            $member = new Call();
            $result = $member -> delCall($id);
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
            $member = new Call();
            $result = $member -> updateCall($data);
            if ($result){
                $this->returnJson(0,'成功修改状态');
            }else{
                $this->returnJson(-2,'修改状态失败');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }



}