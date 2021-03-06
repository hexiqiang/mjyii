<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/3/16
 * Time: 14:28
 */

namespace app\controllers\edp;
use app\controllers\api\OneNetApi;
use app\mj\phpMQTT;
use app\models\Call;
use app\models\Callrecord;
use app\models\Controlrecord;
use app\models\Gateway;
use app\models\Joinrecord;
use app\models\Joinsorder;
use app\models\Joinsproject;
use app\models\Projects;
use app\models\Stream;
use app\models\Trigger;
use app\models\Userproject;
use Yii;
use yii\db\Query;

class Called
{

    //判断上报的数据是否是中移动或者是网关MQTT
    //是否超过报警的值
    public function Compare($gateway_id, $cloud_val, $value, $date)
    {
        $data = $this -> getGateway($gateway_id);
        if ($data['net_type'] == '中移动EDP'){
            file_put_contents(Yii::$app->basePath . '/web/log/ed.log','中移动EDP'.PHP_EOL,FILE_APPEND);
            $this -> doing($data, $cloud_val, $value, $date);
        }elseif ($data['net_type'] == '网关MQTT'){

            $this -> doing($data, $cloud_val, $value, $date);
        }
    }

    public function doing($data, $cloud_val, $value, $date)
    {

        unset($data['net_type']);
        $stream = $this -> getGatewayJoinStream($data['gid'],$cloud_val);
        $data['sid'] = $stream['sid'];
        $data['stream_name'] = $stream['stream_name'];
        // 获取该网关下的数据流的所有触发器
        $model = new Trigger();
        $query = new Query();
        $result = $query -> from($model::tableName()) -> where(['gid' => $data['gid'], 'sid' => $data['sid']]) -> select(['id as tid', 'call_name', 'threshold','equation','cid']) -> all();
        $this -> callDataInsert($result, $value, $data, $date);
        //通过网关id查询对应的联控设置，通过流量值判断是否高于或者低于预设值
        //此处送命令
        $this -> getProjectJoinCall($data['gid'],$value);
    }

    // 保存上报数据
    public function callDataInsert($result, $value, $data, $date)
    {
        if (is_array($result) && $result){
            // 判断数据流的触发值
            foreach ($result as $k => $v){
                if ($value . $v['equation'] . $v['threshold']){
                    $data['tid'] = $v['tid'];
                    $data['cid'] = $v['cid'];
                    $data['call_name'] = $v['call_name'];
                    break;
                }
            }
            $data['call_date'] = $date;
            $data['add_date'] = date('Y-m-d H:i:s',time());
            $model = new Callrecord();
            $data['call_message'] = $data['call_name'] . ':报警值' . $value;
            $openid = $this -> getMid($data['pid']);
            file_put_contents(Yii::$app->basePath . '/web/log/reboot.txt','发送'.$openid.PHP_EOL,FILE_APPEND);
            $data['call_type'] = $this -> sendMsg($data['cid'], $data['gateway_name'], $data['call_name'], $data['call_message'], $data['call_date'], $openid);
            $data['call_status'] = '等待中';
            if ($data['call_type']){
                $data['call_status'] = '报警中';
            }
            $result = Yii::$app -> db -> createCommand() -> insert($model::tableName(),$data)->execute();
            Yii::$app -> db -> close();
        }
    }
    //根据工程获取用户的mid
    public function getMid($pid)
    {
        $query = new Query();
        $m = new Userproject();
        $project = $query -> from($m::tableName()) -> select('mid', 'pid') -> all();
        $mid = '';
        foreach ($project as $k => $v){
            $project[$k]['pid'] = json_decode($v['pid'],true);
        }
        foreach ($project as $k => $v){
            if (is_array($v['pid'])){
                foreach ($v['pid'] as $va){
                    if ($pid == $va){
                        $mid = $k['mid'];
                        break;
                    }
                }
            }else{
                break;
            }
        }
        $query = new Query();
        $openid = $query -> from('mj_member_openid') -> where(['mid' => $mid]) -> select('openid') -> one();
        if (!$openid){
            $openid = $query -> from('mj_member_openid') -> where(['mid' => 19]) -> select('openid') -> one();
        }
        return $openid['openid'];
    }


