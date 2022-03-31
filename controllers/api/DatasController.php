<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/8
 * Time: 13:44
 */

namespace app\controllers\api;

use app\models\Callrecord;
use app\models\Controlrecord;
use app\models\Gateway;
use app\models\Joinrecord;
use app\models\Stream;
use app\models\Trigger;
use Yii;
use app\models\Streamrecord;
use yii\db\Query;

class DatasController extends CommonController
{
    //    报表查询
    public function actionGetstreamrecord($pid=null,$gid=null,$sid=null,$days=7,$time=10,$start=null,$end=null)
    {
        $model = new Streamrecord();
        $date = $this -> getDateSeven($days);
        $where = ['between','get_date',$date[count($date) - 1] . ' ' . date('H:i:s',time()),$date[0] . ' ' . date('H:i:s',time())];
//        print_r($where);
        $min = $model -> getMin($sid,$where);
        $max = $model -> getMax($sid,$where);
        $agv = $model -> getAvg($sid,$where);
        $comp = $model -> getComp($sid);
        $query = new Query();
        $model = new Gateway();
        $gateway_id = $query -> from($model::tableName())  -> where(['id' => $gid]) -> select(['gateway_id']) -> one();
        $data = $this -> Getdatas($sid, $days, $time);
        $data =[
            'min' => $min ? $min . $comp : 0,
            'max' => $max ? $max . $comp : 0,
            'agv' => $agv ? round($agv, 2) . $comp : 0,
            'section' => $min && $max ? '[' . $min . $comp . '~' . $max . $comp .']' : 0,
            'data' => $data
        ];
        $this->returnJson(0,'查询成功',$data);
    }

    public function Getdatas($sid, $day=7, $time=10)
    {
        $days = $this -> getDateSeven($day-1);
        foreach ($days as $k => $v){
            $days[$k] = $v . ' ' . date('H:i:s',time());
        }
        $model = new Streamrecord();
        $query = new Query();
        $last = $days[0];
        $one = $days[count($days)-1];

        $date = $this -> getTimeDays($last, $one, $time);
        sort($date);
        $data = [];

        foreach ($date as $k => $v){
            $data[$k]['日期'] = $v;
            $where = ['between','get_date', $date[$k] ,$date[$k+1] ? $date[$k+1] : $date[$k]];
            $result = $query -> from($model::tableName()) -> where(['sid' => $sid]) -> andWhere($where) -> select('value') -> one();
            $data[$k]['流量'] = $result['value'] ? $result['value']: 0;
        }
        return $data;
    }

    public function getTimeDays( $one, $last,$time=10, $data=[])
    {
        array_push($data,$one);
        if ($one >= $last) {
            $day = date('Y-m-d H:i:s',strtotime($one) - 10 * 60);
            return $this->getTimeDays($day, $last, $time, $data);
        }
        return $data;

    }

    // 根据条件查询对应的历史数值
    public function actionGetstreamrecorddatas($offset=null,$limit=null,$pid=null,$gid=null,$sid=null,$days=null,$start=null,$time=null)
    {
        $query = new Query();
        $model = new Streamrecord();
        $data['totalCount'] = $query -> from($model::tableName()) -> where(['sid' => $sid])  -> count();
        $data['data'] = $query
            -> from($model::tableName())
            -> where(['sid' => $sid])
            -> orderBy('get_date desc')
            -> select(['*'])
            -> offset($offset)
            -> limit($limit)
            -> all();
        $query = new Query();
        $model = new Trigger();
        $query1 = new Query();
        $model1 = new Gateway();
        foreach ($data['data'] as $k => $v){
            $result = $query -> from($model::tableName())->where(['sid'=>$sid])  -> select(['threshold'])->one();
            $gateway = $query1 -> from($model1::tableName())->where(['gateway_id'=>$v['gateway_id']]) -> select(['gateway_name'])->one();
            $data['data'][$k]['gateway_name'] = $gateway['gateway_name'];
            if($v['value'] >= $result['threshold']){
                $data['data'][$k]['status'] = '正常';
            }else{
                $data['data'][$k]['status'] = '不正常';
            }

        }
        $this->returnJson(0,'查询成功',$data);
    }



    //报警记录查询
    public function actionGetcallrecord($pid, $days=null, $start=null, $end=null)
    {

    }

    public function getModel($type)
    {
        switch ($type){
            case 'call':
                $model = new Callrecord();
                break;
            case  'control':
                $model = new Controlrecord();
                break;
            case 'join':
                $model = new Joinrecord();
                break;
        }
        return $model;
    }

    public function getField($type)
    {
        switch ($type){
            case 'call':
                $add_date = 'call_date';
                break;
            case  'control':
                $add_date = 'control_date';
                break;
            case 'join':
                $add_date = 'join_date';
                break;
        }
        return $add_date;
    }

