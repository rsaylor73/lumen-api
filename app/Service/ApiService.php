<?php

namespace App\Service;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiService
{
    /**
     * @param $message
     * @param $code
     * @param null $optionalLabel
     * @param null $optionalData
     * @return JsonResponse
     */
    public static function sendJsonResponse($message, $code, $optionalLabel = null, $optionalData = null): JsonResponse
    {
        switch ($code) {
            case "200":
                $messageType = "success";
                break;
            default:
                $messageType = "errors";
                break;
        }
        /* add meta */

        if (!is_null($optionalLabel) && !is_null($optionalData)) {

            if ($messageType == "errors") {
                return response()->json([
                    'errors' => $message,
                    'meta' => [
                        $optionalLabel => [
                            $optionalData
                        ]
                    ]
                ], $code);
            } else {
                return response()->json([
                    'meta' => [
                        $messageType => $message,
                        $optionalLabel => [
                            $optionalData
                        ]
                    ]
                ], $code);
            }
        } else {
            if ($messageType == "errors") {
                return response()->json([
                    'errors' => $message,
                ], $code);
            } else {
                return response()->json([
                    'meta' => [
                        $messageType => $message
                    ],
                ], $code);
            }
        }
    }


}