    //报警通知

    /**
     * @param $cid 报警器管理id
     */
    public function sendMsg($cid, $gateway_name, $call_name, $call_message, $call_time, $openid)
    {
        $model = new Call();
        $query = new Query();
        $result = $query -> from($model::tableName()) -> where(['id' => $cid]) -> select(['call_type', 'call_phone','id']) -> one();
        $type = json_decode($result['call_type'], true);
        file_put_contents(Yii::$app->basePath . '/web/log/reboot.txt','发送'.$gateway_name.PHP_EOL,FILE_APPEND);
        $result = $this -> callDoing($type, $gateway_name, $call_name, $call_message, $call_time, $openid);
        return $result;
    }

    // 报警通知操作
    public function callDoing($type, $gateway_name,$call_name,$call_message,$call_time,$openid)
    {
        file_put_contents(Yii::$app->basePath . '/web/log/reboot.txt','发送'.$call_name.PHP_EOL,FILE_APPEND);
        $this -> wxsend($gateway_name,$call_name,$call_message,$call_time,$openid);
        foreach ($type as $v) {
            switch ($v){
                case 'app':
                    //doing
                    return 'app通知';

                    break;
                case '短信':
                    //doing:
                    return '短信通知';
                    break;
                case '电话':
                    //doing
                    return '电话通知';
                    break;
            }
        }
    }

    //查询数据流的id
    public function getGatewayJoinStream($gid,$cloud_val)
    {
        $model = new Stream();
        $query = new Query();
        $result = $query -> from($model::tableName()) -> where(['gid' => $gid, 'cloud_var' => $cloud_val]) -> select(['id as sid','stream_name']) -> one();
        return $result;
    }

    //查询网关对应的数据
    public function getGateway($gateway_id)
    {
        $model = new Gateway();
        $query = new Query();
        $gateway = $query -> from($model::tableName()) -> where(['gateway_id' => $gateway_id]) -> select(['id as gid', 'gateway_name', 'pid','net_type']) -> one();
        $data = $this -> getGatewayJoinProject($gateway);
        return $data;
    }

    //查询网关的所属工程
    public function getGatewayJoinProject($data)
    {
        $model = new Projects();
        $query = new Query();
        $result = $query -> from($model::tableName()) -> where(['id'=>$data['pid']]) -> one();
        $data['project_name'] = $result['project_name'];
        return $data;
    }
    //查询工程下的联控命令

    /**
     * @param $gid 网关id
     * @param $value 报警值
     */
    public function getProjectJoinCall($gid, $value)
    {
        // 查询该网关关联的所有联控设置
        $query = new Query();
        $model = new Joinsproject();
        //查询该网关关联了多少个联控
        $data = $query -> from($model::tableName()) -> where(['gid' => $gid]) -> select(['jid', 'threshold', 'equation','condition']) -> all();
//        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($data).'111'.PHP_EOL,FILE_APPEND);
        // 通过分析获取需要发送命令的联控
        $jid = $this -> getEqua($data,$value);
//        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($jid).'222'.PHP_EOL,FILE_APPEND);
        //获取同一个联控下的所有条件
        $condition = $this -> getConditionRow($jid, $value);

        // 根据条件判断是否需要触发命令
        $jid = $this -> isSendOrder($condition);
//        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($jid).'3333'.PHP_EOL,FILE_APPEND);
        // 获取联控的下发命令
        $order = $this -> getJoinsOrders($jid);
//        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($order).'555'.PHP_EOL,FILE_APPEND);
        $this -> getOrderSend($order);
        // 触发命令后保存数据
    }


