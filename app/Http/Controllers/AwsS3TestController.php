<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\TestingServersService;

class AwsS3TestController extends Controller
{
    public function testS3()
    {
        $s3_key = env('S3_ACCESS_KEY');
        $s3_secret = env('S3_SECRET_ACCESS_KEY');

        $s3Client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'credentials' => [
                'key' => $s3_key,
                'secret' => $s3_secret
            ]
        ]);

        try {
            $object = $s3Client->getObject([
                'Bucket' => 'vjs-images-dev',
                'Key' => 'school-logos/school_default.png'
            ]);
            print_r($object);
        } catch (\Exception $e) {
            print "Error {$e}\n";
            die;
        }
    }
}
