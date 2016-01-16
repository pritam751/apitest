<?php

namespace app\controllers\api;

use app\services\UserService;
use Illuminate\Support\Facades\Input;

class UserController extends ApiBaseController
{

    private $objectUserService = NULL;

    /**
     * Initialize variables, objects
     */
    public function __construct()
    {
            parent::__construct();
            $this->objectUserService = new UserService();
    }

    /**
     * Register new user
     * @return json
     */
    public function userRegistration()
    {
            if (empty($this->responseData['error'])) {
                    $postData = Input::all();
                    $this->responseData = $this->objectUserService->newUser($postData, $this->requestHeaders['Device-Id']);
            }

            return $this->createJsonResponse();
    }

    /**
     * User login
     * @return json
     */
    public function userLogin()
    {
            if (empty($this->responseData['error'])) {
                    $postData = Input::all();
                    if (!empty($postData['user_name'])) {
                            $deviceId = $this->requestHeaders['Device-Id'];
                            $this->responseData = $this->objectUserService->doLogin($postData, $deviceId);
                    } else {
                            $this->responseData['error'] = $this->errorParamMissing;
                    }
            }

            return $this->createJsonResponse();
    }

    /**
     * Check for unique user-name
     * @return json
     */
    public function checkUserName()
    {
            if (empty($this->responseData['error'])) {
                    $postData = Input::all();
                    if (!empty($postData['name'])) {
                            $this->responseData = $this->objectUserService->checkUserName($postData);
                    } else {
                            $this->responseData['error'] = $this->errorParamMissing;
                    }
            }

            return $this->createJsonResponse();
    }

    /**
     * Get user profile
     * @return json
     */
    public function getUser()
    {
            if (empty($this->responseData['error'])) {
                    $requestedUserId = Input::get('user_id');
                    if (isset($this->userId)) {
                            $this->responseData = $this->objectUserService->getUserDetails($this->userId, $requestedUserId);
                    } else {
                            $this->responseData['error'] = $this->errorParamMissing;
                    }
            }

            return $this->createJsonResponse();
    }

    /**
     * Provide new password to user
     * @return json
     */
    public function forgotPassword()
    {

            if (empty($this->responseData['error'])) {
                    $email = Input::get('email_id');
                    if (!empty($email) && $email != '') {
                            $this->responseData = $this->objectUserService->forgotPassword($email);
                    } else {
                            $this->responseData['error'] = $this->errorParamMissing;
                    }
            }

            return $this->createJsonResponse();
    }

    /**
     * Logout user
     * @return json
     */
    public function logOut()
    {
            $this->isTokenExists($this->tokenData, 1, $this);
            if (empty($this->responseData['error'])) {
                    if (!empty($this->requestHeaders['Device-Id'])) {
                            $deviceId = $this->requestHeaders['Device-Id'];
                            $this->responseData = $this->objectUserService->logOutUser($this->userId, $deviceId);
                    } else {
                            $this->responseData['error'] = $this->errorParamMissing;
                    }
            }

            return $this->createJsonResponse();
    }

    /**
     * Social login for users
     * @return json
     */
    public function socialLogin()
    {
            if (empty($this->responseData['error'])) {
                    $postData = Input::all();
                    $isIdSet = !empty($postData['facebook_id']);
                    if (!empty($postData['login_by']) && $isIdSet) {
                            if ($postData['login_by'] == 'facebook') {
                                    $deviceId = $this->requestHeaders['Device-Id'];
                                    $this->responseData = $this->objectUserService->doSocialLogin($postData, $deviceId);
                            } else {
                                    $this->responseData['error'] = config::get('constants.errorMessages.invalid_action');
                            }
                    } else {
                            $this->responseData['error'] = $this->errorParamMissing;
                    }
            }

            return $this->createJsonResponse();
    }

                public function recharge() 
                {
                    if (empty($this->responseData['error'])) {
                            $postData = Input::all();
                            $accessToken = $this->requestHeaders['Access-Token'];
                            $this->responseData = $this->objectUserService->makeUsersRecharge($postData, $this->requestHeaders['Device-Id'],$accessToken);
                }

                    return $this->createJsonResponse();
                }

    /**
     * Check for unique email-id [this function is used while user registration]
     * @return json
     */
    public function isEmailUnique()
    {
            if (empty($this->responseData['error'])) {
                    $postData = Input::all();
                    $this->responseData = $this->objectUserService->isEmailUnique($postData);
            }

            return $this->createJsonResponse();
    }

}