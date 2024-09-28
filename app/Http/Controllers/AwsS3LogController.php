<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\TestingServersService;

class AwsS3LogController extends Controller
{
    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function collectS3Logs($id, Request $request)
    {
        return TestingServersService::collectS3Logs($id, $request->request->get('content'));
    }

    /**
     * @param $id
     * @return \Response
     */
    public function streamAwsLog($id)
    {
        return TestingServersService::streamAwsLog($id);
    }
}
