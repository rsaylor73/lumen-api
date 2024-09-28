<?php

namespace App\Service;

use SendGrid\Mail\Mail;
use App\Service\AwsService;
use App\Models\TestingServers;
use App\Models\DemoServers;

class ReportsService
{
    /**
     * @return array
     */
    private static function getConfig(): array
    {
        $emailFrom = "donotreply@pathful.com";
        $emails = ["rsaylor@pathful.com", "tlandes@pathful.com", "malverson@pathful.com"];
        $ec2DegradedEmails = ["rsaylor@pathful.com"];
        $emailSubject = 'Rackspace Load Balancer Report';
        $emailPreHeader = 'Open the report to view attached servers to load balancers.';
        $minNodes = [
            // Add additional load balancers here
            "load_balancers" => [
                "vjs" => [
                    "id" => "459040",
                    "minNodes"=> "2",
                    "primeNodes" => "6"
                ],
                "admin" => [
                    "id" => "459505",
                    "minNodes" => "2",
                    "primeNodes" => "1"
                ],
                "auth" => [
                    "id" => "459508",
                    "minNodes" => "1",
                    "primeNodes" => "1"
                ],
                "jr" => [
                    "id" => "459511",
                    "minNodes" => "2",
                    "primeNodes" => "2"
                ]
            ]
        ];
        // 24 Hour HH:MM
        $primeHours = [
          "start" => "06:00",
          "end" => "18:00",
        ];

        $config['emailFrom'] = $emailFrom;
        $config['emails'] = $emails;
        $config['ec2DegradedEmails'] = $ec2DegradedEmails;
        $config['emailSubject'] = $emailSubject;
        $config['emailPreHeader'] = $emailPreHeader;
        $config['minNodes'] = $minNodes;
        $config['primeHours'] = $primeHours;

        return $config;
    }

