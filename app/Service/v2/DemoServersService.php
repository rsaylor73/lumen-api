<?php

namespace App\Service\v2;

use App\Models\v2\DeleteDemoServerQueue;
use App\Models\v2\DemoServers;
use App\Models\v2\DemoServerQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Service\ApiService;

class DemoServersService
{
    private static function getConfig(): array
    {
        $newServerFields = ["email"];
        $config['new_server_fields'] = $newServerFields;

        return $config;
    }

    public static function checkContent($content): bool
    {
        if (!empty($content)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $fields
     * @param $content
     * @return bool|JsonResponse
     */
    public static function requiredFields(array $fields, $content)
    {
        $error = array();
        foreach ($fields as $field) {
            if (!isset($content[$field])) {
                $error[] = $field;
            }
        }
        if (!empty($error)) {
            return ApiService::sendJsonResponse('One or more fields are missing', 400, 'fields', $error);
        } else {
            return true;
        }
    }

    public static function newServerQueue($json)
    {
        $config = self::getConfig();
        $requiredFields = $config['new_server_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        /* TO-DO check for duplicate terraform file */
        $dns = "d-" . self::generateRandomString('10');

        $terraform_fileName = $dns . ".tf";
        $terraform_variable_string = self::generateRandomString('30');

        $check = DemoServers::checkDuplicateTerraformFile($terraform_fileName);
        if ($check > 0) {
            return ApiService::sendJsonResponse("The DNS name matches an existing server. Please use a different DNS name and try again.", 400);
        }

        $check = DemoServers::checkDuplicateTerraformVar($terraform_variable_string);
        if ($check > 0) {
            return ApiService::sendJsonResponse("Internal error assigning terraform var.", 400);
        }

        $server = DemoServers::newServer($dns, $json['email'], $terraform_fileName,  $terraform_variable_string);
        DemoServerQueue::newServerQueue($server->id, 'pending');
        return ApiService::sendJsonResponse("The job build was received.", 200);
    }

    public static function deleteServer($id): JsonResponse
    {
        DeleteDemoServerQueue::newDeleteQueue($id, 'pending');
        return ApiService::sendJsonResponse("The job build was received.", 200);
    }

    private static function generateRandomString($length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
