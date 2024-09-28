<?php

namespace App\Service;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Models\SentryDNS;
use App\Service\ApiService;

class SentryService
{
    public static function getList($request): array
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 25);

        return SentryDNS::listSentry($page, $pageSize);
    }

    public static function view($id)
    {
        return SentryDNS::view($id);
    }

    public static function save($request): JsonResponse
    {
        $status = SentryDNS::saveNewSentry($request);
        if ($status === true) {
            return ApiService::sendJsonResponse('The request was saved.', 200 );
        } else {
            return ApiService::sendJsonResponse('The request failed to save.', 400);
        }
    }

    public static function update($id, $json): JsonResponse
    {
        $status = SentryDNS::updateSentry($id, $json);
        if ($status === true) {
            return ApiService::sendJsonResponse('The request was updated.', 200 );
        } else {
            return ApiService::sendJsonResponse('The request failed to update.', 400);
        }
    }

    public static function delete($id): JsonResponse
    {
        $status = SentryDNS::deleteSentry($id);

        if (is_object($status)) {
            return ApiService::sendJsonResponse('The request was deleted.', 200 );
        } else {
            return ApiService::sendJsonResponse('The request failed to delete.', 400);
        }
    }
}
