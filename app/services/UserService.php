<?php

namespace app\services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use app\models\UserModel;
use app\models\UsersLogin;
use Illuminate\Support\Facades\Config;
use App\Helpers\MailHelper;

class UserService extends ApiBaseService
{

	private $modelObjectUser;
	private $modelOjectUserFollowerFan;
	private $modelObjectTicker;

	/**
	 * To initialize variables/objects
	 */
	public function __construct()
	{
		parent::__construct();
		$this->modelObjectUser = new UserModel();
	}

	/**
	 * API - register new user
	 * @param array $userData
	 * @return array $messages
	 */
	public function newUser($userData, $deviceId)
	{
		$emailValidation = $this->emailValidation($userData);
		$nameValidation = $this->validateUserName($userData);
		$mobileNoValidation = $this->validateMobileNo($userData);
		// Check is email, name available
		if ($nameValidation->fails() || $emailValidation->fails() || $mobileNoValidation->fails()) {
			$emailMessages = $emailValidation->messages();
			$nameMessages = $nameValidation->messages();
			if ($emailMessages->has('email_id')) {
				$this->messages['error'] = Config::get('constants.errorMessages.' . $emailMessages->first('email_id'));
			}

			if ($nameMessages->has('user_name')) {
				$this->messages['error'] = Config::get('constants.errorMessages.' . $nameMessages->first('user_name'));
			}

			if ($mobileNoValidation->has('mobile_no')) {
				$this->messages['error'] = Config::get('constants.errorMessages.' . $nameMessages->first('mobile_no'));
			}
		}
		$userData['device_id'] = $deviceId;
		$this->validateData($userData);
		if (empty($this->messages['error'])) {
			$this->modelObjectUser->user_name = $userData['user_name'];
			$this->modelObjectUser->email_id = $userData['email_id'];
			$this->modelObjectUser->password = Hash::make($userData['password']);
			$this->modelObjectUser->mobile_no = $userData['mobile_no'];
			$this->modelObjectUser->status = 'active';
			$this->modelObjectUser->login_status = 1;

			$this->modelObjectUser->save();
			$lastInsertId = '';
			$lastInsertId = $this->modelObjectUser->id;

			$this->messages['status'] = $this->successStatus;
			$this->messages['statusCode'] = 201;
			$user = UserModel::select('user_name', 'email_id', 'mobile_no')->where('id', '=', $lastInsertId)->get()->toArray();
			$this->messages['data']['Success'] = $user;
			$this->messages['access_token'] = $this->mapUserAndDevice($lastInsertId, $deviceId, $userData['type'], $userData['push_token']);
		}

		return $this->messages;
	}

	/**
	 * API- check for unique user name
	 * @param array $userData
	 * @return array $messages
	 */
	public function checkUserName($userData)
	{
		$validation = $this->validateUserName($userData);
		if ($validation->fails()) {
			$messages = $validation->messages();
			$this->messages['error'] = Config::get('constants.errorMessages.' . $messages->first('name'));
		} else {
			$this->messages['status'] = $this->successStatus;
		}

		return $this->messages;
	}

	/**
	 * API - authenticate user
	 * @param array $postData
	 * @return array $messages
	 */
	public function doLogin($postData, $deviceId)
	{
		$postData['device_id'] = $deviceId;
		$this->validateData($postData);
		$loginCredentials = array('password' => $postData['password']);
		$rules = array('user_name' => 'email');
		$validation = Validator::make($postData, $rules);
		if ($validation->fails()) {
			$loginCredentials['user_name'] = $postData['user_name'];
		} else {
			$loginCredentials['email_id'] = $postData['user_name'];
		}

		if (empty($this->messages['error'])) {
			if (Auth::attempt($loginCredentials)) {
				$this->messages['status'] = $this->successStatus;
				$userData = Auth::user()->toArray();
				$user = array('user_name' => $userData['user_name'], 'email_id' => $userData['email_id'], 'mobile_no' => $userData['mobile_no']);
				$this->messages['data']['Success'] = $user;
				// Change login status
				UserModel::where('id', '=', Auth::id())->update(array('login_status' => 1));
				// Generate access token for particular device & map wiht user id
				$userTocken = $this->mapUserAndDevice(Auth::id(), $deviceId, $postData['type'], $postData['push_token']);
				$this->messages['access_token'] = $userTocken;
			} else {
				$this->messages['error'] = Config::get('constants.errorMessages.user_unauthorized');
				$this->messages['access_token'] = NULL;
			}
		}

		return $this->messages;
	}

