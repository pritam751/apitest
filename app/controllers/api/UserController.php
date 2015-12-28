<?php
/**
 * Class : UserController
 * Purpose : To perform all operations regarding user. 
 * @example userRegistration(), userLogin(), searchUsers(), followUnfollowUser(), forgotPassword()
 */

namespace app\controllers\api;

use app\services\UserService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;

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
     * Activate user account
     * @return json
     */
    public function activateUser()
    {
        if (empty($this->responseData['error'])) {
            $postData = Input::all();
            if (!empty($postData['user_id'])) {
                if (is_numeric($postData['user_id'])) {
                    $deviceId = $this->requestHeaders['Device-Id'];
                    $this->responseData = $this->objectUserService->activateUser($postData, $deviceId);
                } else {
                    $this->responseData['error'] = Config::get('constants.errorMessages.invalid_user_id');
                }
            } else {
                $this->responseData['error'] = $this->errorParamMissing;
            }
        }

        return $this->createJsonResponse();
    }

    /**
     * Search for users
     * @return json
     */
    public function searchUsers()
    {
        $action = trim(Input::get('action'));
        if (!empty($action)) {
            $this->isTokenExists($this->tokenData, 1, $this);
        }
        if (empty($this->responseData['error'])) {
            $searchString = Input::get('name');
            $offset = trim(Input::get('offset'));
            $count = trim(Input::get('count'));
            $action = trim(Input::get('action'));
            if ((isset($offset) && !empty($count))) {
                if (is_numeric($offset) && is_numeric($count)) {
                    $this->responseData = $this->objectUserService->getUsers($this->userId, $offset, $count, $searchString, $action);
                } else {
                    $this->responseData['error'] = Config::get('constants.errorMessages.invalid_parameter_type');
                }
            } else {
                $this->responseData['error'] = $this->errorParamMissing;
            }
        }

        return $this->createJsonResponse();
    }

    /**
     * Follow or unfollow user
     * @return json
     */
    public function followUnfollowUser()
    {
        $this->isTokenExists($this->tokenData, 1, $this);
        
        if (empty($this->responseData['error'])) {
            $postData = Input::all();
            if (!empty($postData['action']) && isset($this->userId) && !empty($postData['user_id'])) {
                if (in_array($postData['action'], array('follow', 'unfollow'))) {
                    if (is_numeric($postData['user_id'])) {
                        $this->responseData = $this->objectUserService->followUnfollowUser($this->userId, $postData);
                    } else {
                        $this->responseData['error'] = config::get('constants.errorMessages.invalid_parameter_type');
                    }
                } else {
                    $this->responseData['error'] = config::get('constants.errorMessages.invalid_action');
                }
            } else {
                $this->responseData['error'] = $this->errorParamMissing;
            }
        }

        return $this->createJsonResponse();
    }

    /**
     * Update user profile
     * @return json
     */
    public function updateUserProfile()
    {
        $this->isTokenExists($this->tokenData, 1, $this);

        if (empty($this->responseData['error'])) {
            $postData = Input::all();
            if (!empty($this->userId) && isset($postData['action'])) {
                if (in_array($postData['action'], array('profile', 'change_password'))) {
                    $this->responseData = $this->objectUserService->updateUserProfile($postData, $this->userId);
                } else {
                    $this->responseData['error'] = config::get('constants.errorMessages.invalid_action');
                }
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
            $name = Input::get('user_name');
            if (!empty($name) && $name != '') {
                $this->responseData = $this->objectUserService->forgotPassword($name);
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