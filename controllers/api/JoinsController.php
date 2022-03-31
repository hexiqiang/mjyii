<?php
/**
 * 联控控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/25
 * Time: 16:49
 */

namespace app\controllers\api;

use app\models\Joins;
use app\models\Joinsorder;
use app\models\Joinsproject;
use Yii;
use yii\db\Query;

class JoinsController extends CommonController
{
    // 根据工程获取对应的联控记录
    public function actionGetprojectjoinslist()
    {
        $pid = Yii::$app->request->get('pid');
        $query = new Query();
        $data = $query->from('mj_join_project')->where(['pid' => $pid])->select(['jid', 'join_name'])->all();
        $this->returnJson(0, '查询成功', $data);
    }

    //查询联控记录
    public function actionLists($offset, $limit, $keyword = null)
    {
        $andfieldwhere = $this -> getMemberRule();
        $query = new Query();
        $data['totalCount'] = $query->from('mj_join') ->count();
        $where = [];
        if ($keyword) {
            $where = ['like', 'join_name', $keyword];
        }
        $data['data'] = $query->from('mj_join')
            ->offset($offset)
            ->limit($limit)
            ->where($where)
            ->orderBy('id desc')
            ->all();
        foreach ($data['data'] as $k => $v) {
            $data['data'][$k]['trigger_condition'] = $query ->from('mj_join_project')
                ->where(['jid' => $v['id']])->orderBy('id asc')
                -> andFilterWhere($andfieldwhere)
                ->select(['project_name', 'gateway_name', 'stream_name', 'threshold', 'equation', 'condition', 'pid','gid','sid','id'])
                ->all();
            $data['data'][$k]['issue_orders'] = $query->from('mj_join_order')
                ->where(['jid' => $v['id']])
                -> andFilterWhere($andfieldwhere)
                ->orderBy('id asc')
                ->select(['project_name', 'gateway_name', 'stream_name', 'trigger_value', 'recovery_value','pid','gid','sid','id'])
                ->all();
            if (!count($data['data'][$k]['trigger_condition'])){
                unset($data['data'][$k]);
            }
        }
        sort($data['data']);
        $this->returnJson(0, '查询成功', $data);
    }

