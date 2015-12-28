<?php
/**
 * Class : CommonService
 * Purpose : Contains the functionalities which are needed multiple places for API's only.
 * @example generateVideoThumbnail(), convertAudioOrVideo(), sendPush()
 */
namespace app\services;

use Illuminate\Support\Facades\Config;
use App\Helpers\FileHelper;
use app\models\UsersLogin;
use App\Helpers\PushMessageHelper;
use app\models\UserModel;
use app\models\UserFollowerFan;
use \app\models\UserScreenVisitedLog;
use Illuminate\Support\Facades\Log;

class CommonService
{
    const KICKOFF = 1;
    const GOAL = 0;
    const HALFTIME = 3;
    const ENDGAME = 2;

    /**
     * To initialize variables and objects
     */
    public function __construct()
    {
        $this->errorParamMissing = Config::get('constants.errorMessages.parameter_missing');
        $this->errorTokenMissing = Config::get('constants.errorMessages.invalid_token');
    }
    
    /**
     * To get video rotation
     * @param string $videoPath
     * @return array $rotation
     */
    public static function getVideoRotation($videoPath)
    {
        $output = shell_exec("exiftool $videoPath | grep Rotation");
        $rotation = explode(":", $output);

        return $rotation;
    }

    /**
     * Generate thumb image from video file
     * @param string $videoPath
     * @param string $videoName
     * @param string $thumbPath
     * @reutrn string $imageName
     */
    public static function generateVideoThumbnail($videoPath, $videoName, $thumbPath)
    {
	//video dir
	$video = $videoPath . $videoName;
        $name = pathinfo($video, PATHINFO_FILENAME);
        $imageName = 'thumb_' . Config::get('constants.VIDEO_THUMBNAIL_SIZE') . '_' . $name . '.jpeg';
	// path to save the image
	$image = $thumbPath . $imageName;
        $rotation = self::getVideoRotation($video);
        $tempVodeo = $videoPath . 'test_rotated.mp4';
        if (!empty($rotation) && !empty($rotation[1]) && trim($rotation[1]) == 90) {
            shell_exec("avconv -i $video -vf   transpose=1  -strict experimental $tempVodeo");
            $cmd = "avconv -i $tempVodeo -r 1 -vf scale=iw/1:ih/1 -f image2 $image";
        } else {
            $cmd = "avconv -i $video -r 1 -vf scale=iw/1:ih/1 -f image2 $image";
        }

        shell_exec($cmd);
        @unlink($tempVodeo);        

        return $imageName;
    }

    /**
     * Applying video compressing. For compression of video avocnv extension
     * is used. For using this extension we have to install libavcodec-extra-53
     * extension too.
     *
     * For ubuntu servers we can installed by using the followin command.
     * sudo apt-get install libavcodec-extra-53
     *
     * @param string $sourceFile
     * @param string $destination Then destination path with out file name.
     * @param string $fileName The appropriate file name that we want to use for saving.
     * @param string $extension
     */
    public static function compressVideo($sourceFile,$destination,$fileName,$extension = ".mp4")
    {
        shell_exec('avconv -i ' . $sourceFile . ' -vcodec libx264 -acodec libmp3lame -ac 2 ' . public_path($destination) . $fileName . $extension);
    }

    /**
     * To generate thumbnail from original image
     * @param string $sourceFileName
     * @param string $sourceFilePath
     * @param string $destinationPath
     */
    public static function resizeImage($sourceFileName, $sourceFilePath, $destinationPath)
    {
        $fileHelper = new FileHelper();
        $fileHelper->sourceFilename = $sourceFileName;
        $fileHelper->sourceFilepath = $sourceFilePath;
        $fileHelper->destinationPath = $destinationPath;
        $fileHelper->resizeImage('ticker');
    }

    /**
     * To convert audio(.caf to .aac) or video(.mov to .mp4)
     * @param string $path
     * @param string $fileName
     * @param string $fileType
     * @return string $name
     */
    public static function convertAudioOrVideo($path, $fileName, $fileType)
    {
        $fileFullPath = $path . $fileName;
        if ($fileType == 'video') {
            $name = time() . '.mp4';
            $newFilePath = $path . $name;
            $rotation = self::getVideoRotation($fileFullPath);
            if (!empty($rotation) && !empty($rotation[1]) && trim($rotation[1]) == 90) {
                $cmd = "avconv -i $fileFullPath -c:v libx264 -c:a copy -vf transpose=1 $newFilePath";
            } else {
                $cmd = "avconv -i $fileFullPath -c:v libx264 -c:a copy $newFilePath";
            }
        } else {
            $name = time() . '.aac';
            $newFilePath = $path . $name;
            $cmd = "avconv -i $fileFullPath -acodec aac -ac 2 -ab 64k -ar 48000 -strict experimental $newFilePath";
        }

        shell_exec($cmd);
        @unlink($fileFullPath);

        return $name;
    }

