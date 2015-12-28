<?php
namespace app\models;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use app\models\PushMessage;
use PDO;
use app\models\UserFollowerFan;

class UserModel extends \User
{
    protected $dates = ['deleted_at'];
	
    public function updatePassword($userId, $password)
    {
        return UserModel::where('id', '=', $userId)->update(array('password' => $password));
    }

    /**
     * Check old password is correct or not
     * @param integer $userId
     * @param string $oldPassword
     * @return boolean $flag
     */
    public function checkOldPassword($userId, $oldPassword)
    {
        $data = UserModel::find($userId);
        $flag = FALSE;
        if (Hash::check($oldPassword, $data->password)) {
            $flag = TRUE;
        }

        return $flag;
    }

    
    /**
     * Get user by email address
     * @param string $email
     * @return array UserModel
     */
    public function getUserByEmail($email)
    {
        return UserModel::where('email_id', '=', $email)->get()->toArray();
    }

    /**
     * Get user by twitter-id
     * @param integer $id
     * @return array UserModel
     */
    public function getUserByTwitterId($id)
    {
        return UserModel::where('twitter_id', '=', $id)->get()->first();
    }


    /**
     * Get list of all auto follow users
     * @return collection UserModel
     */
    public function getAutoFllowUsers()
    {
        return UserModel::where('auto_follow','=',DB::raw('1'))->orderBy('name','ASC')->get(array('id'));
    }
}