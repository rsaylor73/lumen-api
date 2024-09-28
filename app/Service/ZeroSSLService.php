<?php

namespace App\Service;

use App\Service\TestingServersService;
use Illuminate\Support\Facades\Crypt;

class ZeroSSLService
{
    private static function runCommand($cmd)
    {
        system($cmd);
    }

    public static function fileCleanUp($server)
    {
        $files = ["testingservers.crt", "testingservers_bundle.crt", "testingservers.cnf", "testingservers.key", "testingservers.csr"];

        foreach ($files as $file) {
            $cmd = "ssh ubuntu@{$server->ip_address} \"rm -f /home/ubuntu/{$file}\" ";
            self::runCommand($cmd);
        }
    }

    public static function loopDns($id, $validation, $domain, $type)
    {
        $domain_cname_p1 = $validation['other_methods'][$domain]['cname_validation_p1'];
        $domain_cname_p2 = $validation['other_methods'][$domain]['cname_validation_p2'];

        $array = [
            "hostname" => $domain_cname_p1,
            "text_value" => $domain_cname_p2
        ];
        $result = TestingServersService::recordCnameRecord($id, $array, $type);
    }

    public static function deleteDns($key, $validation)
    {
        $domain_cname_p1 = $validation['other_methods'][$key]['cname_validation_p1'];

        $hostname = explode(".", $domain_cname_p1);
        $count = count($hostname);
        $cname = "";
        $domain = "";
        if ($count == "4") {
            // cname.ticket.domain.com
            // _24FB27FADE323FF91DE9165EEA597A24.dv-600a.virtualjobshadow.com
            $cname = $hostname[0] . "." . $hostname[1];
            $domain = $hostname[2] . "." . $hostname[3];

        } elseif ($count == "5") {
            // cname.ticket.sub.domain.com
            // _24FB27FADE323FF91DE9165EEA597A24.dv-600a.admin.virtualjobshadow.com
            $cname = $hostname[0] . "." . $hostname[1] . "." . $hostname[2];
            $domain = $hostname[3] . "." . $hostname[4];
        }
        DnsService::deleteDns($domain, $cname);
    }

    public static function generateCertificateSigningRequest($server)
    {
        /* Generate CSR */
        $cmd = "cd /home/ubuntu/Ansible && scp testingservers.cnf ubuntu@{$server->ip_address}:/home/ubuntu/testingservers.cnf";
        self::runCommand($cmd);

        $cmd = "ssh ubuntu@{$server->ip_address} \"cd /home/ubuntu && perl -pi -e 's/TICKET/{$server->dns}/g' testingservers.cnf\"";
        self::runCommand($cmd);

        $cmd = "ssh ubuntu@{$server->ip_address} \"openssl genrsa -out testingservers.key 2048\"";
        self::runCommand($cmd);

        $cmd = "ssh ubuntu@{$server->ip_address} \"openssl req -nodes -new -key testingservers.key -out testingservers.csr -subj \"/C=US/ST=NC/L=Asheville/O=DevOps/CN={$server->dns}.virtualjobshadow.com\" -config testingservers.cnf\"";
        self::runCommand($cmd);

        $cmd = "ssh ubuntu@{$server->ip_address} \"cd /home/ubuntu/strivvenmedia && git checkout -- .\"";
        self::runCommand($cmd);

        $csrFileName = date("U") . "-" . $server->id . ".csr";
        $cmd = "scp ubuntu@{$server->ip_address}:/home/ubuntu/testingservers.csr /var/www/html/csr/{$csrFileName}";
        self::runCommand($cmd);

        return $csrFileName;
    }

    public static function validateDomain($domain1, $domain2, $domain3, $domain4, $domain5, $csr)
    {
        $zero_ssl_key = getenv('ZERO_SSL_KEY');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "api.zerossl.com/certificates?access_key={$zero_ssl_key}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'certificate_domains' => "{$domain1},{$domain2},{$domain3},{$domain4},{$domain5}",
                'certificate_validity_days' => '90',
                'certificate_csr' => "{$csr}"
            ],
        ]);

        print "Debug:\n";
        print "Key: {$zero_ssl_key}\n";
        print "CSR:\n";
        print "{$csr}\n";
        print "Domains:\n";
        print "{$domain1}\n";
        print "{$domain2}\n";
        print "{$domain3}\n";
        print "{$domain4}\n";
        print "{$domain5}\n\n";

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            print "Error:\n";
            print_r($error_msg);
        }

        $response = json_decode($response, true);
        curl_close($curl);

        return $response;
    }

    public static function verifyDomain($ssl_id)
    {
        $zero_ssl_key = getenv('ZERO_SSL_KEY');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "api.zerossl.com/certificates/{$ssl_id}/challenges?access_key={$zero_ssl_key}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'validation_method' => 'CNAME_CSR_HASH'
            ],
        ]);

        $response = curl_exec($curl);
        $response = json_decode($response, true);

        curl_close($curl);

        return $response;
    }

    public static function downloadCertificate($ssl_id)
    {
        $zero_ssl_key = getenv('ZERO_SSL_KEY');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "api.zerossl.com/certificates/{$ssl_id}/download/return?access_key={$zero_ssl_key}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);

        curl_close($curl);

        return $response;
    }

    public static function installCertificate($server, $certificate, $ca_bundle)
    {
        /* Certificate */
        $cmd = "ssh ubuntu@{$server->ip_address} \"touch /home/ubuntu/testingservers.crt\"";
        self::runCommand($cmd);
        $cmd = "ssh ubuntu@{$server->ip_address} \"echo '{$certificate}' >> /home/ubuntu/testingservers.crt\"";
        self::runCommand($cmd);
        $cmd = "ssh ubuntu@{$server->ip_address} \"sudo mkdir -p /etc/apache2/ssl && sudo cp /home/ubuntu/testingservers.crt /etc/apache2/ssl/ssl.crt\"";
        self::runCommand($cmd);

        /* CA Bundle */
        $cmd = "ssh ubuntu@{$server->ip_address} \"touch /home/ubuntu/testingservers_bundle.crt\"";
        self::runCommand($cmd);
        $cmd = "ssh ubuntu@{$server->ip_address} \"echo '{$ca_bundle}' >> /home/ubuntu/testingservers_bundle.crt\"";
        self::runCommand($cmd);
        $cmd = "ssh ubuntu@{$server->ip_address} \"sudo mkdir -p /etc/apache2/ssl && sudo cp /home/ubuntu/testingservers_bundle.crt /etc/apache2/ssl/ca_bundle.crt\"";
        self::runCommand($cmd);

        /* Key */
        $cmd = "ssh ubuntu@{$server->ip_address} \"sudo mkdir -p /etc/apache2/ssl && sudo cp /home/ubuntu/testingservers.key /etc/apache2/ssl/ssl.key\"";
        self::runCommand($cmd);
        $cmd = "ssh ubuntu@{$server->ip_address} \"sudo apachectl restart\"";
        self::runCommand($cmd);
    }
}