    //报警分页记录查询
    public function actionGetcalldata($pid, $type,$days=7, $start=null, $end=null)
    {
        $model = $this->getModel($type);
        $add_date = $this->getField($type);

        $query = new Query();
        if ($days){
            $days = $this -> getDateSeven($days - 1);
            rsort($days);
        }
        if ($start && $end){
            $days = $this -> getTime($end,$start);
            rsort($days);
        }
        $stream = $this -> getGawetayStream($pid);
        $arr = [];
        $rows = [];
        $label = ['日期'];
        $all = 0;
        $array =[];
        foreach ($stream as $k => $v){
            array_push($label, $v['stream_name']);
            foreach ($days as $ke => $va){
                $total = 0;
                $count = $query -> from($model::tableName())
                    -> where(['pid' => $pid])
                    -> where(['sid' => $v['id']])
                    -> andWhere(['like',$add_date,$va])
                    -> count();
                $result = $query -> from($model::tableName())
                    -> where(['pid' => $pid])
                    -> where(['sid' => $v['id']])
                    -> andWhere(['like', $add_date,$va])
                    -> select(['gateway_name', 'stream_name'])
                    -> one();
                if ($result){
                    $total += $count;
                    $result['count'] = $total;
                    $result['add_date'] = $va;
                }
                if ($result){
                    array_push($arr,$result);
                }
                if ($k == 0){
                    array_push($rows,['日期'=>$va, '条数'=> $total ? (int)$total : 0]);
                    array_push($array, ['日期'=>$va]);
                }
                array_push($array[$ke],   [$v['stream_name'] => $count]);
            }
            $all += $total;
        }
        $new_array = [];
        foreach ($array as $key => $value){
            $new_array[$key] = [];
            if (is_array($value) &&  count($value) > 0){
                foreach ($value as $k => $val){
                    if (is_array($val)){
                        foreach ($val as $m => $n){
                            $new_array[$key][$m] =  $n;
                        }

                    }else{
                        $new_array[$key][$k] =  $val;
                    }
                }
            }
        }
        $rows = $new_array;
        sort($rows);
        $res = $this -> getNum($arr);
        $data['data'] = $arr;
        $data['min'] = $res['min'];
        $data['max'] =  $res['max'];
        $data['agv'] =  round($res['agv'],2);
        $data['section'] =  $res['section'];
        $data['rows'] =  $rows;
        $data['label'] =  $label;
        $this->returnJson(0,'查询成功',$data);
    }

    //整理多个数据流报表数据
//    public function getStreamDatas($stream, $rows)
//    {
//        if (is_array($stream)){
//            foreach ($rows as $k => $v){
//                $rows[$k%count($stream)]['日期'] = $rows[$k%count($stream)]['日期'];
//                foreach ($stream as $ke => $va){
//                    $stream_name = $stream[$k%count($stream)]['stream_name'];
//                    $rows[$k%count($stream) == $ke][$stream_name] = $rows[$k%count($stream)]['count'];
//                }
//            }
//        }
//        print_r($rows);
//    }

    public function getNum($data)
    {
        if ($data){
            $num = [];
            foreach ($data as $k => $v){
                array_push($num,$v['count']);

            }
            $min = min($num);
            $max = max($num);
            $agv = array_sum($num) / count($num);
            $section = '[' . $min . '~' . $max . ']';
            $data['min'] = $min;
            $data['max'] = $max;
            $data['agv'] = $agv;
            $data['section'] = $section;
        }else{
            $data['min'] = 0;
            $data['max'] = 0;
            $data['agv'] = 0;
            $data['section'] = '[' . 0 . '~' . 0 . ']';
        }
        return $data;
    }

    //根据工程ID获取对应的网关下的数据流
    public function getGawetayStream($pid)
    {
        $model = new Gateway();
        $query = new Query();
        $data = $query -> from($model::tableName()) -> where(['pid'=>$pid]) -> select(['id']) -> all();
        $streams = [];
        // 获取网关下对应的数据流
        $model = new Stream();
        $streamQuery = new Query();
        if ($data){
            foreach ($data as $v){
                $stream = $streamQuery -> from($model::tableName()) -> where(['gid'=>$v['id']]) -> select(['id','stream_name']) -> all();
                if ($stream){
                    foreach ($stream as $va){
                        array_push($streams,['id' => $va['id'], 'stream_name' => $va['stream_name']]);
                    }
                }
            }
        }
        return $streams;
    }

    public function actionGetboxdata($pid,$gid,$sid,$day='')
    {
        $query = new Query();
        $model = new Streamrecord();
        if ($day){
            $day = $this->getDateSeven($day);
            $datas = $this -> formatDays($sid,$day);
        }else{
            $datas =$this -> formatDays($sid);
        }

        $this->returnJson(0,'success',$datas);
    }

    public function formatDays($sid,$day='')
    {
        $query = new Query();
        $model = new Streamrecord();
        $date = [];
        if ($day){
            $date = $day;
            sort($date);
        }else{
            $days = $query -> from($model::tableName()) -> where(['sid' => $sid]) -> select(['get_date']) -> orderBy('get_date asc') -> all();
            foreach ($days as $k => $v){
                array_push($date,date('Y-m-d',strtotime($v['get_date'])));
            }
            $date = array_unique($date);
            sort($date);
        }

        $datas = [];
        foreach ($date as $ke => $va){
            $count = $query -> from($model::tableName()) -> where(['sid' => $sid]) -> andFilterWhere(['like','get_date',$va]) -> count();
            $datas[$ke]['日期'] = $va;
            $datas[$ke]['条数'] = $count;
        }
        return $datas;
    }
}