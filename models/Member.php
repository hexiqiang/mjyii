<?php
/**
 * Created by PhpStorm.
 * User: borui
 * Date: 2022/2/22
 * Time: 15:50
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\web\IdentityInterface;

class Member extends ActiveRecord implements IdentityInterface
{
    public $member;
    public $password;
    public $phone;
    public $username;
    public static function tableName()
    {
        return 'mj_member';
    }
    public function rules()
    {
        return [
            // username and password are both required
            [['member', 'password', 'phone'], 'required'],
        ];
    }
    public function validateMember($params, $id=null)
    {
        if (!$this->hasErrors()) {
            $member = $this->getMember($params, $id);
            return $member;
        }
    }

    //检查是否存在该账户
    public function getMember($member, $id=null)
    {
        $query = new Query();
        if ($id){
            $member = $query -> from(self::tableName())
                -> where(['member' => $member])
                -> andWhere("id!=$id")
                -> one();
        }else{
            $member = $query -> from(self::tableName())
                -> where(['member' => $member])
                -> one();
        }

        return $member;
    }

    //获取用户列表
    public function getMembers($offset, $limit, $mid = null,$where=[])
    {
        $query = new Query();
        $data['totalCount'] = $query -> from(self::tableName()) -> count();
        $andfieldwhere = [];
        if ($mid != 19){
            $andfieldwhere = ['pid' => $mid];
        }
        $data['page'] = ceil($data['totalCount'] / 10 );
        $data['data'] = $query -> from(self::tableName())
            -> limit($limit)
            -> offset($offset)
            -> where($where)
            -> andFilterWhere($andfieldwhere)
            -> select(['*'])
            -> orderBy([
                'id' => SORT_ASC,
                'member' => SORT_ASC,
            ])
            -> all();
        $query1 = new Query();

        foreach ($data['data'] as $k => $v){
            $data['data'][$k]['is_admin'] = $v['is_admin'] == 1 ? '是' : '否';
            $data['data'][$k]['status'] = $v['status'] ? true : false;
        }

        return $data;
    }

    //添加保存账号
    public function addMember($data)
    {
        $result = Yii::$app->db->createCommand()->insert(self::tableName(), $data)->execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

    //编辑保存账号
    public function saveEditMember($data)
    {
        $result = Yii::$app -> db -> createCommand() -> update(self::tableName(), $data,"id=".$data['id']) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }

    //删除账号
    public function delMember($id)
    {
        $result = Yii::$app -> db -> createCommand() -> delete(self::tableName(), "id=".$id) -> execute();
        if ($result){
            return true;
        }else{
            return false;
        }
    }




    //验证登录
    public static function findByUsername($username, $password)
    {
        $query = new  Query();
        $result = $query -> from(self::tableName()) -> where(['member' => $username]) -> select(['member','password','id']) -> one();
        if ($result){
            if ($password == $result['password'] && $username == $result['member']){
                $token = md5($result['member'] . $result['password'].time());
                if ($token){
                    $data = [
                        'mid' => $result['id'],
                        'login_date' => date('Y-m-d H:i:s',time()),
                        'mtoken' => $token,
                    ];
                    Yii::$app -> db -> createCommand() -> update('mj_member',['access_token' => $token],'id='.$result['id']) -> execute();
                    Yii::$app -> db -> createCommand() -> insert('mj_member_token',$data) -> execute();
                    Yii::$app -> session['mtoken'] = $token;
                }
                $data = ['member' => $result['member'],'mid' => $result['id'],'mtoken' => $token];
                return $data;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }



    /**
     * 根据给到的ID查询身份。
     *
     * @param string|integer $id 被查询的ID
     * @return IdentityInterface|null 通过ID匹配到的身份对象
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * 根据 token 查询身份。
     *
     * @param string $token 被查询的 token
     * @return IdentityInterface|null 通过 token 得到的身份对象
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @return int|string 当前用户ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string 当前用户的（cookie）认证密钥
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    //根据账号ID获取用户的账号
    public function getMemberId($mid)
    {
        $query = new Query();
        $result = $query -> from(self::tableName()) -> where(['id' => $mid]) -> select('member') -> one();
        if ($result['member'] == 'admin'){
            Yii::$app -> session -> set('is_admin',true);
            return true;
        }else{
            Yii::$app -> session -> set('is_admin',false);
            $result = $this -> getMemberProject($mid);
            return $result;
        }
    }

    public function getMemberProject($mid){
        $query = new Query();
        $result = $query -> from('mj_member_join_project') -> where(['mid' => $mid]) -> select(['pid']) -> one();
        if ($result){
            $result = json_decode($result['pid'],true);
            Yii::$app -> session -> set('pid',$result);
            return $result;
        }else{
            return false;
        }
    }
}