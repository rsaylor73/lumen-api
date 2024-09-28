<?php

namespace App\Service;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Models\TestingServers;
use App\Models\DemoServers;

class StatsService
{

    public static function getStats($request): array
    {
        $type = $request->input('type');

        switch($type) {
            case "demo":
                // Demo Server
                $data = DemoServers::getServerStats($request->input('date1'), $request->input('date2'));
                break;
            default:
                // Testing Server
                $data = TestingServers::getServerStats($request->input('date1'), $request->input('date2'));
                break;
        }

        return $data;
    }
}