	/**
	 * API - send mail to user with new password & update to database
	 * @param array $email
	 * @return array $messages
	 */
	public function forgotPassword($email)
	{
		$rules = array('email_id' => 'required|email');
		$messages = array('email_id.required' => 'parameter_missing', 'email_id.email' => 'invalid_email_format');
		$validation = Validator::make(array('email_id' => $email), $rules, $messages);
		if ($validation->fails()) {
			$emailMessages = $validation->messages();
			if ($emailMessages->has('email_id')) {
				$this->messages['error'] = Config::get('constants.errorMessages.' . $emailMessages->first('email_id'));
			}
		} else {
			$userData = $this->modelObjectUser->getUserByEmail($email);
			if ($userData) {
				if ($userData[0]['registerd_by'] == 'facebook') {
					$this->messages['error'] = Config::get('constants.errorMessages.user_from_social_site');
					return $this->messages;
				}

				// generate password
				$password = $this->generatePassword();
				$this->modelObjectUser->updatePassword($userData[0]['id'], Hash::make($password));
				$view = 'api.user.forgotPassword';
				$dataForView = ['name' => $userData[0]['user_name'], 'password' => $password];
				$emails[] = $userData[0]['email_id'];
				$subject = 'Forgot Password';
				$objMailHelper = new MailHelper();
				$isMailSent = $objMailHelper->sendMail($view, $dataForView, $emails, $subject);
				$this->messages['status'] = $this->successStatus;
			} else {
				$this->messages['error'] = Config::get('constants.errorMessages.user_not_found');
			}
		}

		return $this->messages;
	}

	/**
	 * To map user with device when user logs in
	 * @param int $userId
	 * @param string $deviceId
	 * @param string $deviceType
	 * @param string $pushToken
	 * @return string $accessToken
	 */
	public function mapUserAndDevice($userId, $deviceId, $deviceType, $pushToken)
	{
		$accessToken = $this->generateToken($userId, $deviceId);
		$dateTime = date('Y-m-d H:i:s');
		$data = array('user_id' => $userId, 'device_id' => $deviceId, 'access_token' => $accessToken, 'device_type' => $deviceType,
			'device_push_token' => $pushToken, 'login_time' => $dateTime, 'status' => 1, 'created_at' => $dateTime);
		UsersLogin::where('device_id', '=', $deviceId)->update(array('status' => 0, 'logout_time' => $dateTime));
		UsersLogin::insert($data);

		return $accessToken;
	}

	/**
	 * To generate token while login/logout
	 * @param type $userId
	 * @param type $deviceId
	 * @return string [Token]
	 */
	public function generateToken($userId, $deviceId)
	{
		return base64_encode(base64_encode($deviceId . '--' . Config::get('constants.ACCESS_KEY') . '--' . $userId));
	}

	/**
	 * To validate user name
	 * @param type $userData
	 * @return boolean/string
	 */
	public function validateUserName($userData)
	{
		$rules = array('user_name' => 'required|min:3|max:30|unique:users,user_name');
		$messages = array('user_name.required' => 'parameter_missing', 'user_name.min' => "invalid_user_name",
			'user_name.max' => 'invalid_user_name', 'user_name.unique' => 'name_not_available');
		$validation = Validator::make($userData, $rules, $messages);

		return $validation;
	}

	/**
	 * To validate mobile no
	 * @param type $userData
	 * @return boolean/string
	 */
	public function validateMobileNo($userData)
	{
		$rules = array('mobile_no' => 'required|min:10|max:10|unique:users,mobile_no');
		$messages = array('mobile_no.required' => 'parameter_missing', 'mobile_no.min' => "invalid_mobile_no",
			'mobile_no.max' => 'invalid_mobile_no', 'mobile_no.unique' => 'name_not_available');

		$validation = Validator::make($userData, $rules, $messages);
		return $validation;
	}

	/**
	 * To genrate random string for password
	 * @param void
	 * @return string $randomNumber
	 */
	public function generatePassword()
	{
		$alphaNumericString = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$stringLength = strlen($alphaNumericString) - 1;
		$randomNumber = substr($alphaNumericString, rand(0, $stringLength), 1) .
				substr($alphaNumericString, rand(0, $stringLength), 1) .
				substr($alphaNumericString, rand(0, $stringLength), 1) .
				substr($alphaNumericString, rand(0, $stringLength), 1) .
				substr($alphaNumericString, rand(0, $stringLength), 1) .
				substr($alphaNumericString, rand(0, $stringLength), 1);

		return $randomNumber;
	}

