<?php
namespace app\models;

class UsersLogin extends \Eloquent
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_login';
    
    public $timestamps = false;
    
    /**
     * To make the user logout from app
     * @param integer $userId
     * @param string $deviceId
     */
    public function logOutUser($userId, $deviceId)
    {
        $dateTime = date('Y-m-d H:i:s');
        $data = array('status' => 0, 'logout_time' => $dateTime);
        UsersLogin::where('user_id', '=', $userId)->where('device_id', '=', $deviceId)->update($data);
       
    }
    
    /**
     * Get device token user id
     * 
     * @param mixed(integer|array) $userId
     * @return \UsersLogin
     */
    public static function getUserLoginDetailsById($userIds,$multiple = false)
    {
        $query = UsersLogin::select(array('device_type as deviceType', 'device_push_token as devicePushToken', 'users.name as name'))
                ->join('users',  'users.id', '=', 'users_login.user_id')   
                ->where('users_login.status','=',\DB::raw(1))
                ->whereNotNull('users_login.device_push_token');
        if ($multiple && is_array($userIds)) {
            return $query->whereIn('users_login.user_id',$userIds)->get();
        } 
        
        return $query->where('users_login.user_id','=', $userIds)->get();
    }
}