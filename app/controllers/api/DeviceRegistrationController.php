<?php
/**
 * Class : DeviceRegistrationController
 * Purpose : Used to register devices when the first time app launches.
 * @example registerDevice()
 */

namespace app\controllers\api;

use app\services\DeviceService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use app\helpers\ResponseHelper;

class DeviceRegistrationController extends \BaseController
{
    private $objectDeviceService;
    
    /**
     * Initialize valiables/objects
     */
    public function __construct() 
    {
        $this->objectDeviceService = new DeviceService();
    }
    
    /**
     * Register devices when app launches
     * @return json
     */
    public function registerDevice()
    {
        $deviceType = Input::get('type');
        $pushToken = trim(Input::get('push_token'));
        $headers = getallheaders();                
        if (!empty($headers['Device-Id']) && !empty($deviceType) && !empty($pushToken)) {
            $deviceTypesAvailable = Config::get('constants.deviceTypes');
            if (in_array($deviceType, $deviceTypesAvailable)) {
                $data = $this->objectDeviceService->registerDevice($headers['Device-Id'], $deviceType, $pushToken);
                return ResponseHelper::jsonResponse($data['status'], $data['statusCode'], $data['data'], $data['error']);
            } else {
                return ResponseHelper::jsonResponse(config::get('constants.ERROR'), 200, NULL, Config::get('constants.errorMessages.invalid_device'));
            }
        } else {
            return ResponseHelper::jsonResponse(config::get('constants.ERROR'), 200, NULL, Config::get('constants.errorMessages.parameter_missing'));
        }
    }
}