	/**
	 * To logout user
	 * @param int $userId
	 * @param string $deviceId
	 * @return array $this->messages
	 */
	public function logOutUser($userId, $deviceId)
	{
		UserModel::where('id', '=', $userId)->update(array('login_status' => 0));
		$objectModelUsersLogin = new UsersLogin();
		$objectModelUsersLogin->logOutUser($userId, $deviceId);
		$token = $this->generateToken(0, $deviceId);
		$this->messages['status'] = $this->successStatus;
		$this->messages['access_token'] = $token;

		return $this->messages;
	}

	/**
	 * Create social user
	 * 
	 * @param array $data
	 * @param string $deviceId
	 * @param mixed(integer|null) $user
	 */
	private function _createSocialUser($data, $deviceId, $user = NULL)
	{
		$lastInsertId = (empty($user)) ? 0 : $user->id;
		$dbKeys = array('user_name', 'email_id', 'facebook_id');
		if (empty($user)) {
			$saveUserData = array();
			foreach ($dbKeys as $key => $value) {
				if (isset($data[$value])) {
					$saveUserData[$value] = $data[$value];
				}
			}
			$saveUserData['registerd_by'] = $data['login_by'];
			$saveUserData['status'] = 'active';
			$saveUserData['created_at'] = date('Y-m-d H:i:s');
			$lastInsertId = UserModel::insertGetId($saveUserData);
		}

		$token = $this->mapUserAndDevice($lastInsertId, $deviceId, $data['type'], $data['push_token']);
		$this->messages['access_token'] = $token;
		$this->messages['status'] = $this->successStatus;

		$userData = UserModel::select('user_name', 'email_id', 'mobile_no')->where('id', '=', $lastInsertId)->get()->toArray();
		$this->messages['data']['Success'] = $userData;
	}

	/**
	 * To login/register user by social sites
	 * @param array $data
	 * @param string $deviceId
	 * @return array $this->messages
	 */
	public function doSocialLogin($data, $deviceId)
	{
		$data['password'] = 'dummy_password';
		$data['device_id'] = $deviceId;
		$this->validateData($data);
		if (empty($this->messages['error'])) {
			switch ($data['login_by']) {
				case 'facebook' :
					$user = null;
					if (!isset($data['email_id']) && !empty($data['facebook_id'])) {
						return $this->messages['error'] = Config::get('constants.errorMessages.parameter_missing');
					}

					$user = UserModel::where('email_id', '=', $data['email_id'])->where('facebook_id', '=', $data['facebook_id'])->get()->first();
					if ($user && ($user->status == 'pending' || $user->status == 'inactive')) {
						return $this->messages['error'] = Config::get('constants.errorMessages.account_status_pending');
					}

					$this->_createSocialUser($data, $deviceId, $user);
					break;
			}
		}

		return $this->messages;
	}

	/**
	 * Check for unique email
	 * @param type $postedData
	 * @return $this->messages
	 */
	public function isEmailUnique($postedData)
	{
		$validation = $this->emailValidation($postedData);
		if ($validation->fails()) {
			$message = $validation->messages();
			$this->messages['error'] = Config::get('constants.errorMessages.' . $message->first('email'));
		} else {
			$this->messages['status'] = $this->successStatus;
		}

		return $this->messages;
	}

	/**
	 * To validate email
	 * @param type $userData
	 * @return object $validation
	 */
	public function emailValidation($userData)
	{
		$rules = array('email_id' => 'required|unique:users,email_id|email');
		$messages = array('email_id.required' => 'parameter_missing',
			'email_id.unique' => 'email_id_not_available',
			'email_id.email' => 'invalid_email_format');

		$validation = Validator::make($userData, $rules, $messages);

		return $validation;
	}

	/**
	 * Validate parameter values
	 * @param array $data
	 */
	public function validateData($data)
	{
		$rules = array('password' => 'required', 'device_id' => 'required',
			'type' => 'required|in:ios,android', 'push_token' => 'required');
		$messages = array('password.required' => 'parameter_missing', 'device_id.required' => 'parameter_missing',
			'push_token.required' => 'parameter_missing', 'type.required' => 'parameter_missing',
			'type.in' => 'invalid_device');

		$validation = Validator::make($data, $rules, $messages);

		if ($validation->fails()) {
			$message = $validation->messages();
			$error = '';
			if ($message->has('password')) {
				$error = $message->first('password');
			} elseif ($message->has('device_id')) {
				$error = $message->first('device_id');
			} elseif ($message->has('push_token')) {
				$error = $message->first('push_token');
			} elseif ($message->has('type')) {
				$error = $message->first('type');
			}
			$this->messages['error'] = Config::get('constants.errorMessages.' . $error);
		}
	}

}