    //判断分析的成立的网关联控
    public function getEqua($data, $value)
    {
        $jid = [];
        if (is_array($data)){
            // 判断该条件成立的网关联控
            foreach ($data as $k => $v){
                if ($value . $v['equation'] . $v['threshold']){
                    array_push($jid,$v['jid']);
                }
            }
        }
        $jid = array_unique($jid);
        $arr = [];
        foreach ($jid as $j){
            array_push($arr, $j);
        }
        return $arr;
    }

    // 获取网关关联联控的全部数据
    public function getConditionRow($jid, $value)
    {
        $query = new Query();
        $model = new Joinsproject();
        $result = [];
        foreach ($jid as $v) {
            $data = $query->from($model::tableName())-> where(['jid' => $v]) -> orderBy('condition asc') ->select(['jid', 'threshold', 'equation', 'condition'])->all();
            array_push($result,$data);
        }
        $conditionArr = [];

        foreach ($result as $key => $val){
            $condition = '';
            foreach ($val as $k => $va){
                if (!$va['condition']){
                    $condition .= $value . $va['equation'] . $va['threshold'];
                }else{
                    $condition .=  ' ' .  $va['condition'] . ' ' . $value . $va['equation'] . $va['threshold'];
                }
                $join_id = $va['jid'];
            }
            array_push($conditionArr,['condition' => $condition, 'jid' => $join_id]);
        }
//        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($conditionArr).'3333'.PHP_EOL,FILE_APPEND);
        return $conditionArr;
    }

    //获取联控下的全部需要发送的命令数据
    public function getJoinsOrders($jid)
    {
        $query = new Query();
        $model = new Joinsorder();
        $data = [];
        foreach ($jid as $v){
             $result = $query -> from($model::tableName()) -> where(['jid' => $jid]) -> all();
             foreach ($result as $ke => $va){
                array_push($data,$va);
             }
         }
        return $data;
    }


    //通过返回的条件判断是否需要发送命令
    public function isSendOrder($condition)
    {

        $jid = [];
        foreach ($condition as $k => $v){
            $boole = self::evalString($v['condition']);
            if ($boole){
                array_push($jid,$v['jid']);
            }
        }
        return $jid;
    }

    protected static function evalString($str)
    {
        return eval("return $str;");
    }


    //根据要下发的命令进行命令下发
    public function getOrderSend($order)
    {
//        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($order).'进入发送命令'.PHP_EOL,FILE_APPEND);
        foreach ($order as $item => $v) {
            $this -> saveJoinControlRecord($v);
            $this -> sendOrder($v['recovery_value'], $v['gid'], $v['sid']);
        }
    }


    //下面为发送命令代码
    //发送命令
    public function sendOrder($order, $gid, $sid)
    {
        $query = new Query();
        $model = new  Gateway();
        $gateway = $query -> from($model::tableName()) -> where(['id' => $gid]) -> select(['gateway_id','master_apikey', 'net_type']) -> one();
//        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($order).'进入发送命令判断区域'.PHP_EOL,FILE_APPEND);
        switch ($gateway['net_type']){
            case '中移动EDP':
                $this -> sendEDP($order, $gateway['gateway_id'], $gateway['master_apikey'], $gid, $sid);
                break;
            case '网关MQTT':
//                file_put_contents(Yii::$app->basePath . '/web/log/ed.log','数据流id:'.$sid.PHP_EOL,FILE_APPEND);
                $this -> sendMQTT($order,$gateway['gateway_id'],$gid, $sid);
                break;
        }
    }

    //发送MQTT命令到
    public function sendMQTT($order, $gateway_id, $gid, $sid)
    {

        $client_id = "/".$gateway_id ; // 设置你的连接客户端id

        $mqtt = new phpMQTT(Yii::$app -> params["mqLocalhost"], Yii::$app -> params["mqPort"], $client_id);
        if ($mqtt->connect(true, NULL, '', '')) {
            //如果创建链接成功
            $mqtt->publish($client_id, $order, 0);
            // 发送到 xxx3809293670ctr 的主题 一个信息 内容为 setr=3xxxxxxxxx Qos 为 0
            $mqtt->close();    //发送后关闭链接
            $pk = $this -> saveControl($gid, $order, $sid);
        } else {
            echo "Time out!\n";
        }
    }

