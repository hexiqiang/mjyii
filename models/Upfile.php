<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/2
 * Time: 9:11
 */

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class Upfile extends Model
{
    public $logo;
    public $bg;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['logo','bg'], 'file']
        ];
    }

    public function fileExists($uploadpath)
    {
        if(!file_exists($uploadpath)){
            mkdir($uploadpath);
        }
        return $uploadpath;
    }

}