<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\v2\TestingServersService;

class TestingServerController extends Controller
{
    public function newServer(Request $request)
    {
        $json = $request->json()->all();
        return TestingServersService::newServerQueue($json);
    }

    public function deleteServer($id): \Illuminate\Http\JsonResponse
    {
        return TestingServersService::deleteServer($id);
    }

    public function listServers(Request $request): JsonResponse
    {
        return new JsonResponse(TestingServersService::listServers($request));
    }


}
