<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It's a breeze. Simply tell Laravel the URIs it should respond to
  | and give it the Closure to execute when that URI is requested.
  |
 */

Route::get('/', function() {
    return View::make('hello');
});

/* * ******************** API Routes For User ************************** */
Route::group(array('prefix' => Config::get('constants.API') . '/' . Config::get('constants.VERSION') . '/user/'), function() {
    Route::post('registration', array('uses' => 'app\controllers\api\UserController@userRegistration', 'as' => 'registration'));
    Route::post('authenticate', array('uses' => 'app\controllers\api\UserController@userLogin', 'as' => 'authenticate'));
    Route::get('check/name', array('uses' => 'app\controllers\api\UserController@checkUserName', 'as' => 'check/name'));
    Route::post('forgotpassword', array('uses' => 'app\controllers\api\UserController@forgotPassword', 'as' => 'forgotpassword'));
    Route::post('social/login', array('uses' => 'app\controllers\api\UserController@socialLogin', 'as' => 'social/login'));
    Route::post('recharge', array('uses' => 'app\controllers\api\UserController@recharge', 'as' => 'recharge'));
    Route::post('logout', array('uses' => 'app\controllers\api\UserController@logOut', 'as' => 'logout'));
});

/* * ******************** API Routes For Device registration ************************** */
Route::group(array('prefix' => Config::get('constants.API') . '/' . Config::get('constants.VERSION') . '/device/'), function() {
    Route::post('registration', 'app\controllers\api\DeviceRegistrationController@registerDevice');
});