    public static function testingServerErrorReport()
    {
        $config = self::getConfig();
        $ignoreList = self::ignoreEc2List();
        $instances = AwsService::describeInstances();
        $data = $instances['Reservations'];

        $html = "";

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $ignore = false;
                foreach ($ignoreList as $key2 => $value2) {
                    if ($ignoreList[$key2]['instance'] == $data[$key]['Instances'][0]['InstanceId']) {
                        $ignore = true;
                    }
                }

                $state = $data[$key]['Instances'][0]['State']['Name'];

                $found = false;
                if ($ignore === false) {
                    if ($state == "running") {
                        /* Locate instance in Testing Server DB */
                        $server = TestingServers::getServerByInstanceId($data[$key]['Instances'][0]['InstanceId']);
                        foreach ($server as $s) {
                            $found = true;
                            if ($s->status == "error") {
                                $html .= "ID (Testing): {$s->id}<br>";
                                $html .= "Status: {$s->status}<br>";
                                $html .= "AWS Status: {$state}<br>";
                                $html .= "Error: Server is still up!<br>";
                                $html .= "Instance: {$data[$key]['Instances'][0]['InstanceId']}<br>";
                                $html .= "==========================<br>";
                            }
                        }

                        /* Locate instance in Demo Server DB */
                        $server = DemoServers::getServerByInstanceId($data[$key]['Instances'][0]['InstanceId']);
                        foreach ($server as $s) {
                            $found = true;
                            if ($s->status == "error") {
                                $html .= "ID (Demo): {$s->id}<br>";
                                $html .= "Status: {$s->status}<br>";
                                $html .= "AWS Status: {$state}<br>";
                                $html .= "Error: Server is still up!<br>";
                                $html .= "Instance: {$data[$key]['Instances'][0]['InstanceId']}<br>";
                                $html .= "==========================<br>";
                            }
                        }

                        if ($found === false) {
                            $html .= "Error: Not in database.<br>";
                            $html .=  "AWS Status: {$state}<br>";
                            $html .= "Instance: {$data[$key]['Instances'][0]['InstanceId']}<br>";
                            $html .= "==========================<br>";
                        }
                    }
                }
            }
        }

        if ($html != "") {
            $email = new Mail();
            $subject = "AWS Servers Running in Error";
            $email->setFrom($config['emailFrom']);
            $email->setSubject($subject);

            $email->addTo('rsaylor@pathful.com');

            $email->addContent(
                "text/html", view('email_template.aws_ec2_error', [
                    "subject" => $subject,
                    "preheader" => $config['emailPreHeader'],
                    "html" => $html,
                ]
            )->render()
            );
            $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
            $sendgrid->send($email);
        }
    }

    public static function runServerMinReport($type): bool
    {
        $config = self::getConfig();
        $data = ServerMonitorRSService::listLoadBalancers();

        $loadBalancers = $config['minNodes']['load_balancers'];
        $startTime = $config['primeHours']['start'];
        $endTime = $config['primeHours']['end'];

        $timeNow = new \DateTime();
        $timeNow->setTimezone(new \DateTimeZone('America/New_York'));
        $html = "";
        $error_summary = array();
        $error = false;

        foreach ($data as $key => $value) {
            foreach ($loadBalancers as $key2 => $value2) {
                if ($data[$key]['id'] == $loadBalancers[$key2]['id']) {
                    $error_summary[$key2] = "false"; // default setting
                    $html .= "<b>Load Balancer: $key2</b><hr>";
                    $html .= "Minimum Servers: {$loadBalancers[$key2]['minNodes']}<br>";
                    $html .= "Prime Servers: {$loadBalancers[$key2]['primeNodes']}<br>";
                    $html .= "Active Servers: {$data[$key]['active_nodes']}<br>";

                    // run cron M-F only (6:30 am, 7:30 am, noon, 8:30 pm)

                    // if in the time we should check for the min servers up (primeNodes)
                    if (($timeNow->format("H:i") >= $startTime) or ($timeNow->format("H:i") <= $endTime)) {
                        if ($data[$key]['active_nodes'] < $loadBalancers[$key2]['primeNodes']) {
                            $html .= "<br><b><font color='red'>ERROR:</font></b> The number of active servers does not meet the number of prime nodes. Please add more servers.<br><br>";
                            $error_summary[$key2] = "true";
                            $error = true;
                        }
                    }

                    if (($timeNow->format("H:i") < $startTime) or ($timeNow->format("H:i") > $endTime)) {
                        if ($data[$key]['active_nodes'] > $loadBalancers[$key2]['minNodes']) {
                            $html .= "<br><b><font color='red'>ERROR:</font></b> The number of active servers exceeds the minimum servers. The time {$timeNow->format("H:i")} is outside the operating hours $startTime to $endTime<br><br>";
                            $error_summary[$key2] = "true";
                        }
                    }

                    $html .= "<ul><b>Nodes:</b>";
                    $nodes = $data[$key]['nodes'];
                    sort($nodes);
                    foreach ($nodes as $key3 => $value3) {
                        $html .= "<ul><b>Server Name: {$nodes[$key3]['name']}</b>";
                        $html .= "<li>IP Address: {$nodes[$key3]['ipAddress']}</li>";
                        $html .= "<li>DMZ Address: {$nodes[$key3]['dmz']}</li>";
                        if ($nodes[$key3]['status'] != "ONLINE") {
                            $html .= "<li><b><font color='red'>ERROR:</font></b> Server is not online!</li>";
                            $error_summary[$key2] = "true";
                        } else {
                            $html .= "<li>Server is online.</li>";
                        }
                        if ($nodes[$key3]['condition'] != "ENABLED") {
                            $html .= "<li><b><font color='orange'>WARNING</font></b>: Server is not enabled in the Load Balancer!</li>";
                        } else {
                            $html .= "<li>Server is enabled in the Load Balancer.</li>";
                        }
                        $html .= "</ul>";
                    }
                    $html .= "</ul>";
                }
            }
        }

        if ($type == "summary") {
            $subject = $config['emailSubject'];
        } elseif ($type == "error") {
            $subject = "ERROR : " . $config['emailSubject'];
        }

        $email = new Mail();

        $email->setFrom($config['emailFrom']);
        $email->setSubject($subject);

        foreach ($config['emails'] as $key => $value) {
            $email->addTo($value);
        }

        $email->addContent(
            "text/html", view('email_template.rackspace_report', [
                    "subject" => $subject,
                    "preheader" => $config['emailPreHeader'],
                    "html" => $html,
                    "error_summary" => $error_summary,
                ]
            )->render()
        );
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            if ($type == "summary") {
                $sendgrid->send($email);
            } elseif ($type == "error") {
                if ($error === true) {
                    $sendgrid->send($email);
                }
            }
            return true;
        } catch (Exception $e) {
            echo 'Caught exception: '.  $e->getMessage(). "\n";
            return false;
        }
    }

    public static function staleServerReport($emailTo, $dns)
    {
        $config = self::getConfig();

        $subject = "Stale server : {$dns}";

        $email = new Mail();

        $email->setFrom($config['emailFrom']);
        $email->setSubject($subject);

        $email->addTo($emailTo);

        $email->addContent(
            "text/html", view('email_template.stale_server_report', [
                "subject" => $subject,
                "preheader" => 'Possible old testing server running.',
                "dns" => $dns
                ]
            )->render()
        );
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $sendgrid->send($email);
            return true;
        } catch (Exception $e) {
            echo 'Caught exception: '.  $e->getMessage(). "\n";
            return false;
        }
    }

    public static function degradedEc2Server($instanceId)
    {
        $config = self::getConfig();
        $subject = "Degraded AWS EC2 Server";

        $email = new Mail();

        $email->setFrom($config['emailFrom']);
        $email->setSubject($subject);

        foreach ($config['ec2DegradedEmails'] as $key => $value) {
            $email->addTo($value);
        }

        $email->addContent(
            "text/html", view('email_template.aws_ec2_degraded_server', [
                "subject" => $subject,
                "preheader" => 'Degraded AWS EC2 Server.',
                "instanceId" => $instanceId
            ]
            )->render()
        );
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $sendgrid->send($email);
            return true;
        } catch (Exception $e) {
            echo 'Caught exception: '.  $e->getMessage(). "\n";
            return false;
        }
    }

    private static function ignoreEc2List()
    {
        return [
            [
                'instance' => 'i-0e81a92fe3e18a9c5' // CP Base V2
            ],
            [
                'instance' => 'i-081727cd5d97d4d1f' // dev.virtualjobshadow.com (new 5/11/2022)
            ],
            [
                'instance' => 'i-0abb652dfeb7302c1' // Lumen-T3-Medium-V2-PROD
            ],
            [
                'instance' => 'i-01bee6093acc13a44' // Lumen-T3-Medium-V2-DEV
            ],
            [
                'instance' => 'i-03d3ee5eb9529379f' // bcps - NOT CP-BASE
            ],

            [
                'instance' => 'i-016a511b70115505a' // WSL2-Daily-Live-Database
            ],
            [
                'instance' => 'i-0f5eeb5bab090bbb6' // CP Base V3 (Current)
            ]
        ];
    }
}
