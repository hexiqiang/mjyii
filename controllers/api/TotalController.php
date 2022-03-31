<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/3
 * Time: 16:23
 */

namespace app\controllers\api;


use app\models\Callrecord;
use app\models\Controlrecord;
use app\models\Joinrecord;
use app\models\Message;
use yii\db\Query;
use Yii;

class TotalController extends CommonController
{
    //统计报警记录
    public function actionTotalcall($day = 6)
    {
        $andfieldwhere = $this -> getMemberRule();
        $days = $this -> getDateSeven($day);
        $model = new Callrecord();
        $query = new Query();
        $result = [];
        foreach ($days as $v){
            $count = $query -> from($model::tableName()) -> where(['like','call_date',$v]) ->andFilterWhere($andfieldwhere) -> count();
            $re = ['日期' => $v,'条数' => $count];
            array_push($result,$re);
        }
        $this->returnJson(0,'查询成功',$result);

    }

    //统计报警记录
    public function actionTotalcontrol($day = 6)
    {
        $andfieldwhere = $this -> getMemberRule();
        $days = $this -> getDateSeven($day);
        $model = new Controlrecord();
        $query = new Query();
        $result = [];
        foreach ($days as $v){
            $count = $query -> from($model::tableName()) -> where(['like','control_date',$v]) ->andFilterWhere($andfieldwhere) -> count();
            $re = ['日期' => $v,'条数' => $count];
            array_push($result,$re);
        }
        $this->returnJson(0,'查询成功',$result);
    }

    //统计报警记录
    public function actionTotaljoin($day = 6)
    {
        $andfieldwhere = $this -> getMemberRule();
        $days = $this -> getDateSeven($day);
        $model = new Joinrecord();
        $query = new Query();
        $result = [];
        foreach ($days as $v){
            $count = $query -> from($model::tableName()) -> where(['like','join_date',$v]) -> andFilterWhere($andfieldwhere) -> count();
            $re = ['日期' => $v,'条数' => $count];
            array_push($result,$re);
        }
        $this->returnJson(0,'查询成功',$result);
    }

    //统计反馈记录
    public function actionTotalmessage($day = 6)
    {
        $mid = $this -> mid;
        $days = $this -> getDateSeven($day);
        $model = new Message();
        $query = new Query();
        $result = [];
        $where = [];
        if ($mid != 19){
            $where = ['mid' => $mid];
        }
        foreach ($days as $v){
            $count = $query -> from($model::tableName()) -> where(['like','add_date',$v]) -> andWhere(['!=','mid','19']) -> andFilterWhere($where) -> count();
            $re = ['日期' => $v,'条数' => $count];
            array_push($result,$re);
        }
        $this->returnJson(0,'查询成功',$result);
    }
}