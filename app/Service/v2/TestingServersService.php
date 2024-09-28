<?php

namespace App\Service\v2;

use App\Models\v2\ServerQueue;
use App\Models\v2\DeleteServerQueue;
use App\Models\v2\TestingServers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Service\ApiService;

class TestingServersService
{
    private static function getConfig(): array
    {
        $newServerFields = ["ticket", "dns", "email", "description"];
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

    public static function listServers($request): array
    {
        $status = $request->input('status');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 25);
        $searchTerm = $request->input('search_term');
        $orderBy = $request->input('order_by');
        $sortOrder = $request->input('sort_order');
        $emailFilter = $request->input('email_filter');
        $dateStartFilter = $request->input('date_start');
        $dateEndFilter = $request->input('date_end');
        $textSearch = $request->input('text_search');

        return TestingServers::getServers($status, $page, $pageSize, $searchTerm, $emailFilter, $orderBy, $sortOrder, $dateStartFilter, $dateEndFilter, $textSearch);
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
        $terraform_fileName = $json['dns'] . ".tf";
        $terraform_variable_string = self::generateRandomString('30');

        $query = TestingServers::checkDuplicateTerraformFile($terraform_fileName);
        $check = false;
        foreach ($query as $c) {
            $check = true;
        }
        if ($check === true) {
            return ApiService::sendJsonResponse("The DNS name matches an existing server. Please use a different DNS name and try again.", 400);
        }

        $check = TestingServers::checkDuplicateTerraformVar($terraform_variable_string);
        if ($check > 0) {
            return ApiService::sendJsonResponse("Internal error assigning terraform var.", 400);
        }

        // Validate URL
        if(filter_var($json['dns'], FILTER_VALIDATE_URL)) {
            return ApiService::sendJsonResponse("DNS {$json['dns']} must not be a URL.", 400);
        }

        // Validate alphanumeric
        if (!preg_match('/^[a-zA-Z0-9-]+$/', $json['dns'])) {
            return ApiService::sendJsonResponse("DNS {$json['dns']} contains non alphanumeric characters.", 400);
        }

        if (self::checkReservedDnsNames($json['dns']) === true) {
            return ApiService::sendJsonResponse("{$json['dns']} is a reserved name and can not be used.", 400);
        }

        $server = TestingServers::newServer($json['ticket'], $json['dns'], $json['email'], $terraform_fileName,  $terraform_variable_string, $json['description']);
        ServerQueue::newServerQueue($server->id, 'pending');
        return ApiService::sendJsonResponse("The job build was received.", 200);
    }

    private static function checkReservedDnsNames($name): bool
    {
        $domains = ["api-build-server", "demo-server", "dev-server"];

        if (in_array($name, $domains)) {
            return true;
        }

        return false;
    }

    public static function deleteServer($id): JsonResponse
    {
        DeleteServerQueue::newDeleteQueue($id, 'pending');

        $qId = ServerQueue::getServerQueueId($id);
        ServerQueue::updateStatus($qId[0]->id, 'deleting');

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
