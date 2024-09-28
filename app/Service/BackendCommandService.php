<?php

namespace App\Service;

use Illuminate\Support\Facades\Crypt;

class BackendCommandService
{
    private static function getAwsAccessKey()
    {
        return env('AWS_ACCESS_KEY');
    }

    private static function getAwsSecretKey()
    {
        return env('AWS_SECRET_KEY');
    }

    private static function getRackspaceUserName()
    {
        return env('DNS_USERNAME');
    }

    private static function getRackspaceApiKey()
    {
        return env('DNS_APIKEY');
    }

    private static function getZeroSslKey()
    {
        return env('ZERO_SSL_KEY');
    }

    private static function getSendGridKey()
    {
        return env('SENDGRID_API_KEY');
    }

    private static function getLumenApiKey()
    {
        return env('API_KEY');
    }

    private static function getWhiteListSecurityGroup()
    {
        return env('WHITELIST_SECURITY_GROUP');
    }

    private static function getNordVpnSecurityGroup()
    {
        return env('NORD_VPN_SECURITY_GROUP');
    }

    private static function getHashedFile($file)
    {
        $hashedFile = file_get_contents($file);
        return Crypt::decrypt($hashedFile);
    }

    public static function generateSSLRenewCommand($data)
    {
        $sendGridKey = self::getSendGridKey();

        return "cd /home/ubuntu/Ansible && ansible-playbook playbook-renew-letsentrypt.yml --extra-vars \"sg={$data->security_groupID} ip={$data->ip_address} instance={$data->instanceID} email={$data->email} dns={$data->dns} sendgrid={$sendGridKey}\" --ssh-common-args='-o StrictHostKeyChecking=no'";
    }

    public static function generateAnsibleCommand($data)
    {
        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);

        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        $zeroSslKey = self::getZeroSslKey();

        $sendGridKey = self::getSendGridKey();

        $lumenApiKey = self::getLumenApiKey();

        $whiteListSecurityGroup = self::getWhiteListSecurityGroup();
        $nordVpnSecurityGroup = self::getNordVpnSecurityGroup();
        /* End config details */

