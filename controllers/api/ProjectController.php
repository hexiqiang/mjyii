<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/23
 * Time: 10:22
 */

namespace app\controllers\api;

use app\models\Gateway;
use app\models\Member;
use app\models\Projects;
use Yii;
use yii\db\Query;

class ProjectController extends CommonController
{
    // 获取工程列表
    public function actionGetprojects($offset=0, $limit=10, $mid=null,$keyword)
    {
        $where = [];
        if ($keyword){
            $where = ['id' => $keyword];
        }
        $project_id = $this -> project_id;
        $mid = $this -> mid;
        $project = new Projects();
        $data = $project -> getProjects($offset, $limit, $where ,$mid, $project_id);
        $this->returnJson(0, '查询成功',$data);
    }


    //查询工程的对应数据
    public function actionSearchproject()
    {

        $id = Yii::$app -> request -> get('id');
        $query = new Query();
        $project = new Projects();
        $data = $query -> from($project::tableName()) -> where(['id' => $id]) -> all();
        foreach ($data['data'] as $k => $v){
            $data['data'][$k]['status'] = $v['status'] == 1 ? true :false;
            unset($data['data'][$k]['add_date']);
        }
        $this->returnJson(0, '查询成功',$data);
    }

    // 获取所有的工程名称和id
    public function actionGetprojectlist()
    {
        $query = new Query();
        $project = new Projects();
        $result = $this -> project_id;
//        print_r($result);
        if ($this -> is_admin == true){
            $data = $query -> from($project::tableName()) -> select(['id', 'project_name']) -> all();
            $this->returnJson(0, '查询成功',$data);
        }else{
            $data = $query -> from($project::tableName()) -> where(['in','id',$result]) -> select(['id', 'project_name']) -> all();
            $this->returnJson(0, '查询成功',$data);
        }

    }

    //添加工程
    public function actionAddproject()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $data['edit_date'] = date('Y-m-d H:i:s',time());
            $project = new Projects();
            if (!empty($data['project_name']) && !empty($data['master_apikey'])){
                $result = $project -> addProject($data);
                if ($result){
                    $key = $data['master_apikey'];
                    $this -> actionGetgatewaylist($result, $key);
                    $this->returnJson(0, '成功添加工程');
                }else{
                    $this->returnJson(-3, '服务器忙！添加工程失败');
                }
            }else{
                $this->returnJson(-2, '请认真填写星号的栏目');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //编辑工程
    public function actionEditproject()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (!empty($data['project_name']) && !empty($data['master_apikey'])){
                $project = new Projects();
                $data['edit_date'] = date('Y-m-d H:i:s',time());
                $result = $project -> updateProject($data);
                if ($result){
                    $this->returnJson(0, '成功编辑工程');
                }else{
                    $this->returnJson(-3, '服务器忙！编辑工程失败');
                }
            }else{
                $this->returnJson(-2, '请认真填写星号的栏目');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    //删除工程
    public function actionDelproject()
    {
        if (Yii::$app -> request -> isPost){
            $id = Yii::$app -> request -> post('id');
            $member = new Projects();
            $result = $member -> delMember($id);
            if ($result){
                $this->returnJson(0,'成功删除该工程');
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
            $member = new Projects();
//            var_dump($data);return;
            $result = $member -> updateProject($data);
            if ($result){
                $this->returnJson(0,'成功修改状态');
            }else{
                $this->returnJson(-2,'修改状态失败');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }
    //拉取该工程下的全部网关设备
    public function actionGetgatewaylist($pid, $key, $mid=null)
    {
        $one = new OneNetApi($key);
        $data = $one -> devices();
        $lists = [];
        $listkey = ['gateway_name', 'pid', 'gateway_id', 'net_type', 'status', 'note',  'add_date',  'edit_date',  'master_apikey', ];
        if ($data){
            if ($data['devices']){

                foreach ($data['devices'] as $k => $v){
                    $list = [$v['title'], $pid, $v['id'], $v['protocol'], $v['online'], $v['desc'], $v['create_time'], $v['create_time'], $key];
                    array_push($lists,$list);
                }
            }
            $project = new Gateway();
            $project -> insertAllGateway($listkey, $lists);
        }

    }

}