    /**
     * Upload File
     * @param int $userId
     * @param string $type
     * @param string $postFileName
     * @param int $tickerId
     * @return $data
     */
    public function uplodFile($userId, $type, $postFileName, $tickerId=NULL)
    {
        $data = array('errorMessage' => '', 'fileName' => '', 'fileThumbName' => '');
        // Check for file size
        if ($_FILES[$postFileName]["size"] > Config::get('constants.MAX_UPLOAD_SIZE')) {
            $data['errorMessage'] = 'file_size_exceeded';

            return $data;
        }

        $dir = $userId;

        // Paths
        $path = public_path() . "/uploads/";

        $fileType = explode("/", $_FILES[$postFileName]['type']);
        $getExtension = explode(".", $_FILES[$postFileName]["name"]);
        $fileExtension = strtolower(end($getExtension));

        // To check path already exists[if not then create]
        $this->checkIsPahtExist($path, $userId, $fileType[0], $tickerId);

        // To set path by type of file
        switch ($fileType[0]) {
            case 'image' :
                $data['errorMessage'] = $this->isFileTypeAllowed($fileExtension, Config::get('constants.imageTypesAllowed'));
                $path .= $dir . '/image/';
                break;
            case 'audio' :
                $data['errorMessage'] = $this->isFileTypeAllowed($fileExtension, Config::get('constants.audioTypesAllowed'));
                $path .= $dir . '/audio/';
                break;
            case 'video' :
                $data['errorMessage'] = $this->isFileTypeAllowed($fileExtension, Config::get('constants.videoTypesAllowed'));
                $path .= $dir . '/video/';
        }

        if ($data['errorMessage'] != '') {
            return $data;
        }

        // To set path for ticker files
        if (isset($tickerId) && $tickerId != NULL) {
            $path .= $tickerId . "/";
        }

        $fileName = time() . '.' . $fileExtension;
        $name = $_FILES[$postFileName]['name'];
        $result = move_uploaded_file($_FILES[$postFileName]['tmp_name'] , $path . $fileName);

        if ($result) {

            if ($fileType[0] == 'video') {
                if ($fileExtension == "mov") {
                    // convert to mp4 video
                    $fileName = self::convertAudioOrVideo($path, $fileName, $fileType[0]);
                }
                // To generate thumbnail from video
                $thumbImageName = self::generateVideoThumbnail($path, $fileName, $path);
            } elseif ($fileType[0] == 'image') {
                // To generate thumbnail of image
                $path = '/uploads/' . $dir . '/image/' . $tickerId . "/";
                self::resizeImage($fileName, $path, $path);
            } elseif ($fileType[0] == 'audio') {
                // Default file for audio
                $thumbImageName = Config::get('constants.AUDIO_IMAGE_NAME');
                if ($fileExtension == "caf") {
                    // convert to aac/mp3 video
                    $fileName = self::convertAudioOrVideo($path, $fileName, $fileType[0]);
                }
            }

            $data['displayName'] = $name;
            $data['fileName'] = $fileName;
            $data['mediaType'] = $fileType[0];
        } else {
            $data['errorMessage'] = 'upload_failed';
        }

        return $data;
    }

    /**
     * Check file type is allowed or not
     * @param string $fileExtension
     * @param array $allowedTypes
     * @return string $allowedTypes
     */
    public function isFileTypeAllowed($fileExtension, $allowedTypes)
    {
        $errorMessage = '';
        if (!in_array($fileExtension, $allowedTypes)) {
            $errorMessage = 'invalid_file_type';
        }

        return $errorMessage;
    }

