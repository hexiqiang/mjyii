<?php
/**
 * 侧边栏控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/22
 * Time: 9:35
 */

namespace app\controllers\api;

use Yii;
use yii\db\Query;
use yii\filters\Cors;
use yii\filters\VerbFilter;

class NavsController extends CommonController
{
    //获取哦全部导航栏目
    public function actionGetnavs()
    {
        $mid = $this -> mid;
        if ($mid != 19){
            $query1 = new Query();
            $data = $query1 -> from('mj_member_join_nav') -> where(['mid' => $mid]) -> select(['nid']) -> one();
            $nid = json_decode($data['nid']);
        }
        $query = new Query();
        $data = $query -> from('mj_navs')
            -> select(['id', 'name', 'nav_url', 'icon', 'pid'])
            -> where(['pid' => 0])
            -> orderBy('id asc')
            -> all();
        if ($nid){
            foreach ($data as $k => $v) {
                $data[$k]['level'] = [];
                foreach ($nid as $ke => $va) {
                    $nav = $query->from('mj_navs')
                        -> select(['id', 'name', 'nav_url', 'icon', 'pid'])
                        -> where(['pid' => $v['id']])
                        -> andWhere(['id' => $va])
                        -> orderBy('id asc')
                        -> one();
                    if ($nav){
                        array_push($data[$k]['level'] ,$nav);
                    }
                }
            }
        }else{
            foreach ($data as $k => $v){
                $data[$k]['level'] = $query -> from('mj_navs')
                    -> select(['id', 'name', 'nav_url', 'icon', 'pid'])
                    -> where(['pid' => $v['id']])
                    -> orderBy('id asc')
                    -> all();
            }
        }


        $this->returnJson(0, '查询成功', $data);
    }

    //获取可分配栏目
    public function actionNavs()
    {
        $mid = $this -> mid;
        if ($mid != 19){
            $query1 = new Query();
            $data = $query1 -> from('mj_member_join_nav') -> where(['mid' => $mid]) -> select(['nid']) -> one();
            $nid = json_decode($data['nid'],true);
        }
        $query = new Query();

        $data = $query -> from('mj_navs')
            -> select(['id', 'name'])
            -> where(['<>', 'pid', 0])
            -> orderBy('id asc')
            -> all();
        if ($nid){
            $data = $query -> from('mj_navs')
                -> select(['id', 'name'])
                -> where(['in', 'id', $nid])
                -> orderBy('id asc')
                -> all();
        }

        $this->returnJson(0, '查询成功', $data);
    }
}