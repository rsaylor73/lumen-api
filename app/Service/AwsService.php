<?php

namespace App\Service;

use Illuminate\Support\Facades\Crypt;

class AwsService
{
    private static function getAwsAccessKey()
    {
        return env('AWS_ACCESS_KEY');
    }

    private static function getAwsSecretKey()
    {
        return env('AWS_SECRET_KEY');
    }

    private static function getAwsSSOAccessKey()
    {
        return env('AWS_SSO_ACCESS_KEY');
    }

    private static function getAwsSSOSecretKey()
    {
        return env('AWS_SSO_SECRET_KEY');
    }

    private static function getAwsRegion()
    {
        return env('AWS_REGION');
    }

    /**
     * private function to obtain the hashed file content in the secure Lumen/Laravel storage area
     */
    private static function getHashedFile($file)
    {
        $hashedFile = file_get_contents($file);
        return Crypt::decrypt($hashedFile);
    }

    public static function ec2Client()
    {
        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);
        /* End config details */

        return new \Aws\Ec2\Ec2Client([
            'version' => 'latest',
            'region' => self::getAwsRegion(),
            'credentials' => [
                'key' => $awsAccessKey,
                'secret' => $awsSecretKey
            ]
        ]);
    }

    public static function s3Client()
    {
        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);
        /* End config details */

        return new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => self::getAwsRegion(),
            'credentials' => [
                'key' => $awsAccessKey,
                'secret' => $awsSecretKey
            ]
        ]);
    }

    public static function route53Client()
    {
        //$awsAccessKey = self::getAwsSSOAccessKey();
        //$awsSecretKey = self::getAwsSSOSecretKey();

        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);
        /* End config details */

        return new \Aws\Route53\Route53Client([
            'version' => 'latest',
            'region' => self::getAwsRegion(),
            'credentials' => [
                'key' => $awsAccessKey,
                'secret' => $awsSecretKey
            ]
        ]);
    }

    public static function queryRecord($subDomain, $domain, $hostedZoneId)
    {
        $route53Client = self::route53Client();

        print "H: {$hostedZoneId}\n";

        try {
            return $route53Client->listResourceRecordSets([
                'HostedZoneId' => "$hostedZoneId",
                'StartRecordName' => "{$subDomain}.{$domain}.",
                'StartRecordType' => 'A',
                'MaxItems' => '1'
            ]);
        } catch (\Exception $e) {
            print "Error 1\n";
            return false;
        }
    }

    public static function createRecord($subDomain, $domain, $ipAddress, $hostedZoneId)
    {
        $route53Client = self::route53Client();

        try {
            $data[] = ["Value" => $ipAddress];

            $response = $route53Client->changeResourceRecordSets([
                'ChangeBatch'  => [
                    'Changes' => [
                        [
                            'Action'            => 'CREATE',
                            'ResourceRecordSet' => [
                                'Name'          => "{$subDomain}.{$domain}.",
                                'Type'          => 'A',
                                'TTL'           => 120,
                                'ResourceRecords' => $data
                            ],
                        ]
                    ]
                ],
                'HostedZoneId' => "/hostedzone/{$hostedZoneId}"
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function deleteRecord($subDomain, $domain, $ipAddress, $hostedZoneId)
    {
        $route53Client = self::route53Client();

        try {
            $data[] = ["Value" => $ipAddress];

            $response = $route53Client->changeResourceRecordSets([
                'ChangeBatch'  => [
                    'Changes' => [
                        [
                            'Action'            => 'DELETE',
                            'ResourceRecordSet' => [
                                'Name'          => "{$subDomain}.{$domain}.",
                                'Type'          => 'A',
                                'TTL'           => 120,
                                'ResourceRecords' => $data
                            ],
                        ]
                    ]
                ],
                'HostedZoneId' => "/hostedzone/{$hostedZoneId}"
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function describeInstance($instanceId)
    {
        $ec2Client = self::ec2Client();
        $instanceIds = [$instanceId];

        try {
            return $ec2Client->describeInstances(['InstanceIds' => $instanceIds,]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function describeInstances()
    {
        $ec2Client = self::ec2Client();
        return $ec2Client->describeInstances();
    }

    public static function describeInstanceStatus($instanceId)
    {
        $ec2Client = self::ec2Client();
        $instanceIds = [$instanceId];
        return $ec2Client->describeInstanceStatus(['InstanceIds' => $instanceIds]);
    }

    public static function shutDownInstance($instanceId)
    {
        $ec2Client = self::ec2Client();

        $instanceIds = [$instanceId];

        try {
            $result = $ec2Client->stopInstances([
                'InstanceIds' => $instanceIds,
            ]);
            return $result->get('StoppingInstances');
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function startInstance($instanceId)
    {
        $ec2Client = self::ec2Client();

        $instanceIds = [$instanceId];

        try {
            $result = $ec2Client->startInstances([
                'InstanceIds' => $instanceIds,
            ]);
            return $result->get('StartingInstances');
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function terminateInstance($instanceId)
    {
        $ec2Client = self::ec2Client();

        $instanceIds = [$instanceId];

        try {
            $result = $ec2Client->TerminateInstances([
                'InstanceIds' => $instanceIds,
            ]);
        } catch (\Exception $e) {
            return false;
        }

        return $result->get('TerminatingInstances');
    }

    public static function describeSnapshot($snapshotID)
    {
        $ec2Client = self::ec2Client();

        try {
            $result = $ec2Client->describeSnapshots([
                'DryRun' => false,
                'SnapshotIds' => [
                    $snapshotID,
                ],
            ]);
        } catch (\Exception $e) {
            print "Error {$e}\n";
            $result = false;
        }
        return $result;
    }

    public static function createSnapShot($volumeID, $description)
    {
        $ec2Client = self::ec2Client();
        /*
         * SnapshotCreationPerVolumeRateExceeded (client): The maximum per volume CreateSnapshot request rate has been exceeded.
         * Use an increasing or variable sleep interval between requests.
         */
        sleep(60); // sleep for 60 seconds to allow the proper time to pass

        try {
            $result = $ec2Client->createSnapshot([
                'Description' => $description,
                'DryRun' => false,
                'region' => 'us-east-1',
                'TagSpecifications' => [
                    [
                        'ResourceType' => 'snapshot',
                        'Tags' => [
                            [
                                'Key' => 'Name',
                                'Value' => $description,
                            ],
                        ],
                    ],
                ],
                'VolumeId' => $volumeID,
            ]);
        } catch (\Exception $e) {
            print "Error {$e}\n";
            $result = false;
        }
        return $result;
    }

    public static function putObject($bucket, $path, $name)
    {
        $s3Client = self::s3Client();

        try {
            $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $name,
                'SourceFile' => $path
            ]);
            $s3Client->waitUntil('ObjectExists', ['Bucket' => $bucket, 'Key' => $name]);
        } catch (\Exception $e) {
            print "Error {$e}\n";
            $result = false;
        }
        return $result; // $result['ObjectURL']
    }

    public static function getObject($bucket, $key)
    {
        $s3Client = self::s3Client();

        try {
            return $s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $key
            ]);
        } catch (\Exception $e) {
            print "Error {$e}\n";
            return false;
        }
    }

    public static function rebootInstance($instanceID)
    {
        $ec2Client = self::ec2Client();

        $instanceIds = array($instanceID);

        try {
            $result = $ec2Client->rebootInstances([
                'InstanceIds' => $instanceIds,
            ]);
        } catch (\Exception $e) {
            $result = false;
        }

        return $result ;
    }

}
