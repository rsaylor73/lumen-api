<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\StatsService;

class StatsController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        # test github action
        return new JsonResponse(StatsService::getStats($request));
    }
}
