<?php

namespace App\Service;

use Illuminate\Support\Facades\Crypt;
use App\Service\TestingServersService;
use App\Models\RackspaceServers;

class ServerMonitorRSService
{
    private static function getRackspaceUserName()
    {
        return env('SERVER_MONITOR_RS_USERNAME');
    }

    private static function getRackspaceApiKey()
    {
        return env('SERVER_MONITOR_RS_APIKEY');
    }

    private static function getUrl($region, $type)
    {
        return "https://{$region}.{$type}.api.rackspacecloud.com";
    }

    private static function getHashedFile($file)
    {
        $hashedFile = file_get_contents($file);
        return Crypt::decrypt($hashedFile);
    }

    private static function rackSpaceAuthenticate($rackspaceUsername, $rackspaceApiKey)
    {
        $curl = curl_init();

        $json = '{"auth": {"RAX-KSKEY:apiKeyCredentials": {"username": "' . $rackspaceUsername . '","apiKey": "' . $rackspaceApiKey . '"}}}';

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://identity.api.rackspacecloud.com/v2.0/tokens',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $json = json_decode($response, true);
        $token = $json['access']['token']['id'];
        $tenant = $json['access']['token']['tenant']['id'];

        $payload = [
            'token' => $token,
            'tenant' => $tenant,
        ];
        curl_close($curl);
        return $payload;
    }

    // https://ord.servers.api.rackspacecloud.com/v2/640095/servers
    private static function curlWrapperGet($method, $url, $version, $tenant, $token, $endpoint)
    {
        $curl = curl_init();

        $url = $url . '/' . $version . '/' . $tenant . '/' . $endpoint;

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
                'X-Auth-Token: ' . $token,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    public static function refreshServers()
    {
        $result = self::updateServers();
        if ($result === true) {
            return TestingServersService::sendJsonResponse('All Rackspace servers have been updated.', 200);
        } else {
            return TestingServersService::sendJsonResponse('No servers found.', 400);
        }
    }

    public static function updateServers()
    {
        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        $rs = self::rackSpaceAuthenticate($rackspaceUsername, $rackspaceApiKey);

        $data = self::curlWrapperGet('GET', self::getUrl('ord', 'servers'), 'v2', $rs['tenant'], $rs['token'], 'servers');

        $servers = $data['servers'];

        $vjsServers = array();
        $i = 0;

        foreach ($servers as $s) {
            $vjsServers[$i]['name'] = $s['name'];
            $vjsServers[$i]['ID'] = $s['id'];
            $serverDetails = self::curlWrapperGet('GET', self::getUrl('ord', 'servers'), 'v2', $rs['tenant'], $rs['token'], "servers/{$s['id']}");

            $vjsServers[$i]['status'] = $serverDetails['server']['status'];
            $vjsServers[$i]['ipAddress'] = $serverDetails['server']['addresses']['private'][0]['addr'];

            if (isset($serverDetails['server']['addresses']['RC-DMZ'])) {
                $vjsServers[$i]['dmzIpAddress'] = $serverDetails['server']['addresses']['RC-DMZ'][0]['addr'];
            }
            $i++;
        }

        $foundServers = false;
        if (is_array($vjsServers)) {
            $foundServers = true;
            if (!empty($vjsServers)) {
                foreach ($vjsServers as $s) {
                    $query = RackspaceServers::findServerByField('name', $s['name']);
                    $found = false;
                    foreach ($query as $q) {
                        $found = true;
                        $dmzIpAddress = null;
                        if (isset($s['dmzIpAddress'])) {
                            $dmzIpAddress = $s['dmzIpAddress'];
                        }
                        RackspaceServers::updateServer($q->id, $s['name'], $s['ID'], $s['status'], $s['ipAddress'], $dmzIpAddress);
                    }
                    if ($found === false) {
                        $dmzIpAddress = null;
                        if (isset($s['dmzIpAddress'])) {
                            $dmzIpAddress = $s['dmzIpAddress'];
                        }
                        RackspaceServers::newServer($s['name'], $s['ID'], $s['status'], $s['ipAddress'], $dmzIpAddress);
                    }
                }
            }
        }


        if ($foundServers === false) {
            return false;
        } else {
            return true;
        }
    }

    public static function listLoadBalancers()
    {
        self::updateServers();

        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        $rs = self::rackSpaceAuthenticate($rackspaceUsername, $rackspaceApiKey);

        $data = self::curlWrapperGet('GET', self::getUrl('ord', 'loadbalancers'), 'v1.0', $rs['tenant'], $rs['token'], 'loadbalancers');

        $balancer = $data['loadBalancers'];

        $loadBalancers = array();
        $i = 0;

        foreach ($balancer as $b) {
            $loadBalancers[$i]['name'] = $b['name'];
            $loadBalancers[$i]['id'] = $b['id'];
            $loadBalancers[$i]['status'] = $b['status'];

            $data2 = self::curlWrapperGet('GET', self::getUrl('ord', 'loadbalancers'), 'v1.0', $rs['tenant'], $rs['token'], "loadbalancers/{$b['id']}/nodes");
            $nodes = $data2['nodes'];
            $i2 = 0;
            $activeNodes = 0;
            foreach ($nodes as $node) {
                $condition = $node['condition'];
                $status = $node['status'];
                $type = $node['type'];
                $ip = $node['address'];

                $query = RackspaceServers::findServerByField('ipAddress', $ip);
                $name = "None/Missing";
                $dmz = "N/A";
                foreach ($query as $q) {
                    $name = $q->name;
                    if (!is_null($q->dmzIpAddress)) {
                        $dmz = $q->dmzIpAddress;
                    }
                }
                $loadBalancers[$i]['nodes'][$i2]['name'] = $name;
                $loadBalancers[$i]['nodes'][$i2]['ipAddress'] = $ip;
                $loadBalancers[$i]['nodes'][$i2]['dmz'] = $dmz;
                $loadBalancers[$i]['nodes'][$i2]['condition'] = $condition;
                $loadBalancers[$i]['nodes'][$i2]['status'] = $status;
                $loadBalancers[$i]['nodes'][$i2]['type'] = $type;
                if (($status == "ONLINE") && ($condition == "ENABLED")) {
                    $activeNodes++;
                }
                $i2++;
            }
            $loadBalancers[$i]['active_nodes'] = $activeNodes;
            $i++;
        }

        return $loadBalancers;
    }
}
