<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($message = [], $result = [])
    {
    	$response = [
            'success' => true,
            'data' => $result
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        // if (!empty($result)) {
        //     $response['data'] = $result;
        // }

        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($message = [], $error = [], $code = 500)
    {
    	$response = [
            'success' => false,
            'message' => $message,
        ];



        if(!empty($error) || count($error) > 1){
            $response['data'] = $error;
        }


        return response()->json($response, $code);
    }
}