        return "cd /home/ubuntu/Ansible && ansible-playbook aws_create_dev_snapshot.yml --extra-vars \"aws_access_key={$awsAccessKey} aws_secret_key={$awsSecretKey} rs_user={$rackspaceUsername} rs_apikey={$rackspaceApiKey} repository={$data->ticket} dns={$data->dns} whitelist_sg={$whiteListSecurityGroup} nordvpn_sg={$nordVpnSecurityGroup} email={$data->email} zero_ssl_key={$zeroSslKey} send_grid_key={$sendGridKey} lumen_api_key={$lumenApiKey} serverID={$data->id} sentry_dns={$data->sentry_dns}\" > /var/www/html/log/server-{$data->id}.log ";
    }

    public static function generateAnsibleCommandNoSSH($data)
    {
        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);

        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        $zeroSslKey = self::getZeroSslKey();

        $sendGridKey = self::getSendGridKey();

        $lumenApiKey = self::getLumenApiKey();

        $whiteListSecurityGroup = self::getWhiteListSecurityGroup();
        $nordVpnSecurityGroup = self::getNordVpnSecurityGroup();
        /* End config details */

        return "cd /home/ubuntu/Ansible && ansible-playbook aws_create_dev_snapshot_no_ssh.yml --extra-vars \"aws_access_key={$awsAccessKey} aws_secret_key={$awsSecretKey} rs_user={$rackspaceUsername} rs_apikey={$rackspaceApiKey} repository={$data->ticket} dns={$data->dns} whitelist_sg={$whiteListSecurityGroup} nordvpn_sg={$nordVpnSecurityGroup} email={$data->email} zero_ssl_key={$zeroSslKey} send_grid_key={$sendGridKey} lumen_api_key={$lumenApiKey} serverID={$data->id} sentry_dns={$data->sentry_dns}\" > /var/www/html/log/server-{$data->id}.log ";
    }

    public static function getRSLoginTest()
    {
        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        return [
            'username' => $rackspaceUsername,
            'password' => $rackspaceApiKey
        ];
    }

    public static function generateCloneDevAnsibleCommand($data)
    {
        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);

        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        $zeroSslKey = self::getZeroSslKey();

        $sendGridKey = self::getSendGridKey();

        $lumenApiKey = self::getLumenApiKey();

        $whiteListSecurityGroup = self::getWhiteListSecurityGroup();
        $nordVpnSecurityGroup = self::getNordVpnSecurityGroup();
        /* End config details */

        return "cd /home/ubuntu/Ansible && ansible-playbook aws_create_dev_clone_snapshot.yml --extra-vars \"aws_access_key={$awsAccessKey} aws_secret_key={$awsSecretKey} rs_user={$rackspaceUsername} rs_apikey={$rackspaceApiKey} repository={$data->ticket} dns={$data->dns} whitelist_sg={$whiteListSecurityGroup} nordvpn_sg={$nordVpnSecurityGroup} email={$data->email} zero_ssl_key={$zeroSslKey} send_grid_key={$sendGridKey} lumen_api_key={$lumenApiKey} serverID={$data->id} sentry_dns={$data->sentry_dns}\" > /var/www/html/log/server-{$data->id}.log ";
    }

    public static function generateRestoreSnapshotAnsibleCommand($data, $snapshot)
    {
        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);

        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        $zeroSslKey = self::getZeroSslKey();

        $sendGridKey = self::getSendGridKey();

        $lumenApiKey = self::getLumenApiKey();

        $whiteListSecurityGroup = self::getWhiteListSecurityGroup();
        $nordVpnSecurityGroup = self::getNordVpnSecurityGroup();
        /* End config details */

        return "cd /home/ubuntu/Ansible && ansible-playbook aws_create_dev_restore_snapshot.yml --extra-vars \"aws_access_key={$awsAccessKey} aws_secret_key={$awsSecretKey} rs_user={$rackspaceUsername} rs_apikey={$rackspaceApiKey} repository={$data->ticket} dns={$data->dns} whitelist_sg={$whiteListSecurityGroup} nordvpn_sg={$nordVpnSecurityGroup} email={$data->email} zero_ssl_key={$zeroSslKey} send_grid_key={$sendGridKey} lumen_api_key={$lumenApiKey} serverID={$data->ticketID} snapshot={$snapshot} sentry_dns={$data->sentry_dns}\" > /var/www/html/log/server-{$data->ticketID}.log ";
    }

    public static function generateRestoreCloneSnapshotAnsibleCommand($data, $snapshot)
    {
        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);

        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        $zeroSslKey = self::getZeroSslKey();

        $sendGridKey = self::getSendGridKey();

        $lumenApiKey = self::getLumenApiKey();

        $whiteListSecurityGroup = self::getWhiteListSecurityGroup();
        $nordVpnSecurityGroup = self::getNordVpnSecurityGroup();
        /* End config details */

        return "cd /home/ubuntu/Ansible && ansible-playbook aws_create_dev_clone_restore_snapshot.yml --extra-vars \"aws_access_key={$awsAccessKey} aws_secret_key={$awsSecretKey} rs_user={$rackspaceUsername} rs_apikey={$rackspaceApiKey} repository={$data->ticket} dns={$data->dns} whitelist_sg={$whiteListSecurityGroup} nordvpn_sg={$nordVpnSecurityGroup} email={$data->email} zero_ssl_key={$zeroSslKey} send_grid_key={$sendGridKey} lumen_api_key={$lumenApiKey} serverID={$data->ticketID} snapshot={$snapshot} sentry_dns={$data->sentry_dns}\" > /var/www/html/log/server-{$data->ticketID}.log ";
    }

    public static function generateDemoServerAnsibleCommand($data)
    {
        /* Get config details */
        $awsAccessKey = self::getAwsAccessKey();
        $file = storage_path() . "/app/" . $awsAccessKey;
        $awsAccessKey = self::getHashedFile($file);

        $awsSecretKey = self::getAwsSecretKey();
        $file = storage_path() . "/app/" . $awsSecretKey;
        $awsSecretKey = self::getHashedFile($file);

        $rs_user = self::getRackspaceUserName();
        $file = storage_path() . "/app/" . $rs_user;
        $rackspaceUsername = self::getHashedFile($file);

        $rs_apikey = self::getRackspaceApiKey();
        $file = storage_path() . "/app/" . $rs_apikey;
        $rackspaceApiKey = self::getHashedFile($file);

        $sendGridKey = self::getSendGridKey();

        $lumenApiKey = self::getLumenApiKey();

        return "cd /home/ubuntu/Ansible && ansible-playbook aws_create_cpbase_snapshot.yml --extra-vars \"aws_access_key={$awsAccessKey} aws_secret_key={$awsSecretKey} rs_user={$rackspaceUsername} rs_apikey={$rackspaceApiKey} dns={$data->dns} email={$data->email} send_grid_key={$sendGridKey} lumen_api_key={$lumenApiKey} serverID={$data->id}\" > /var/www/html/log/demo-server-{$data->id}.log ";
    }
}
