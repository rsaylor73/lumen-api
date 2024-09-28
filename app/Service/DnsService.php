<?php

namespace App\Service;

use Illuminate\Support\Facades\Crypt;

class DnsService
{
    /**
     * private getter for the hashed rackspace username
     */
    private static function getRackspaceUserName()
    {
        return env('DNS_USERNAME');
    }

    /**
     * private getter for the hashed rackspace api key
     */
    private static function getRackspaceApiKey()
    {
        return env('DNS_APIKEY');
    }

    /**
     * private getter for the rackspace API base URL (Cloud DNS)
     */
    private static function getUrl()
    {
        return "https://dns.api.rackspacecloud.com/";
    }

    /**
     * private function to obtain the hashed file content in the secure Lumen/Laravel storage area
     */
    private static function getHashedFile($file)
    {
        $hashedFile = file_get_contents($file);
        return Crypt::decrypt($hashedFile);
    }

    /**
     * private function to wrap GET and DELETE calls to rackspace
     */
    private static function curlWrapperGet($method, $url, $version, $tenant, $token, $endpoint)
    {
        $curl = curl_init();

        $url = $url . $version . $tenant . $endpoint;

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'X-Auth-Token: '.$token,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    private static function curlWrapperPost($type, $url, $version, $tenant, $token, $endpoint, $subDomain, $domain, $data)
    {
        $curl = curl_init();

        $url = $url . $version . $tenant . $endpoint;

        switch ($type) {
            case "A":
                $json = '{"records": [{"name": "'.$subDomain.'.'.$domain.'", "type": "A", "data": "'.$data.'", "ttl": "300"}]}';
                break;
            case "CNAME":
                $json = '{"records": [{"name": "'.$subDomain.'.'.$domain.'", "type": "CNAME", "data": "'.$data.'", "ttl": "300"}]}';
                break;
        }
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'X-Auth-Token: '.$token,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    public static function checkIfDnsIsProtected($subDomain)
    {
        $protectedList = [
            'www', 'dev', 'auth', 'admin', 'rostering', 'dev.auth', 'dev.admin', 'local.auth', 'vpn', 'deploy', 'blackboard', 'stage', 'stage.admin', 'stage.auth', 'stage.planner', 'dev.planner', 'helpcenter', 'brochures'
        ];

        foreach ($protectedList as $key => $value) {
            if ($value == $subDomain) {
                return false;
            }
        }
        return true;
    }
}
