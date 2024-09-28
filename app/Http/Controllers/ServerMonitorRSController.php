<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ServerMonitorRSService;

class ServerMonitorRSController extends Controller
{
    public function refreshServers()
    {
        return new JsonResponse(ServerMonitorRSService::refreshServers());
    }

    public function listLoadBalancers()
    {
        return new JsonResponse(ServerMonitorRSService::listLoadBalancers());
    }
}