    //发送中移动的命令
    public function sendEDP($order, $gateway_id, $master_apikey, $gid, $sid)
    {
//        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($order).'发送mqtt'.PHP_EOL,FILE_APPEND);
        $one = new OneNetApi($master_apikey);
        $result = $one -> send_data_to_edp_mqtt_use_device_id($gateway_id, $order,['timeout' => 300]);
        if ($result){
            $pk = $this -> saveControl($gid, $order, $sid);
            $res = $this -> getGatewayRes($master_apikey,$result['cmd_uuid'], $pk, $gid);
//            file_put_contents(Yii::$app->basePath . '/web/log/order.txt',json_encode($res)  .PHP_EOL,FILE_APPEND);
        }else{
            $this->returnJson(-1,'命令发送失败');
        }
    }

    //查询命令的响应状态
//状态值	              状态描述	                      常见场景
//1	命令已创建         Command Created	             设备离线，调用发送命令API，带有timeout参数时
//2	命令已发往设备     Command Sent	                 设备在线，但是未做命令应答时
//4	设备正常响应       Command Response Received	 设备接收到命令且正常发送命令响应
    public function getGatewayRes($master_apikey, $cmd_uuid, $pk, $gid)
    {
        $one = new OneNetApi($master_apikey);
        $data = $one -> get_dev_status($cmd_uuid);
        $model = new Controlrecord();

        if ($data['status'] == 4){
            $field['control_status'] = '成功';
            Yii::$app -> db -> createCommand() -> update($model::tableName(), $field,'id='.$pk) -> execute();
        }elseif ($data['status'] == 2){
            $field['control_status'] = '成功';
            Yii::$app -> db -> createCommand() -> update($model::tableName(), $field,'id='.$pk) -> execute();
        }elseif ($data['status'] == 1){
            $field['control_status'] = '失败';
            $re = Yii::$app -> db -> createCommand() -> update($model::tableName(), $field,'id='.$pk) -> execute();
            if ($re){
                $model = new Gateway();
                $param['status'] = 0;
                Yii::$app -> db -> createCommand() -> update($model::tableName(), $param,'id='.$gid) -> execute();
            }
        }
        return $data['status'];
    }

