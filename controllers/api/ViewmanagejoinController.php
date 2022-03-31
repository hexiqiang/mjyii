<?php
/**
 * 视图管理关联控件
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/28
 * Time: 17:03
 */

namespace app\controllers\api;

use app\models\Viewjoincontrol;
use Yii;
use yii\db\Query;

class ViewmanagejoinController extends CommonController
{
    //查询添加的控件
    public function actionLists()
    {
        $vid = Yii::$app -> request -> get('vid');
        $query = new Query();
        $model = new Viewjoincontrol();
        $data = $query -> from($model::tableName()) -> where(['vid' => $vid]) -> orderBy('id asc') -> select(['cid', 'vid', 'id']) -> all();
        if ($data){
            $this->returnJson(0,'查询成功',$data);
        }else{
            $this->returnJson(-3,'请添加控件');
        }
    }

    //添加的控件
    public function actionAdd()
    {
        if (Yii::$app -> request -> isPost){
            $params = Yii::$app -> request -> post();
            if ($params['cid'] && $params['vid']){
                $query = new Query();
                $model = new Viewjoincontrol();
                $re = $query ->  from($model::tableName()) -> where(['vid' => $params['vid'], 'cid' => $params['cid']]) -> one();
                if (!$re){
                    $result = $model -> add($params);
                    if ($result){
                        $vid = $params['vid'];
                        $data = $query -> from($model::tableName()) -> where(['vid' => $vid]) -> orderBy('id asc') -> select(['cid', 'vid','id']) -> all();
                        if ($data){
                            $this->returnJson(0,'查询成功',$data);
                        }else{
                            $this->returnJson(-3,'没有相关数据');
                        }
                    }else{
                        $this->returnJson(-2,'服务器忙请稍后再试！');
                    }
                }else{
                    $this->returnJson(-5,'该控件已存在！');
                }
            }else{
                $this->returnJson(-4,'请提交合数据');
            }
        }else{
            $this->returnJson(-1,'请提交合数据');
        }
    }

    //删除对应的控件
    public function actionDel()
    {
        if (Yii::$app -> request -> isPost){
            $model = new Viewjoincontrol();
            $id = Yii::$app -> request -> post('id');
            $result = Yii::$app -> db -> createCommand() -> delete($model::tableName(),'id='.$id) -> execute();
            if ($result){
//                $vid = Yii::$app -> request -> post('vid');
//                $query = new Query();
//                $result = $query -> from($model::tableName()) -> where(['vid' => $vid]) -> orderBy('id asc') -> select(['cid', 'vid', 'id']) -> all();
                $this->returnJson(0,'删除成功');
            }else{
                $this->returnJson(-2,'服务器忙请稍后再试！');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }
}