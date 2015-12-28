<?php
/**
 * Class : ApiBaseService
 * Purpose : Used to perform some basic operations like setting offset and count, validation for inputdata
 * @example setOffsetAndCount(), validateInputData()
 */

namespace app\services;

use Illuminate\Support\Facades\Config;

class ApiBaseService
{
    protected $messages = array('statusCode' => 200, 'access_token' => NULL);
    protected $successStatus;
    protected $loggedInUserId;
    protected $offset;
    protected $count;

    /**
     * To initialize variables/objects
     */
    public function __construct($userId = NULL)
    {
        $this->messages['status'] = Config::get('constants.ERROR');
        $this->messages['data'] = array();
        $this->messages['error'] = array();
        $this->successStatus = Config::get('constants.SUCCESS');
        // user-id which is taken from access token
        $this->loggedInUserId = $userId;
    }

    /**
     * To set offset and count
     * @param array data
     */
    public function setOffsetAndCount($data)
    {
        if (isset($data['offset']) && !empty($data['count'])) {
            if (is_numeric($data['offset']) && is_numeric($data['count'])) {
                $this->offset = $data['offset'];
                $this->count = $data['count'];
            } else {
                $this->messages['error'] = Config::get('constants.errorMessages.invalid_offset_type');
            }
        } else {
            $this->offset = $this->count = NULL;
        }
    }
    
    /**
     * To validate data coming with request
     * @param array $data
     * @param array $validationRules
     * @return object $validatorObject
     */
    public function validateInputData($data, $validationRules)
    {
        $validationErrorMessages = array('sometimes' => "", 'required' => 'parameter_missing', 'numeric' => 'invalid_parameter_type', 
                                            'min' => 'parameter_missing', 'regex' => 'invalid_parameter_type');
        foreach ($validationRules as $keyToValidate => $rulesToApply) {
            $applyRules = '';
            foreach ($rulesToApply as $value) {
                if (empty($applyRules)) {
                    $applyRules = $value;
                } else {
                    $applyRules .= '|' . $value;
                }
                if (strpos($value, ':') !== false) {
                    $keyArray = explode(':', $value);
                    $messages[$keyToValidate . '.' . $keyArray[0]] = $validationErrorMessages[$keyArray[0]];
                } else {
                    $messages[$keyToValidate . '.' . $value] = $validationErrorMessages[$value];
                }
            }
            $rules[$keyToValidate] = $applyRules;
        }

        $validatorObject = \Validator::make($data, $rules, $messages);

        return $validatorObject;
    }
}