<?php
/**
 * 公用控制器
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/22
 * Time: 10:36
 */

namespace app\controllers\api;

use app\models\Member;
use app\models\Userprojectrule;
use Yii;
use yii\db\Query;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\Cors;
use yii\filters\VerbFilter;



class CommonController extends Controller
{
    public $enableCsrfValidation = true;
    public $project_id;
    public $is_admin;
    public $mid;
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $header = $this -> isHeader();

        $this -> getMid($header);
        $this -> mid = Yii::$app -> session -> get('mid');
        $this -> gePid();
    }


    //判断请求头
    public function isHeader()
    {
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result['Authorization'];
        } else {
            // 没有 apache_request_headers函数替代方法
            $header = [];
            $server = $_SERVER;

            foreach ($server as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
            $header = $header['authorization'];
        }
        $header = explode(' ',$header);
        $header = $header[1];
        return $header;
    }

    //获取用的项目id
    public function gePid()
    {
        $mid = $this -> mid;
        $model = new Member();
        $result = $model -> getMemberId($mid);
        $this -> is_admin = Yii::$app -> session -> get('is_admin');
        $this -> project_id = $result;
    }
    // 根据提交上来的鉴权密钥获取用户的id
    public function getMid($auth){
        $query = new Query();
        $res = $query -> from('mj_member_token')  -> where(['mtoken' => $auth]) -> select('mid') -> one();
        Yii::$app->session->set('mid' , $res['mid']);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
//        $auth = $behaviors['authenticator'];
//        if (Yii::$app->getRequest()->getMethod() !== 'OPTIONS')
//        {
//            $behaviors['authenticator'] = [
//                'class' => HttpBearerAuth::className(),
//            ];
//        }
//        else
//        {
//            // re-add authentication filter
//            $behaviors['authenticator'] = $auth;
//            // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
//            $behaviors['authenticator']['except'] = ['options'];
//        }
//        print_r($auth);
//        authorized
        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['*'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => ['*'],
            ],
        ];

        return $behaviors;
    }

//
//    public function anthLogin()
//    {
//        $get = Yii::$app -> request -> get();
//        $post = Yii::$app -> request -> post();
//        if ($get['mtoken'] == Yii::$app -> session['mtoken'] || $post['mtoken'] == Yii::$app -> session['mtoken'] ){
//            unset($get['mtoken']);
//            unset($post['mtoken']);
//
//        }else{
//            $this->returnJson(-9,'非会员不能操作');
//        }
//    }

    public function getCsrf()
    {
        $csrfparam = array(Yii::$app->request->csrfParam=>Yii::$app->request->getCsrfToken());
        $this->returnJson(0,'csrf',$csrfparam );
    }

    public function returnJson($code, $msg, $data=null)
    {
        $data = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        $response->data = $data;
    }
    public function GetWhere($n, $date)
    {
        $start = date('Y-m-d 00:00:00',strtotime("-$n day"));
        $end = date('Y-m-d 23:59:59',time());
        $where = ['between',$date,$start,$end];
        return $where;
    }


    // 获取最近多少天的日期
    function getDateSeven($day, $time = '', $format = 'Y-m-d')
    {
        $time = $time != '' ? $time : time();
        //获取当前周几
        // $week = date('w', $time);
        $date = [];
        for ($i = $day; $i >= 0; $i--) {
            $date[$i] = date($format, strtotime('-' . $i . ' days', $time));
        }
        return $date;
    }
    //求取时间段
    function  getTime($end,$start){
        $time = strtotime($end) - strtotime($start);
        $day = $time/(3600*24);
        $date = $this -> getDateSeven($day-1);
        return $date;
    }

    //获取月份时间
    function getMonth(){
        $month = [];
        for ($i = 1; $i <= 12; $i++){
            if ($i < 10) $i = '0' . $i;
            $m = ['month' => $i];
            array_push($month, $m);
        }
        return $month;
    }
    
    //获取当月的日期
    public function getDays()
    {
        $monthDays = [];
        $firstDay = date('Y-m-01', time());
        $i = 0;
        $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));
        while (date('Y-m-d', strtotime("$firstDay +$i days")) <= $lastDay) {
            $monthDays[$i]['day'] = date('Y-m-d', strtotime("$firstDay +$i days"));
            $i++;
        }
        return $monthDays;
    }

    //获取当前用户的工程权限
    public function getMemberRule($type=false){
        $where = [];
        $mid = $this -> mid;
        $project_id = $this -> project_id;
        if ($mid != 19){
            $where = ['in','pid',$project_id];
        }
        if ($type && $mid != 19){
            $where = ['in','id',$project_id];
        }
        return $where;
    }

    public function getMemberJuri(){
        $mid = $this -> mid;
        if ($mid != 19){
            $query = new Query();
            $model = new Userprojectrule();
            $result = $query -> from($model::tableName()) -> where(['mid' => $mid]) -> select('pid') -> one();

            return json_decode($result['pid'], true);
        }

    }
}