    // 保存发送控制命令操作
    public function saveControl($gid, $msg, $sid=null)
    {
        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($msg).'保存发送记录'.PHP_EOL,FILE_APPEND);
        $model = new Gateway();
        $query = new Query();
        $result = $query -> from($model::tableName()) -> where(['id' => $gid]) -> select(['pid', 'gateway_name']) -> one();
        $model = new Projects();
        $query = new Query();
        $project = $query -> from($model::tableName()) -> where(['id' => $result['pid']]) -> select(['project_name']) -> one();
        $model = new Stream();
        $query = new Query();
        $stream = $query -> from($model::tableName()) -> where(['id' => $sid]) -> select(['stream_name']) -> one();
        $data = [
            'pid' => $result['pid'],
            'gid' => $gid,
            'sid' => $sid,
            'project_name' => $project['project_name'],
            'gateway_name' => $result['gateway_name'],
            'stream_name' => $stream['stream_name'],
            'add_time' => date('Y-m-d H:i:s',time()),
            'control_date' => date('Y-m-d H:i:s',time()),
            'post_orders' => $msg,
        ];
        $model = new Controlrecord();
        $result = Yii::$app -> db -> createCommand() -> insert($model::tableName(),$data) -> execute();
        if ($result){
            $pk = Yii::$app -> db -> getLastInsertID();
            Yii::$app -> db -> close();
            return $pk;
        }
    }

    //保存发送的联控命令
    public function saveJoinControlRecord($order)
    {
        file_put_contents(Yii::$app->basePath . '/web/log/ed.log',json_encode($order).'保存联控记录'.PHP_EOL,FILE_APPEND);
        $data = [
            'pid' => $order['pid'],
            'project_name' => $order['project_name'],
            'gid' => $order['jid'],
            'gateway_name' => $order['gateway_name'],
            'sid' => $order['sid'],
            'stream_name' => $order['stream_name'],
            'jid' => $order['jid'],
            'join_name' => $order['join_name'],
            'add_date' => date('Y-m-d H:i:s',time()),
            'join_date' => date('Y-m-d H:i:s',time()),
            'order' => $order['recovery_value'],
            'join_status' => '触发控制',
        ];
        $model = new Joinrecord();
        $result = Yii::$app -> db -> createCommand() -> insert($model::tableName(),$data) -> execute();
        if ($result){
            $pk = Yii::$app -> db -> getLastInsertID();
            Yii::$app -> db -> close();
        }
    }



    //报警小程序发送服务通知
    //Hc7gzOPdRrXFLI9AJ-Iie8kZ4YY6E4jLiZCpLFv-EMI
    //https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send?access_token=ACCESS_TOKEN
    //请求参数

    public function wxsend($gateway_name, $call_name, $call_message, $call_time, $openid)
    {

        $template_id = 'kv9SC6IHFkxYGlVwhB9iBGH45ve6gLxAjSn7rC_HILw';
        $msg = [
            'thing1' => ['value'=>$gateway_name],
            'thing2' => ['value'=> $call_name],
            'time3' => ['value'=> $call_time],
            'thing4' => ['value'=> $call_message],
        ];
        $access_token = $this->getToken();
        //请求url
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $access_token ;
        //发送内容
        $data = [] ;
        $data['touser'] = $openid;  //用户的openid
        $data['template_id'] = $template_id; //所需下发的订阅模板id
        //点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
        $data['page'] = 'pages/called/index';
        //模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
        $data['data'] = $msg;
        //跳转小程序类型：developer为开发版；trial为体验版；formal为正式版；默认为正式版
        $data['miniprogram_state'] = 'developer';
        $data['lang'] = 'zh_CN';
        $json_data = json_encode($data);
        file_put_contents(Yii::$app->basePath . '/web/log/reboot.txt','发送'.$json_data.PHP_EOL,FILE_APPEND);
        return [
            'openid' => $openid,
            'data' => $this->curl_post($url, $json_data)//这里面就是个curl请求 , 转成数组返回
        ];
    }


    public function curl_post($url , $data){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        // POST数据

        curl_setopt($ch, CURLOPT_POST, 1);

        // 把post的变量加上

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);

        curl_close($ch);

        file_put_contents(Yii::$app->basePath . '/web/log/reboot.txt','接收'.json_encode($output,true).PHP_EOL,FILE_APPEND);
        return $output;
    }

    public function curl_data($url)
    {
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$url); //要访问的地址
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        $data = json_decode(curl_exec($ch));
        if(curl_errno($ch)){
            curl_error($ch); //若错误打印错误信息
        }
        //打印信息
        curl_close($ch);//关闭curl
        return $data;
    }

    public function getWxToken($appid, $appsecret)
    {
        $token = Yii::$app -> redis -> get('token');
        if ($token){
            return $token;
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
            $data = $this->curl_data($url);
            $access_token = $data -> access_token;
            Yii::$app -> redis -> set('token',$access_token);
            Yii::$app -> redis -> expire('token',7000);
            return $access_token;
        }

    }

    public function getToken()
    {
        $query = new Query();
        $re = $query -> from('mj_setting') -> select(['appid', 'appsecret']) -> one();
        $access_token = $this -> getWxToken($re['appid'],$re['appsecret']);
        return $access_token;
    }

}