<?php
/**
 * 消息管理
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/1
 * Time: 16:03
 */

namespace app\controllers\api;

use Yii;
use app\models\Message;
use yii\db\Query;

class MessageController extends CommonController
{

    public function actionLists($offset, $limit,$mid, $keyword=null)
    {
        $where = [];
        if ($keyword){
            $where = ['like', 'title', $keyword];
        }
        $model = new Message();
        $query = new Query();
        $totalCount = $query -> from($model::tableName()) -> where(['mid' => $mid]) -> andWhere($where) -> count();
        $result = $query -> from($model::tableName()) -> offset($offset) -> limit($limit) -> where(['mid' => $mid]) -> andWhere($where) -> orderBy('id desc') -> all();
        if ($result){
            $data['totalCount'] = $totalCount;
            $data['data'] = $result;
            foreach ($data['data'] as $k => $v){
                $data['data'][$k]['status'] = $v['status'] == 1 ? true : false;
            }
            $this->returnJson(0, '查询成功',$data);
        }else{
            $this -> returnJson(-1, '暂无数据');
        }
    }

    public function actionAdd()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if(trim(!empty($data['title'])) && trim(!empty($data['content']))){
                $model =new Message();
                $data['add_date'] = date('Y-m-d H:i:s',time());
                $result = $model -> add($data);
                if ($result){
                    $this->returnJson(0,'发布成功');
                }else{
                    $this->returnJson(-2,'服务器忙请稍后再试');
                }
            }else{
                $this->returnJson(-3,'请认证填写星号项');
            }

        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }

    public function actionEdit()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if(trim(!empty($data['title'])) && trim(!empty($data['content'])) && isset($data['id'])){
                $model =new Message();
                $data['add_date'] = date('Y-m-d H:i:s',time());
                $result = $model -> edit($data);
                if ($result){
                    $this->returnJson(0,'编辑成功');
                }else{
                    $this->returnJson(-2,'服务器忙请稍后再试');
                }
            }else{
                $this->returnJson(-3,'请认证填写星号项');
            }

        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }

    public function actionDel()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if($data['id']){
                $model =new Message();
                $result = $model -> del($data['id']);
                if ($result){
                    $this->returnJson(0,'删除成功');
                }else{
                    $this->returnJson(-2,'服务器忙请稍后再试');
                }
            }else{
                $this->returnJson(-3,'没有数据');
            }

        }else{
            $this->returnJson(-1,'请提交合法数据');
        }
    }
}