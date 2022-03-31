<?php
header("Access-Control-Allow-Origin: *");
//如果需要设置允许所有域名发起的跨域请求，可以使用通配符 * ，如果限制自己的域名的话写自己的域名就行了。
// 响应类型 *代表通配符，可以指出POST,GET等固定类型
header('Access-Control-Allow-Methods:* ');
// 响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');

(new yii\web\Application($config))->run();
