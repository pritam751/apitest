<?php

return array(
	'ACCESS_KEY' => 'API-TEST-V1',
	'API' => 'api',
	'VERSION' => 'v1',
	'SUCCESS' => 1,
	'ERROR' => 0,
	'SITE_PATH' => URL::to('/'),
	'statusCodes' => array(
		'OK' => '200',
		'CREATED' => '201'
	),
    'deviceTypes' => array(
        'ios',
        'android'
    ),	
	'errorMessages' => array(
		
        'parameter_missing' => array(
            'errorCode' => 'parameter_missing',
            'errorMessage' => 'Some parameter is missing'
        ),
        'invalid_token' => array(
            'errorCode' => 'invalid_token',
            'errorMessage' => 'Invalid access token'
        ),
        'user_unauthorized' => array(
            'errorCode' => 'user_unauthorized',
            'errorMessage' => 'Invalid username or password'
        ),
        'user_already_exists' => array(
            'errorCode' => 'user_already_exists',
            'errorMessage' => 'User already registered. Please select user name'
        ),
        'email_id_not_available' => array(
            'errorCode' => 'email_id_not_available',
            'errorMessage' => 'E-mail id is already used by another user'
        ),
        'name_not_available' => array(
            'errorCode' => 'name_not_available',
            'errorMessage' => 'User name already used by another user'
        ),
        'user_not_found' => array(
            'errorCode' => 'user_not_found',
            'errorMessage' => 'No such user exists'
        ),
        'invalid_user_name' => array(
            'errorCode' => 'invalid_user_name',
            'errorMessage' => 'User name should contain 3 to 30 characters'
        ),
        'invalid_device' => array(
            'errorCode' => 'invalid_device',
            'errorMessage' => 'Invalid device type'
        ),
        'device_not_registered' => array(
            'errorCode' => 'device_not_registered',
            'errorMessage' => 'Unable to register device'
        ),
        'fail_send_mail' => array(
            'errorCode' => 'fail_send_mail',
            'errorMessage' => 'Unable to send mail.'
        ),
        'account_status_pending' => array(
            'errorCode' => 'account_status_pending',
            'errorMessage' => 'User already rgistered. Please select user name'
        ),
        'invalid_user_id' => array(
            'errorCode' => 'invalid_user_id',
            'errorMessage' => 'User id provided is not of valid type'
        ),
        'user_from_social_site' => array(
            'errorCode' => 'user_from_social_site',
            'errorMessage' => 'User registered from social site'
        ),
        'account_deactivated' => array(
            'errorCode' => 'account_deactivated',
            'errorMessage' => 'User account deactivated by admin'
        ),
        'invalid_parameter_type' => array(
            'errorCode' => 'invalid_parameter_type',
            'errorMessage' => 'Invalid type of parameter'
        ),
        'invalid_user' => array(
            'errorCode' => 'invalid_user',
            'errorMessage' => 'Invalid user to update/delete'
        ),
        'invalid_email_format' => array(
            'errorCode' => 'invalid_email_format',
            'errorMessage' => 'Invalid email format'
        ),
        'invalid_action' => array(
            'errorCode' => 'invalid_action',
            'errorMessage' => 'Invalid action parameter provided'
        ),
        'invalid_parameter_value' => array(
            'errorCode' => 'invalid_parameter_value',
            'errorMessage' => 'Parameter contains invalid value'
        ),
        'user_account_already_exists' => array(
            'errorCode' => 'user_account_already_exists',
            'errorMessage' => 'User already registered by app. Do you want to link up account?'
        ),
        'something_went_wrong_at_server' => array(
            'errorCode' => 'something_went_wrong_at_server',
            'errorMessage' => 'Somthing went wrong at server end'
        ),
	),
);
