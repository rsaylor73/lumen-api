<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\SentryService;

class SentryController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        return new JsonResponse(SentryService::getList($request));
    }

    public function view($id): JsonResponse
    {
        return new JsonResponse(SentryService::view($id));
    }

    public function save(Request $request): \Illuminate\Http\JsonResponse
    {
        return SentryService::save($request);
    }

    public function update($id, Request $request): \Illuminate\Http\JsonResponse
    {
        $json = $request->json()->all();
        return SentryService::update($id, $json);
    }

    public function delete($id): \Illuminate\Http\JsonResponse
    {
        return SentryService::delete($id);
    }
}