    //添加联控记录
    public function actionAdd()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $insert = [
                'join_name' => $data['join_name'],
                'note' => $data['note'],
                'edit_date' => date('Y-m-d H:i:s', time()),
                'add_date' => date('Y-m-d H:i:s', time()),
            ];
            if ($data['trigger_condition'] && $data['issue_orders'] && !empty($data['join_name'])) {
                $model = new Joins();
                $result = $model->add($insert);
                if ($result > 0) {
                    $this->conditionList($data, $result);//插入触发条件数据
                    $this->orderList($data, $result);//插入命令数据
                    $this->returnJson(0, '添加成功');
                } else {
                    $this->returnJson(-2, '服务器忙请稍后再试！');
                }
            } else {
                $this->returnJson(-4, '请填写完整再提交！');
            }
        } else {
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    public function conditionList($data, $id)
    {
        $query = new Query();
        foreach ($data['trigger_condition'] as $k => $v) {
            $project = $query->from('mj_project')->where(['id' => $v['pid']])->select(['project_name'])->one();
            $gateway = $query->from('mj_gateway')->where(['id' => $v['gid']])->select(['gateway_name'])->one();
            $stream = $query->from('mj_stream')->where(['id' => $v['sid']])->select(['stream_name'])->one();
            $trigger_equ[] = [$id, $data['join_name'], $v['pid'], $v['gid'], $v['sid'], $project['project_name'], $gateway['gateway_name'], $stream['stream_name'], date('Y-m-d H:i:s', time()), $v['threshold'], $v['equation'], $v['condition'],
            ];
        }
        $params = ['jid', 'join_name', 'pid', 'gid', 'sid', 'project_name', 'gateway_name', 'stream_name', 'add_date', 'threshold', 'equation', 'condition'];
        $model = new Joinsproject();
        $model->adds($params, $trigger_equ);
    }

    public function orderList($data, $id)
    {
        $query = new Query();
        foreach ($data['issue_orders'] as $k => $v) {
            $project = $query->from('mj_project')->where(['id' => $v['pid']])->select(['project_name'])->one();
            $gateway = $query->from('mj_gateway')->where(['id' => $v['gid']])->select(['gateway_name'])->one();
            $stream = $query->from('mj_stream')->where(['id' => $v['sid']])->select(['stream_name'])->one();
            $order[] = [$id, $data['join_name'], $v['pid'], $v['gid'], $v['sid'], $project['project_name'], $gateway['gateway_name'], $stream['stream_name'], date('Y-m-d H:i:s', time()), $v['trigger_value'], $v['recovery_value']];
        }
        $params = ['jid', 'join_name', 'pid', 'gid', 'sid', 'project_name', 'gateway_name', 'stream_name', 'add_date', 'trigger_value', 'recovery_value'];
        $model = new Joinsorder();
        $model->adds($params, $order);
    }

    //编辑联控记录
    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $data = Yii::$app->request->post();
            $insert = [
                'join_name' => $data['join_name'],
                'note' => $data['note'],
                'edit_date' => date('Y-m-d H:i:s', time()),
                'id' => $data['id'],
            ];
            if ($data['trigger_condition'] && $data['issue_orders'] && !empty($data['join_name'])) {
                $model = new Joins();
                $result = $model->edit($insert);
                if ($result > 0) {
                    $this->conditionListEdit($data, $data['id']);//插入触发条件数据
                    $this->orderListEdit($data, $data['id']);//插入命令数据
                    $this->returnJson(0, '编辑成功');
                } else {
                    $this->returnJson(-2, '服务器忙请稍后再试！');
                }
            } else {
                $this->returnJson(-4, '请填写完整再提交！');
            }
        } else {
            $this->returnJson(-1, '请提交合法数据');
        }
    }



    public function conditionListEdit($data, $id)
    {
        $query = new Query();
        $model = new Joinsproject();
        foreach ($data['trigger_condition'] as $k => $v) {
            if ($v['id']){
                $model->edit($v);
            }else{
                $project = $query->from('mj_project')->where(['id' => $v['pid']])->select(['project_name'])->one();
                $gateway = $query->from('mj_gateway')->where(['id' => $v['gid']])->select(['gateway_name'])->one();
                $stream = $query->from('mj_stream')->where(['id' => $v['sid']])->select(['stream_name'])->one();
                $data = [
                    'jid' => $id,
                    'join_name' => $data['join_name'],
                    'pid' => $v['pid'],
                    'gid' => $v['gid'],
                    'sid' => $v['sid'],
                    'project_name' => $project['project_name'],
                    'gateway_name' => $gateway['gateway_name'],
                    'stream_name' => $stream['stream_name'],
                    'add_date' => date('Y-m-d H:i:s', time()),
                    'threshold' => $v['threshold'],
                    'equation' => $v['equation'],
                    'condition' => $v['condition']
                ];
                $model->add($data);
            }
        }

    }

    public function orderListEdit($data, $id)
    {
        $query = new Query();
        $model = new Joinsorder();
        foreach ($data['issue_orders'] as $k => $v) {
            if ($v['id']){
                $model->edit($v);
            }else{
                $project = $query->from('mj_project')->where(['id' => $v['pid']])->select(['project_name'])->one();
                $gateway = $query->from('mj_gateway')->where(['id' => $v['gid']])->select(['gateway_name'])->one();
                $stream = $query->from('mj_stream')->where(['id' => $v['sid']])->select(['stream_name'])->one();
                $data = [
                    'jid' => $id,
                    'join_name' =>$data['join_name'],
                    'pid' => $v['pid'],
                    'gid' => $v['gid'],
                    'sid' => $v['sid'],
                    'project_name' => $project['project_name'],
                    'gateway_name' => $gateway['gateway_name'],
                    'stream_name' => $stream['stream_name'],
                    'add_date' => date('Y-m-d H:i:s', time()),
                    'trigger_value' => $v['trigger_value'],
                    'recovery_value' => $v['recovery_value']
                ];
                $model->add($data);
            }
        }
    }


    //删除联控记录
    public function actionDel()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $id = Yii::$app->request->post('id');
            $member = new Joins();
            $result = $member->del($id);
            if ($result) {
                $this->returnJson(0, '成功删除该工程');
            } else {
                $this->returnJson(-2, '删除失败');
            }
        } else {
            $this->returnJson(-1, '请提交合法数据');
        }
    }


}