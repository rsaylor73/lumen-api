<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\v2\DemoServersService;

class DemoServerController extends Controller
{
    public function newServer(Request $request)
    {
        $json = $request->json()->all();
        return DemoServersService::newServerQueue($json);
    }

    public function deleteServer($id): \Illuminate\Http\JsonResponse
    {
        return DemoServersService::deleteServer($id);
    }
}
