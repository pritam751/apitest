<?php
namespace app\models;

class Devices extends \Eloquent
{
    /** 
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'registered_devices';
    
    /**
     * Register device with push and access token
     * @param string $deviceId
     * @param string $deviceType
     * @param string $accessToken
     * @param string $pushToken
     * @return boolean
     */
    public function registerDevice($deviceId, $deviceType, $accessToken, $pushToken)
    {
        $devices = new Devices();
        $devices->device_identifier = $deviceId;
        $devices->device_type = $deviceType;
        $devices->access_token = $accessToken;
        $devices->device_push_token = $pushToken;
        
        return $devices->save();
    }
    
    /**
     * Check is device already registered
     * @param string $deviceId
     * @return boolean
     */
    public function checkIsDeviceRegistered($deviceId)
    {
        return Devices::where('device_identifier', '=', $deviceId)->exists();
    }
}