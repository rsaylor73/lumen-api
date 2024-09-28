<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\TestingServersService AS DemoServersService;

class DemoServerController extends Controller
{
    public function listServers(Request $request)
    {
        return new JsonResponse(DemoServersService::listDemoServers($request));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newServer(Request $request)
    {
        $json = $request->json()->all();
        return DemoServersService::newDemoServer($json);
    }

    /**
     * @param $id
     * @param $action
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function modifyServer($id, $action, Request $request)
    {
        $json = $request->json()->all();
        return DemoServersService::modifyDemoServer($id, $action, $json);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateServerStatus($id, Request $request)
    {
        $json = $request->json()->all();
        return DemoServersService::updateDemoServerStatus($id, $json);
    }
}
