<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/2
 * Time: 9:13
 */

namespace app\controllers\api;

use app\models\Setting;
use Yii;
use app\models\Upfile;
use yii\db\Query;
use yii\web\UploadedFile;

class SettingController extends CommonController
{

    public function actionField()
    {
        $query = new Query();
        $model = new Setting();
        $data = $query -> from($model::tableName()) -> orderBy('id desc') -> one();
        if ($data){
            $this->returnJson(0, '查询成功',$data);
        }else{
            $this->returnJson(-1, '暂无数据');
        }
    }

    // 添加
    public function actionAdd()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (trim(!empty($data['title']))){
                $model = new Setting();
                $result = $model -> add($data);
                if ($result){
                    $this->returnJson(0,'设置成功');
                }else{
                    $this->returnJson(-3,'服务器忙请稍后再试');
                }
            }else{
                $this->returnJson(-2,'请输入系统名称');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    // 百年祭
    public function actionEdit()
    {
        if (Yii::$app -> request -> isPost){
            $data = Yii::$app -> request -> post();
            if (trim(!empty($data['title'])) && trim(isset($data['id']))){
                $model = new Setting();
                $result = $model -> edit($data);
                if ($result){
                    $this->returnJson(0,'设置成功');
                }else{
                    $this->returnJson(-3,'服务器忙请稍后再试');
                }
            }else{
                $this->returnJson(-2,'请输入系统名称');
            }
        }else{
            $this->returnJson(-1, '请提交合法数据');
        }
    }

    public function actionUpfile(){
        $model = new Upfile();
        if (Yii::$app->request->isPost) {
            $model->logo = UploadedFile::getInstanceByName('logo');
            if ($model->logo){
                if ($model->validate()) {
                    $filePath = $model->fileExists(Yii::$app->basePath.'/web/uploads/');  //上传路径
                    $extension = $model->logo->extension;
                    $name = substr(md5($model->logo->baseName . time()),1,20).strtotime('MHis',time());
                    $url = $filePath. $name . '.' . $extension;
                    $result = $model->logo->saveAs($url);
                    $this->returnJson(0,'上传成功', 'http://'.$_SERVER['HTTP_HOST'].'/uploads/'. $name . '.' . $extension);
                }
            }
            $model->bg = UploadedFile::getInstanceByName('bg');
            if ($model->bg){
                if ($model->validate()) {
                    $filePath = $model->fileExists(Yii::$app->basePath.'/web/uploads/');  //上传路径
                    $extension = $model->bg->extension;
                    $name = substr(md5($model->bg->baseName),1,20).strtotime('MHis',time());
                    $url = $filePath. $name . '.' . $extension;
                    $model->bg->saveAs($url);
                    $this->returnJson(0,'上传成功', 'http://'.$_SERVER['HTTP_HOST'].'/uploads/'. $name . '.' . $extension);
                }
            }
        }else{
            return false;
        }
    }
}