<?php
/**
 * Class : DeviceService
 * Purpose : Used to register devices when the first time app launches
 * [contains business logic for constructing response regarding to api].
 * @example registerDevice()
 */

namespace app\services;

use app\models\Devices;
use Illuminate\Support\Facades\Config;

class DeviceService
{
    private $objectModelDevice;
    
    /**
     * To initialize variables/objects
     */
    public function __construct() 
    {
        $this->objectModelDevice = new Devices();
    }
    
    /**
     * To register device
     * @param string $deviceId
     * @param string $deviceType
     * @param string $pushToken
     * @return array $messages
     */
    public function registerDevice($deviceId, $deviceType, $pushToken)
    {   
        $accessToken = $this->generateToken($deviceId);
        $messages = array('statusCode' => 200, 'data' => array(), 'error' => array());        
        $isExists = $this->objectModelDevice->checkIsDeviceRegistered($deviceId);
        if ($isExists) {
            $messages['status'] = config::get('constants.SUCCESS');
            $messages['data']['access_token'] = $accessToken;
            
            return $messages;
        }
        
        $result = $this->objectModelDevice->registerDevice($deviceId, $deviceType, $accessToken, $pushToken);
        if ($result) {
            $messages['status'] = config::get('constants.SUCCESS');
            $messages['data']['access_token'] = $accessToken;
        } else {
            $messages['status'] = config::get('constants.ERROR');
            $messages['error'] = config::get('constants.errorMessages.device_not_registered');
        }
        
        return $messages;
    }
    
    /**
     * Generate token
     * @param string $deviceId
     * @return string
     */
    public function generateToken($deviceId)
    {
        return base64_encode(base64_encode($deviceId . '--' . Config::get('constants.ACCESS_KEY') . '--' . 0));
    }
}