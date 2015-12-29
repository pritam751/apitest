<?php

namespace app\controllers\api;

use Illuminate\Support\Facades\Config;
use app\models\UserModel;
use app\helpers\ResponseHelper;

class ApiBaseController extends \BaseController 
{   
    public $userId = NULL;
    public $responseData = array();    
    public $tokenData;
    
    /**
     * Initialize variables/objects.
     */
    public function __construct() 
    {
        $this->requestHeaders = getallheaders();

        $this->responseData['status'] = config::get('constants.ERROR');
        $this->responseData['statusCode'] = config::get('constants.statusCodes.OK');
        $this->responseData['data'] = NULL;
        $this->responseData['error'] = NULL;
        $this->responseData['access_token'] = NULL;
        
        $this->errorParamMissing = Config::get('constants.errorMessages.parameter_missing');
        $this->errorTokenMissing = Config::get('constants.errorMessages.invalid_token');

        $this->tokenData = self::validateRequestToken();        
        $this->isTokenExists($this->tokenData, 0, $this);
    }

    /**
     * Check whether token exits or not in api call
     * @param array $tokenData
     * @param int $checkIsAuthorized
     * @param object $object
     */
    public function isTokenExists($tokenData, $checkIsAuthorized=NULL, &$object)
    {
        if (isset($tokenData['userId'])) {
            $object->userId = $tokenData['userId'];
        }
		
        if (empty($checkIsAuthorized)) {
            if (!$tokenData['isAccessTokenExists']) {
                $object->responseData['error'] = $this->errorParamMissing;
            } elseif (!$tokenData['isAccessTokenValid']) {
                $object->responseData['error'] = $this->errorTokenMissing;
            }
        } else {
            if (!$tokenData['isAccessTokenExists']) {
                $object->responseData['error'] = $this->errorParamMissing;
            } elseif (!$tokenData['isAccessTokenValid'] || $object->userId == 0) {
                $object->responseData['error'] = $this->errorTokenMissing;
            }
        }
    }
    
    /**
     * To validate token of api request
     * @return array $data
     */
    public static function validateRequestToken()
    {
        $requestHeaders = getallheaders();
        $data = array('isAccessTokenValid' => TRUE, 'isAccessTokenExists' => TRUE);
        if (isset($requestHeaders['Access-Token'])) {
            $decyptedKey = base64_decode(base64_decode($requestHeaders['Access-Token'], FALSE), FALSE);
            $keys = explode('--', $decyptedKey);
            if (isset($keys[1]) && isset($keys[2]) && is_numeric($keys[2])) {
                // To check is user exists or not in database
                $isUserExists = $keys[2] != 0 ? UserModel::find($keys[2]) : 1;
                if (Config::get('constants.ACCESS_KEY') != $keys[1] || empty($isUserExists)) {
                    $data['isAccessTokenValid'] = FALSE;
                }
                $data['userId'] = $keys[2];
            } else {
                $data['isAccessTokenValid'] = FALSE;
            }
        } else {
            $data['isAccessTokenExists'] = FALSE;
        }

        return $data;
    }
    
    /**
     * To create and return json response for all apis
     * @return json
     */
    public function createJsonResponse()
    {
        return ResponseHelper::jsonResponse($this->responseData['status'], 
                                            $this->responseData['statusCode'], 
                                            $this->responseData['data'], 
                                            $this->responseData['error'],
                                            $this->responseData['access_token']);
    }
}