<?php
/**
 * Class : ResponseHelper
 * Purpose : Used to create response in json for every api request.
 */

namespace app\helpers;
use Illuminate\Support\Facades\Response;

class ResponseHelper
{
    /**
     * Generate json
     * @param string $status[success/error]
     * @param string $message
     * @param array $data
     * @param integer $statusCode[200,201,...]
     * @return json
     */
    public static function jsonResponse($status, $statusCode, $data = NULL, $error = NULL, $accessToken = NULL)
    {
        $responseData = array('status' => $status);
        if ($accessToken) {
            $responseData['access_token'] = $accessToken;
        }
        if ($data) {            
            $responseData = array_merge($responseData, $data);
        }
        if ($error) {
            $responseData['error'] = $error;
        }

        return Response::json($responseData, $statusCode);
    }
}