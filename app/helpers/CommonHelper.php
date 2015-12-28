<?php

/**
  |@author Alankar More<a.more@easternenterprise.com>
  |
  |24 June 2015
  |--------------------------------------------------------------------------
  | Helper with all common functions
  |--------------------------------------------------------------------------
 */

namespace App\Helpers;

use File;
use Auth;
use Redirect;
use app\models\Match;
use app\models\UserModel;
use app\models\SearchTags;
use app\models\UsersLogin;

class CommonHelper
{

    /**
     * All match statuses during which tags are not allowed while posting ticks.
     * 
     * @var array 
     */
    private static $matchStatuses = array(
        Match::MATCH_STATUS_ABORTED,
        Match::MATCH_STATUS_CANCELLED,
        Match::MATCH_STATUS_NOT_STARTED,
        Match::MATCH_STATUS_RESCHEDULED,
        Match::MATCH_STATUS_HALF_TIME_BREAK,
        Match::MATCH_STATUS_ADDITIONAL_TIME,
        Match::MATCH_STATUS_OVERTIME_BREAK,
        Match::MATCH_STATUS_END
    );

    /**
     * Incrementing app users profile visits count by user id.
     * The return value of this function is the actual visits count of that 
     * respective profile.
     * 
     * @param integer $userId
     * @return integer 
     */
    public static function incrementProfileVisits($userId)
    {
        $visits = 0;
        if (!empty($userId)) {
            $user = UserModel::find($userId);
            if (!empty($user)) {
                $visits = $user->app_user_profile_visits + 1;
                $user->app_user_profile_visits = $visits;
                $user->save();
            }
        }

        return $visits;
    }

    /**
     * Checking is search tag is exists.
     * 
     * @param string $searchTag
     * @return app/models/SearchTags
     */
    protected static function isSearchTagExists($searchTag)
    {
        $searchTagObj = new SearchTags();

        return $searchTagObj->where('tag_name', '=', $searchTag)->first();
    }

    /**
     * Adding new search tag to the database or incrementing the search tags count by 1.
     * 
     * @param string $searchTag
     * @return integer
     */
    public static function addSearchTagOrIncrementCount($searchTag)
    {
        $count = 0;
        if (!empty($searchTag)) {
            $tagName = strtolower(strip_tags(trim($searchTag)));
            $searchTagRecord = self::isSearchTagExists($tagName);
            if (empty($searchTagRecord->id)) {
                $searchTagObj = new SearchTags();
                $searchTagObj->tag_name = $tagName;
                $searchTagObj->searched_count = 1;
                $searchTagObj->save();

                return $searchTagObj->searched_count;
            }

            // if the search tag already exists then increments its searched count.
            $count = $searchTagRecord->searched_count + 1;
            $searchTagRecord->searched_count = $count;
            $searchTagRecord->save();
        }

        return $count;
    }

    /**
     * Checking is the users login status according to the user id 
     * 
     * @param integer $userId
     * @return string
     */
    public static function checkUsersLoginStatus($userId)
    {
        if (!empty($userId)) {
            $userLoginStatus = UsersLogin::USER_VERY_ACTIVE;
            $currentDate = (new \DateTime());
            $past7daysDate = (new \DateTime())->sub(new \DateInterval('P7D'));
            $past14daysDate = (new \DateTime())->sub(new \DateInterval('P14D'));
            $past30daysDate = (new \DateTime())->sub(new \DateInterval('P30D'));

            $query = UsersLogin::where('user_id', '=', $userId);
            $_7daysRecord = $query->whereBetween('login_time', array($past7daysDate->format('Y-m-d'), $currentDate->format('Y-m-d')))
                    ->get();
            if (!$_7daysRecord->count()) {
                $_14daysRecord = $query->whereBetween('login_time', array($past14daysDate->format('Y-m-d'), $past7daysDate->format('Y-m-d')))
                        ->get();
                if (!$_14daysRecord->count()) {
                    $_30daysRecord = $query->whereBetween('login_time', array($past30daysDate->format('Y-m-d'), $past14daysDate->format('Y-m-d')))
                            ->get();
                    return (!$_30daysRecord->count()) ? UsersLogin::USER_IN_ACTIVE : UsersLogin::USER_LITTLE_ACTIVE;
                }

                $userLoginStatus = UsersLogin::USER_ACTIVE;
            }

            return $userLoginStatus;
        }
    }

    public static function redirectUser()
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            return Redirect::route('backend.listusers');
        } elseif (Auth::user() && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('support'))) {
            return Redirect::route('backend.support-mail-list', ['trashStatus' => 'inbox']);
        }
    }

    /**
     * Checking is the tags are allowed during posting the ticks.
     * 
     * @param String $matchStatus
     */
    public static function tagsAreNotAllowed($matchStatus)
    {
        if (in_array($matchStatus, self::$matchStatuses)) {
            return true;
        }

        return false;
    }

    /**
     * Showing encrypted data for the image
     * 
     * @param string $image
     * @param string $mime
     * @return string
     */
    public static function getDataURI($image, $mime = '')
    {
        if (file_exists($image)) {
            return 'data: ' . (function_exists('mime_content_type') ? mime_content_type($image) : $mime) . ';base64,' . base64_encode(file_get_contents($image));
        }
    }

    /**
     * 
     * @param string $dateString
     * @param string $timeZone
     * @return string 
     */
    public static function dateToUTCFromTimezone($dateString, $timeZone = 'Europe/Berlin')
    {
        date_default_timezone_set($timeZone);
        $dateEu = new \DateTime($dateString);
        $dateEu->setTimezone(new \DateTimeZone('UTC'));

        return $dateEu->format('Y-m-d H:i:s');
    }

    /**
     * Get mime type of respective media file.
     * 
     * @param string $mediaPath
     * @return string
     */
    public static function getMimeTypeOfMedia($mediaPath)
    {
        if (File::exists($mediaPath)) {
            $file = finfo_open(FILEINFO_MIME_TYPE);
            $fileInfo = finfo_file($file, $mediaPath);
            finfo_close($file);

            return $fileInfo;
        }
        
        return false;
    }

}