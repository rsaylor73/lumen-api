<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ReportsService;

class ReportsController extends Controller
{
    public function serverMinReport()
    {
        ReportsService::runServerMinReport();
    }
}
