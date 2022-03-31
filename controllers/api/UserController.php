<?php
/**
 * 账号api
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/22
 * Time: 14:05
 */

namespace app\controllers\api;

use app\models\Member;
use app\models\Projects;
use app\models\Userproject;
use Yii;
use yii\db\Query;

class UserController extends CommonController
{

    public function actionCsrf()
    {
        $this->getCsrf();
    }

    // 查询用户列表
    public function actionGetmembers($offter=0, $limit=10, $keyword=null)
    {
        $where = [];
        $mid = $this -> mid;
        if ($keyword){
            $where = ['like','member',$keyword];
        }
        $member = new Member();
        $data = $member -> getMembers($offter, $limit, $mid, $where);
        $this->returnJson(0,'查询成功',$data);
    }

    //添加用户
    public function actionAdduser()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app->request->post();
            $data['show_password'] = $data['password'];
            $data['password'] = md5($data['password']);
            $data['add_time'] = date('Y-m-d H:i:s',time());
            $data['edit_time'] = date('Y-m-d H:i:s',time());
            $member = new Member();
            $field = $member -> getMember($data['member']);
            if ($field){
                $this->returnJson(-3,'账号已存在');
                return;
            }
            $result = $member -> addMember($data);
            if ($result){
                $this->returnJson(0,'成功添加账号');
            }else{
                $this->returnJson(-2,'添加账号失败');
            }
        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }


    //编辑并保存账号
    public function actionEditmember()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app->request->post();
            if ($data['password']){
                $data['show_password'] = $data['password'];
                $data['password'] = md5($data['password']);
            }
            $data['edit_time'] = date('Y-m-d H:i:s',time());
            $member = new Member();
            $field = $member -> getMember($data['member'],$data['id']);
            if ($field){
                $this->returnJson(-3,'账号已存在');
                return;
            }
//            var_dump($data);return;
            $result = $member -> saveEditMember($data);
            if ($result){
                $this->returnJson(0,'成功修改账号');
            }else{
                $this->returnJson(-2,'修改账号失败');
            }
        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }

    //删除账号
    public function actionDelmember()
    {
        if (Yii::$app -> request -> isPost){
            $id = Yii::$app -> request -> post('id');
            $member = new Member();
            $query = new Query();
            $result = $query -> from($member::tableName()) -> where(['id' => $id]) -> one();
            if ($result['member'] == 'admin'){
                $this->returnJson(-3,'不能删除超级管理员');
            }else{
                $result = $member -> delMember($id);
                if ($result){
                    $this->returnJson(0,'成功删除该账号');
                }else{
                    $this->returnJson(-2,'删除账号失败');
                }
            }
        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }

    // 修改用户状态
    public function actionChangestatus()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app->request->post();
            $data['edit_time'] = date('Y-m-d H:i:s',time());
            $member = new Member();
//            var_dump($data);return;
            $result = $member -> saveEditMember($data);
            if ($result){
                $this->returnJson(0,'成功修改账号状态');
            }else{
                $this->returnJson(-2,'修改账号状态失败');
            }
        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }

    public function actionSearchprojectlist()
    {
        $mid = Yii::$app -> request -> get('mid');
        $model = new Userproject();
        $query = new Query();
        $pid = $query -> from($model::tableName()) -> where(['mid'=> $mid]) -> one();
        $upid = $pid['id'];
        $pid = json_decode($pid['pid'],true);
        $query1 = new Query();
        $model = new Projects();
        if ($pid){
            $result = $query1 -> from($model::tableName()) -> where(['in','id',$pid]) -> select(['id','project_name','note']) -> all();
            foreach ($result as $k => $v){
                $result[$k]['upid'] = $upid;
                $result[$k]['pid'] = $pid;
                $result[$k]['mid'] = $mid;
            }
            $this->returnJson(0,'查询成功',$result);
        }else{
            $this->returnJson(-1,'暂无项目分配');
        }

    }
}