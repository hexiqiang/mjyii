<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/3
 * Time: 17:00
 */

namespace app\controllers\api;


use app\models\LoginForm;
use app\models\Member;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use Yii;

class LoginController extends Controller
{
    public $enableCsrfValidation = false;
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }
    public function behaviors()
    {
        $behaviors = [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [],
            ],
            'corsFilter' => [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Origin' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 86400,
                    'Access-Control-Expose-Headers' => [],
                ],
            ],
        ];
        return $behaviors;
    }

    public function actionLogin()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (isset($data['type'])){
                $data['password'] = md5($data['password']);
            }
            $model = new Member();
            $member = $data['username'];
            $password = $data['password'];
            $result = $model::findByUsername($member,$password);
            if ($result != false){
                $this -> returnJson(0,'登录成功',$result);
            }else{
                $this -> returnJson(-1,'账号或密码有误');
            }
        }else{
            $this -> returnJson(-1,'账号或密码有误');
        }
    }

//    public function actionLogin1()
//    {
//        $model = new LoginForm();
//        $model->setAttributes(Yii::$app->request->post());
//        if ($model->login()) {
//            return [
//                'code' => 200,
//                'message' => '登陆成功',
//                'data' => [
//                    'access_token' => $model->user->access_token
//                ]
//            ];
//        }
//        return [
//            'code' => 500,
//            'message' => $model->errors
//        ];
//    }

    public function actionSignout()
    {

    }

    public function actionChecklogin()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            $token = $data['token'];
            $mid = $data['mid'];
            $query = new Query();
            if ($token ){
                $re = $query -> from('mj_member') -> where(['access_token'=>$token,'id' => $mid]) -> one();
                if (!$re){
                    $this->returnJson(0,'请登录');
                }
            }else{
                $this->returnJson(0,'请登录');
            }
        }

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
}