<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\TestingServersService;

class TestingServerController extends Controller
{
    public function listServers(Request $request)
    {
        return new JsonResponse(TestingServersService::listServers($request));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newServer(Request $request)
    {
        $json = $request->json()->all();
        return TestingServersService::newServer($json);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newImportedServer(Request $request)
    {
        $json = $request->json()->all();
        return TestingServersService::newImportedServer($json);
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
        return TestingServersService::modifyServer($id, $action, $json);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateServerStatus($id, Request $request)
    {
        $json = $request->json()->all();
        return TestingServersService::updateServerStatus($id, $json);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteServer($id, Request $request)
    {
        $json = $request->json()->all();
        return TestingServersService::deleteServer($id, $json);
    }

    public function checkIpActive($ip)
    {
        return TestingServersService::checkIpActive($ip);
    }
}