    /**
     * Generate path if path does not exists
     * @param int $path
     * @param int $dir
     * @param string $fileType
     * @param string $subDir
     */
    public function checkIsPahtExist($path, $dir, $fileType, $subDir=NULL)
    {
        if (!file_exists($path . $dir)) {
            mkdir($path . $dir , 0777, TRUE);
            mkdir($path . $dir . "/audio" , 0777, TRUE);
            mkdir($path . $dir . "/video" , 0777, TRUE);
            mkdir($path . $dir . "/image" , 0777, TRUE);
            chmod($path . $dir , 0777);
            chmod($path . $dir . "/audio" , 0777);
            chmod($path . $dir . "/video" , 0777);
            chmod($path . $dir . "/image" , 0777);
        }

        if (isset($subDir) && $subDir != NULL) {
            if (!file_exists($path . $dir . "/" . $fileType . "/" . $subDir)) {
                mkdir($path . $dir .  "/" . $fileType . "/" . $subDir , 0777, TRUE);
                chmod($path . $dir .  "/" . $fileType . "/" . $subDir , 0777);
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
     * Get push-tokens of app logged in users
     * @param array $pushReceivers
     * @return array $pushTokens
     */
    private static function _getTokensOfLoggedInUsers($pushReceivers)
    {
        $pushTokens = array();
        foreach ($pushReceivers as $key => $pushReceiverId) {
            // Get user push token
            $tokenData = UsersLogin::select('device_push_token', 'device_type')->where('user_id', '=', $pushReceiverId)
                    ->where('status', '=', 1)->get()->toArray();
            foreach ($tokenData as $value) {
                $pushTokens[] = $value;
            }
        }

        return $pushTokens;
    }

    /**
     * To send push
     * @param int $pushSenderId
     * @param array $pushReceivers
     * @param string $pushMessage
     * @param array $pushData
     */
    public static function sendPush($pushSenderId, $pushReceivers, $pushMessage, $pushData)
    {
        $pushTokens = self::_getTokensOfLoggedInUsers($pushReceivers);
        $res = NULL;
        $deviceTypeIos = PushMessageHelper::DEVICE_TYPE_IPHONE;
        $deviceTypeAndroid = PushMessageHelper::DEVICE_TYPE_ANDROID;
        $iosPushTokens = array();
        $androidPushTokens = array();
        if (!empty($pushTokens)) {
            foreach ($pushTokens as $value) {
                // Get push tokens
                if ($value['device_type'] == 'ios') {
                    $iosPushTokens[] = $value['device_push_token'];
                } else {
                    $androidPushTokens[] = $value['device_push_token'];
                }
            }
            if (!empty($iosPushTokens)) {
                $res = PushMessageHelper::sendPushMessage($pushMessage, $iosPushTokens, $deviceTypeIos, $pushData);
            }
            if (!empty($androidPushTokens)) {
                $res = PushMessageHelper::sendPushMessage($pushMessage, $androidPushTokens, $deviceTypeAndroid, $pushData);
            }
        }
        Log::info($res);
    }

    /**
     * To get push data regarding push for ticker ticks. While sending the
     * push notification we will send the ticker owner id as well as the ticker
     * invities users id.
     *
     * @param string $pushMessage
     * @param object $ticker
     * @param object $user
     * @param boolean $showScore
     * @return array $pushData
     */
    public static function getPushData($pushMessage, $ticker, $user=NULL, $showScore=TRUE)
    {
        $matchDetails = $ticker->compititions;
        if ($showScore) {
            $match = $matchDetails->home_team . " (" . $ticker->home_team_goals . ") : (" .
                        $ticker->guest_team_goals . ") " . $matchDetails->guest_team;
        } else {
            $match = $matchDetails->home_team . " : " . $matchDetails->guest_team;
        }
        $userName = '';
        $tickerInvitedUsers = $ticker->invities()->select('invited_user_id')->where('status', '=', \DB::Raw(1))->get()->toArray();
        $tickerUsers = array_pluck($tickerInvitedUsers, 'invited_user_id');
        $tickerUsers[] = $ticker->user_id;
        if (!empty($user)) {
            $userName = strtoupper($user->name);
        }
        $pushData = array('notification_type' => $pushMessage,
                        'user_name' => $userName,
                        'ticker_id' => $ticker->id,
                        'ticker_title' => strtoupper($ticker->precoverage_title),
                        'match' => strtoupper($match),
                        'user_ids' => $tickerUsers
            );

        return $pushData;
    }

    /**
     * Get users to send push of particular type
     * @param int $userId
     * @param int $tickerId
     * @param string $pushType
     * @return array $pushReceivers
     */
    public static function getUsersToSendPush($userId, $tickerId, $pushType)
    {
        $userObject = UserModel::find($userId);
        $pushReceivers = array();
        switch ($pushType) {
            case Config::get('constants.GOAL') :
                // ticker owner/co-owner + push subscribers + fans
                $fans = $userObject->fans->toArray();
                if (!empty($fans)) {
                    $fans = array_pluck($fans, 'fan_id');
                }
                $fans = CommonService::getTickerOwnerOrJoinee($userId, $tickerId, $fans);
                $fansAndOwnserOrJoinee = CommonService::checkUserSettingsForGoal($userId, $fans);
                $subscribedUsers = CommonService::getPushSubscribedUsers($userObject, self::GOAL);
                $mergedUserIds = array_merge($fansAndOwnserOrJoinee, $subscribedUsers);
                $pushReceivers = array_unique($mergedUserIds);
                break;
            case Config::get('constants.KICK_OFF') :
                // ticker owner/co-owner + push subscribers
                $pushSubscribedUsers = CommonService::getPushSubscribedUsers($userObject, self::KICKOFF);
                $pushReceivers = CommonService::getTickerOwnerOrJoinee($userId, $tickerId, $pushSubscribedUsers);
                break;
            case Config::get('constants.HALF_TIME') :
                $pushSubscribedUsers = CommonService::getPushSubscribedUsers($userObject, self::HALFTIME);
                $pushReceivers = CommonService::getTickerOwnerOrJoinee($userId, $tickerId, $pushSubscribedUsers);
                break;
                // ticker owner/co-owner + push subscribers
            case Config::get('constants.END_GAME') :
                // ticker owner/co-owner + push subscribers
                $pushSubscribedUsers = CommonService::getPushSubscribedUsers($userObject, self::ENDGAME);
                $pushReceivers = CommonService::getTickerOwnerOrJoinee($userId, $tickerId, $pushSubscribedUsers);
                break;
            case Config::get('constants.URGENTLY') :
                // ticker owner/co-owner + push subscribers
                $pushReceivers  = $userObject->fans->toArray();
                if (!empty($pushReceivers)) {
                    $pushReceivers = array_pluck($pushReceivers, 'fan_id');
                }
                break;
        }

        return $pushReceivers;
    }

    /**
     * To check push settings of users for invitation
     * @param int $pushSenderId
     * @param array $pushReceiverId
     * @return array $pushReceivers
     */
    public static function checkUserSettingsForInvitation($pushSenderId, $pushReceiverId)
    {
        $userPushSettings = CommonService::getUserPushSettings($pushReceiverId);
        $tickerInvitation = $userPushSettings['ticker_invitation'];
        // Make push receiver id to 0 if he set off in his push settings for invitaion
        if ($tickerInvitation == 0) {
            $pushReceiverId = 0;
        } elseif ($tickerInvitation == 1) {
            // Check is the user is fan of sender
            $isFanOf = UserFollowerFan::where('fan_id', '=', $pushReceiverId)->where('follower_id', '=', $pushSenderId)->exists();
            if (!$isFanOf) {
                $pushReceiverId = 0;
            }
        }

        return $pushReceiverId;
    }

    /**
     * To check push settings of users for goal
     * @param int $pushSenderId
     * @param array $pushReceivers
     * @return array $pushReceivers
     */
    public static function checkUserSettingsForGoal($pushSenderId, $pushReceivers)
    {
        foreach ($pushReceivers as $key => $pushReceiverId) {
            $userPushSettings = CommonService::getUserPushSettings($pushReceiverId);
            $goal = $userPushSettings['fan_posted_goal'];
            if (!$goal) {
                unset($pushReceivers[$key]);
            }
        }

        return $pushReceivers;
    }

    /**
     * Get users by checking the push settings for before ticker start
     * @param int $pushSenderId
     * @param array $pushReceivers
     * @return array $pushReceivers
     */
    public static function checkUserSettingsForBeforeTickerStart($pushSenderId, $pushReceivers)
    {
        foreach ($pushReceivers as $key => $pushReceiverId) {
            $userPushSettings = CommonService::getUserPushSettings($pushReceiverId);
            $tickerStart = $userPushSettings['own_ticker_start'];
            if (!$tickerStart) {
                unset($pushReceivers[$key]);
            }
        }

        return $pushReceivers;
    }

    /**
     * To get push settings of particular user
     * @param int $pushReceiverId
     * @return array $userPushSettings
     */
    public static function getUserPushSettings($pushReceiverId)
    {
        $userPushSettings = Config::get('constants.DEFAULT_PUSH_SETTINGS');
        $user = UserModel::find($pushReceiverId);// To whom the push will go
        $userSettings = $user->pushSettings->first();
        if (!empty($userSettings)) {
            $userPushSettings = unserialize($userSettings['push_settings']);
        }

        return $userPushSettings;
    }

    /**
     * To get users who have subscribed push for goal from this user
     * @param Object $userObject
     * @param int $typeOfPush
     * @return array $pushSubscribedUsers
     */
    public static function getPushSubscribedUsers($userObject, $typeOfPush)
    {
        $subscribedUsers = $userObject->pushSubscribed->toArray();
        $pushSubscribedUsers = array();
        foreach ($subscribedUsers as $value) {
            $data = explode(',', $value['push_subscribed']);
            if ($data[$typeOfPush] == 1) {
                $pushSubscribedUsers[] = $value;
            }
        }
        if (!empty($pushSubscribedUsers)) {
            $pushSubscribedUsers = array_pluck($pushSubscribedUsers, 'subscriber_id');
        }

        return $pushSubscribedUsers;
    }

    /**
     * To add ticker owener or joinee to push subscribed users
     * @param int $userId
     * @param int $tickerId
     * @param array $pushSubscribers
     * @return array $pushSubscribers
     */
    public static function getTickerOwnerOrJoinee($userId, $tickerId, $pushSubscribers)
    {
        $sql = \app\models\TickerInvities::where('ticker_id', '=', $tickerId)->where('status', '=', \DB::Raw(1));
        $tickerInvityData = $sql->get()->first();
        $id = 0;
        if (!empty($tickerInvityData)) {
            if ($tickerInvityData->requester_id == $userId) {
                $id = $tickerInvityData->invited_user_id;
            } else {
                $id = $tickerInvityData->requester_id;
            }
            $pushSubscribers[] = $id;
        }

        $pushSubscribers = array_unique($pushSubscribers);

        return $pushSubscribers;
    }

    /**
     * Log user screens visited
     *
     * @param String $routeVisited
     * @param array $inputParams
     */
    public function logUserScreensVisited($routeVisited, $inputParams) {
        $userData = self::validateRequestToken();
        $userId = isset($userData['userId']) ? $userData['userId'] : 0;
        // save user screen visited log
        if (!empty($userId)) {
            $screenVisitedData = self::getScreenVisited($routeVisited, $inputParams);
            if (!empty($screenVisitedData['screen_visited'])) {
                $userScreenVisitedLog = new UserScreenVisitedLog();

                $userScreenVisitedLog->user_id = $userId;
                $userScreenVisitedLog->screen_visited = $screenVisitedData['screen_visited'];
                $userScreenVisitedLog->entity_id = $screenVisitedData['entity_id'];

                $userScreenVisitedLog->save();
            }
        }
    }

    /**
     * To get user screens visited
     *
     * @param String $routeVisited
     * @param array $inputParams
     */
    public static function getScreenVisited($routeVisited, $inputParams)
    {
        $screenVisitedData = array('entity_id' => 0);
        switch ($routeVisited) {
            case 'home':
                $screenVisitedData['screen_visited'] = "home";
                break;
            case 'create':
                $screenVisitedData['screen_visited'] = "create_ticker";
                break;
            case 'live':
                $screenVisitedData['screen_visited'] = "live_matches";
                break;
            case 'update/ticker':
                $screenVisitedData['screen_visited'] = "ticker_precoverage";
                break;
            case 'search/user':
                $screenVisitedData['screen_visited'] = "search_user";
                break;
            case 'search/matches':
                $screenVisitedData['screen_visited'] = "search_matches";
                break;
            case 'activities/fans':
                $screenVisitedData['screen_visited'] = "activities_fans";
                break;
            case 'activities/own':
                $screenVisitedData['screen_visited'] = "activities_own";
                break;
            case 'profile':
                $screenVisitedData['screen_visited'] = "user_profile";
                $screenVisitedData['entity_id'] = (int) isset($inputParams['user_id']) ? $inputParams['user_id'] : 0;
                break;
            case 'tickers':
                $screenVisitedData['screen_visited'] = "live_tickers";
                break;
            case 'update/profile':
                $screenVisitedData['screen_visited'] = "update_profile";
                break;
            case 'settings/get':
                $screenVisitedData['screen_visited'] = "push_setting";
                break;
            case 'fans':
                $screenVisitedData['screen_visited'] = "get_fans";
                break;
            case 'fansown':
                $screenVisitedData['screen_visited'] = "get_fans";
                break;
            case 'subscribe/get':
                $screenVisitedData['screen_visited'] = "push_subscribe";
                break;
            case 'get/webview':
                $screenVisitedData['screen_visited'] = "help";
                break;
            case 'ticker/ticks':
                $screenVisitedData['screen_visited'] = "ticker_ticks";
                $screenVisitedData['entity_id'] = (int) isset($inputParams['ticker_id']) ? $inputParams['ticker_id'] : 0;
                break;
            case 'ticker/comments/get':
                $screenVisitedData['screen_visited'] = "ticker_comments";
                $screenVisitedData['entity_id'] = (int) isset($inputParams['ticker_id']) ? $inputParams['ticker_id'] : 0;
                break;

            default:
                break;
        }

        return $screenVisitedData;
    }
}