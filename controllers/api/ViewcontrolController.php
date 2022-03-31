<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/1
 * Time: 8:40
 */

namespace app\controllers\api;

use Yii;
use yii\db\Query;
use app\models\Viewcontrol;

class ViewcontrolController extends CommonController
{
    public function actionLists()
    {
        $vc = new Viewcontrol();
        $query = new Query();
        $data = $query -> from($vc::tableName()) -> orderBy('id asc') -> all();
        $this->returnJson(0, '查询成功',$data);
    }
}