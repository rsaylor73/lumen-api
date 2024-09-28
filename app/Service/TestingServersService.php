<?php

namespace App\Service;

use App\Models\DelayDnsLog;
use App\Models\DemoServersQueue;
use App\Models\EventLog;
use App\Models\LogFiles;
use App\Models\PendingServerQueue;
use App\Models\ServerStatus;
use App\Models\TestingServers;
use App\Models\DemoServers;
use App\Models\DemoServerStatus;
use App\Models\cNameRecords;
use App\Models\cNameRecordsDemoServers;
use App\Models\UnitTests;
use App\Models\ZeroSSL;
use App\Models\RestoreSnapshotServerQueue;
use App\Models\DeleteTestingServer;
use App\Models\HardRebootServerQueue;
use App\Models\RebuildTestingServerQueue;
use App\Models\RenewSslQueue;
use App\Models\RefreshDnsQueue;
use App\Models\SnapshotQueue;
use App\Service\ApiService;
use App\Service\AwsService;
use App\Service\DnsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TestingServersService
{
    /**
     * @return array
     */
    private static function getConfig(): array
    {
        $newServerFields = ["ticket", "dns", "email", "description"];
        $newDemoServerFields = ["email"];
        $deleteServerFields = ["email"];
        $modifyServerIpFields = ["ip_address"];
        $modifyServerInstanceFields = ["instance"];
        $modifyServerSecurityFields = ["security"];
        $modifyServerExtendFields = ["date"];
        $updateServerStatusFields = ["status"];
        $logServerActivityFields = ["message", "section"];
        $cNameFields = ["hostname", "text_value"];
        $sSLFields = ["zero_ssl_id"];
        $allowedSortFields = ["ticket", "dns", "status", "description", "created_at", "email"];
        $searchMaxLength = "50";

        $config['new_server_fields'] = $newServerFields;
        $config['delete_server_fields'] = $deleteServerFields;
        $config['new_demo_server_fields'] = $newDemoServerFields;
        $config['modify_server_ip_fields'] = $modifyServerIpFields;
        $config['modify_server_instance_fields'] = $modifyServerInstanceFields;
        $config['modify_server_security_fields'] = $modifyServerSecurityFields;
        $config['modify_server_extend_fields'] = $modifyServerExtendFields;
        $config['update_server_status_fields'] = $updateServerStatusFields;
        $config['log_server_activity_fields'] = $logServerActivityFields;
        $config['cname_fields'] = $cNameFields;
        $config['zero_ssl_fields'] = $sSLFields;
        $config['allowed_sort_fields'] = $allowedSortFields;
        $config['search_max_length'] = $searchMaxLength;

        return $config;
    }

    public static function maintenanceMode()
    {
        return env('MAINTENANCE_MODE');
    }

    /**
     * @param $content
     * @return bool
     */
    public static function checkContent($content): bool
    {
        if (!empty($content)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $fields
     * @param $content
     * @return bool|JsonResponse
     */
    public static function requiredFields(array $fields, $content)
    {
        $error = array();
        foreach ($fields as $field) {
            if (!isset($content[$field])) {
                $error[] = $field;
            }
        }
        if (!empty($error)) {
            return ApiService::sendJsonResponse('One or more fields are missing', 400, 'fields', $error);
        } else {
            return true;
        }
    }

    /**
     * @param array $fields
     * @param $field
     * @return bool|JsonResponse
     */
    public static function checkSortFields(array $fields, $field)
    {
        // allowed_sort_fields
        foreach ($fields as $f) {
            if ($field == $f) {
                return true;
            }
        }
        $error[] = $field;
        return ApiService::sendJsonResponse('The field passed order_by is not sortable.', 400, 'field', $error);
    }

    /**
     * @param $sortOrder
     * @return bool|JsonResponse
     */
    public static function checkSortOrder($sortOrder)
    {
        switch ($sortOrder) {
            case "DESC":
            case "desc":
            case "ASC":
            case "asc":
                return true;
            default:
                return ApiService::sendJsonResponse('Order direction must be "asc" or "desc".', 400);
        }
    }

    /**
     * @param $string
     * @param $maxLength
     * @return bool|JsonResponse
     */
    public static function checkStringLength($string, $maxLength)
    {
        $len = strlen($string);
        if ($len > $maxLength) {
            return ApiService::sendJsonResponse("The field passed exceeds the maximum length of {$maxLength}.", 400);
        } else {
            return true;
        }
    }

    /**
     * @param $request
     * @return array|bool|JsonResponse
     */
    public static function listServers($request)
    {
        $config = self::getConfig();

        $status = $request->input('status');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 25);
        $searchTerm = $request->input('search_term');
        $orderBy = $request->input('order_by');
        $sortOrder = $request->input('sort_order');
        $emailFilter = $request->input('email_filter');
        $dateStartFilter = $request->input('date_start');
        $dateEndFilter = $request->input('date_end');
        $textSearch = $request->input('text_search');

        if (!empty($sortOrder)) {
            $check = self::checkSortOrder($sortOrder);
            if ($check !== true) {
                return $check;
            }
        }

        if (!empty($searchTerm)) {
            $check = self::checkStringLength($searchTerm, $config['search_max_length']);
            if ($check !== true) {
                return $check;
            }
        }

        if (!empty($orderBy)) {
            // check if field is valid
            $check = self::checkSortFields($config['allowed_sort_fields'], $orderBy);
            if ($check !== true) {
                return $check;
            }
        }

        return TestingServers::getServers($status, $page, $pageSize, $searchTerm, $emailFilter, $orderBy, $sortOrder, $dateStartFilter, $dateEndFilter, $textSearch);
    }

    /**
     * @param $request
     * @return array|bool|JsonResponse
     */
    public static function listDemoServers($request)
    {
        $config = self::getConfig();

        $status = $request->input('status');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 25);
        $searchTerm = $request->input('search_term');
        $orderBy = $request->input('order_by');
        $sortOrder = $request->input('sort_order');
        if (!empty($sortOrder)) {
            $check = self::checkSortOrder($sortOrder);
            if ($check !== true) {
                return $check;
            }
        }

        if (!empty($searchTerm)) {
            $check = self::checkStringLength($searchTerm, $config['search_max_length']);
            if ($check !== true) {
                return $check;
            }
        }

        if (!empty($orderBy)) {
            // check if field is valid
            $check = self::checkSortFields($config['allowed_sort_fields'], $orderBy);
            if ($check !== true) {
                return $check;
            }
        }

        return DemoServers::getServers($status, $page, $pageSize, $searchTerm, $orderBy, $sortOrder);
    }

    /**
     * @param $json
     * @return JsonResponse
     */
    public static function newServer($json): JsonResponse
    {
        $config = self::getConfig();

        $mode = self::maintenanceMode();
        if ($mode == "ON") {
            return ApiService::sendJsonResponse('The API is in maintenance mode', 400);
        }

        $requiredFields = $config['new_server_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        // Check if DNS is already attached to a running server
        $check = TestingServers::checkDuplicate($json['dns']);
        if ($check > 0) {
            // error
            return ApiService::sendJsonResponse("DNS record {$json['dns']} is already in use.", 400);
        }

        // Validate URL
        if(filter_var($json['dns'], FILTER_VALIDATE_URL)) {
            return ApiService::sendJsonResponse("DNS {$json['dns']} must not be a URL.", 400);
        }

        // Validate alphanumeric
        if (!preg_match('/^[a-zA-Z0-9-]+$/', $json['dns'])) {
            return ApiService::sendJsonResponse("DNS {$json['dns']} contains non alphanumeric characters.", 400);
        }

        // If a server was recreated we will require a 5-minute delay regardless
        $check = self::checkTimeDelay($json['dns']);

        $cloneFlag = false;

        if (isset($json['clone_flag'])) {
            if ($json['clone_flag'] == "Yes") {
                $cloneFlag = true;
            }
        }

        $bypassSleepMode = false;
        if (isset($json['bypass_sleep_mode'])) {
            if ($json['bypass_sleep_mode'] == "Yes") {
                $bypassSleepMode = true;
            }
        }

        $sshFlag = false;
        if (isset($json['ssh_flag'])) {
            if ($json['ssh_flag'] == "Yes") {
                $sshFlag = true;
            }
        }

        $backupFlag = false;
        if (isset($json['daily_snapshot_flag'])) {
            if ($json['daily_snapshot_flag'] == "Yes") {
                $backupFlag = true;
            }
        }

        $sentryDns = "";
        if (isset($json['sentry_dns'])) {
            $sentryDns = $json['sentry_dns'];
        }

        if ($check > 0) {
            // delay
            $server = TestingServers::newServer($json['ticket'], $json['dns'], $json['email'], $json['description'], 'delay', $cloneFlag, $bypassSleepMode, $sshFlag, $backupFlag, $sentryDns);
            DelayDnsLog::newDelay($server->id);
            PendingServerQueue::newPendingServer($server->id, 'delay');
        } else {
            $server = TestingServers::newServer($json['ticket'], $json['dns'], $json['email'], $json['description'], 'pending', $cloneFlag, $bypassSleepMode, $sshFlag, $backupFlag, $sentryDns);
            PendingServerQueue::newPendingServer($server->id, 'pending');
        }

        EventLog::newEvent($server->id, "A new server has been requested.", 'NewServer');
        return ApiService::sendJsonResponse("The job build was received.", 200);
    }

    /**
     * @param $json
     * @return JsonResponse
     */
    public static function newDemoServer($json): JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['new_demo_server_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        $dns = self::generateRandomCode(8);
        $server = DemoServers::newServer($dns, $json['email'], "CP Base Demo Server {$dns}", 'pending');
        DemoServersQueue::newPendingServer($server->id, 'pending');

        return ApiService::sendJsonResponse("The job build was received.", 200);
    }

    /**
     * @param $json
     * @return JsonResponse
     */
    public static function newImportedServer($json): JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['new_server_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        $server = TestingServers::newImportedServer($json['ticket'], $json['dns'], $json['email'], $json['description']);

        EventLog::newEvent($server->id, "A new server has been requested.", 'NewServer');
        $log = PendingServerQueue::newImportedPendingServer($server->id);
        return ApiService::sendJsonResponse("The server was added.", 200);
    }

    /**
     * @param $id
     * @param $action
     * @param $json
     * @return JsonResponse
     */
    public static function modifyServer($id, $action, $json): JsonResponse
    {
        switch ($action) {
            case "ip":
                return self::modifyServerIp($id, $json);
            case "privateip":
                return self::modifyPrivateServerIp($id, $json);
            case "instance":
                return self::modifyServerInstance($id, $json);
            case "restore":
                return self::restoreSnapShot($id);
            case "reboot":
                return self::rebootServer($id);
            case "hardreboot":
                return self::hardRebootServer($id);
            case "resetdns":
                return self::resetDns($id);
            case "activity":
                return self::logActivity($id, $json);
            case "renewssl":
                return self::renewSSL($id);
            case "ssl":
                return self::recordZeroSSL($id, $json);
            case "backup":
                return self::backupInstance($id);
            case "rebuild":
                return self::rebuildServer($id);
            case "unittesting":
                return self::unitTesting($id, $json);
            case "security":
                return self::modifyTestingServerSecurity($id, $json);
        }
        return ApiService::sendJsonResponse("The sub action was unknown.", 400);
    }

    /**
     * @param $id
     * @param $action
     * @param $json
     * @return JsonResponse
     */
    public static function modifyDemoServer($id, $action, $json): JsonResponse
    {
        switch ($action) {
            case "ip":
                return self::modifyDemoServerIp($id, $json);
            case "instance":
                return self::modifyDemoServerInstance($id, $json);
            case "security":
                return self::modifyDemoServerSecurity($id, $json);
        }
        return ApiService::sendJsonResponse("The sub action was unknown.", 400);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public static function restoreSnapShot($id) : JsonResponse
    {
        $server = TestingServers::getSingleServer($id);
        foreach ($server as $s) {
            // check server status
            if ($s->status != "terminated") {
                return ApiService::sendJsonResponse("The server status must be terminated.", 400);
            }

            // check if server has a snapshot
            if (is_null($s->snapshotID)) {
                return ApiService::sendJsonResponse("The server must have an existing snapshot.", 400);
            }

            // create a restore queue
            RestoreSnapshotServerQueue::addSnapshotToQueue($id, 'pending');
            PendingServerQueue::updateServerStatus($id, 'building');
            TestingServers::updateCurrentStatus($s->id, 'restoring');

            return ApiService::sendJsonResponse('The server has been sent to the queue.', 200);
        }
        return ApiService::sendJsonResponse('The server was not valid.', 400);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    public static function unitTesting($id, $json) : JsonResponse
    {
        $unitID = "";
        $record = UnitTests::getUnitTestingRecord($id);
        foreach ($record as $r) {
            $unitID = $r->id;
        }

        if ($unitID == "") {
            // new
            UnitTests::saveUnitTestRecord('new', $id, $json, null);
        } else {
            // update
            UnitTests::saveUnitTestRecord('update', $id, $json, $unitID);
        }
        return ApiService::sendJsonResponse('The Unit Test record was updated.', 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public static function rebuildServer($id) : JsonResponse
    {
        $server = TestingServers::getSingleServer($id);
        foreach ($server as $s) {
            /* Delete Server */
            $check = DnsService::checkIfDnsIsProtected($s->dns);
            if ($check === false) {
                // error
                EventLog::newEvent($id, "{$s->dns} is a protected sub domain. The delete has been halted.", "DeleteDNS");
                return ApiService::sendJsonResponse("{$s->dns} is a protected sub domain. The delete has been halted.", 400);
            }

            $cloneFlag = false;
            $bypassSleepMode = false;
            $sshFlag = false;
            $backupFlag = false;

            if ($s->clone_flag == "1") {
                $cloneFlag = true;
            }
            if ($s->bypass_sleep_mode == "1") {
                $bypassSleepMode = true;
            }
            if ($s->ssh_flag == "1") {
                $sshFlag = true;
            }
            if ($s->daily_snapshot_flag == "1") {
                $backupFlag = true;
            }

            DeleteTestingServer::newDeletServer($id);
            PendingServerQueue::updateServerStatus($s->queueID, 'terminated');
            TestingServers::updateCurrentStatus($s->id, 'terminated');

            $rebuildServer = TestingServers::newServer($s->ticket, $s->dns, $s->email, $s->description, 'delay', $cloneFlag, $bypassSleepMode, $sshFlag, $backupFlag, $s->sentry_dns);
            DelayDnsLog::newDelay($rebuildServer->id);
            PendingServerQueue::newPendingServer($rebuildServer->id, 'delay');

            return ApiService::sendJsonResponse("The server will be rebuilt shortly. You will receive an email when the server rebuild is complete.", 200);
        }
        return ApiService::sendJsonResponse("The server failed to rebuild.", 400);
    }

    public static function backupInstance($id): JsonResponse
    {
        SnapshotQueue::backup($id);
        return ApiService::sendJsonResponse('The backup request was accepted.', 200);
    }

    public static function renewSSL($id) : JsonResponse
    {
        RenewSslQueue::renewSSL($id);
        return ApiService::sendJsonResponse('The SSL request was accepted.', 200);
    }

    /**
     * @param $id
     * @param $json
     * @return bool|JsonResponse
     */
    public static function logActivity($id, $json)
    {
        $config = self::getConfig();
        $requiredFields = $config['log_server_activity_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        EventLog::newEvent($id, $json['message'], $json["section"]);
        return ApiService::sendJsonResponse("The server activity was recorded.", 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public static function rebootServer($id): JsonResponse
    {
        $server = TestingServers::getSingleServer($id);
        foreach ($server as $s) {
            $result = AwsService::rebootInstance($s->instanceID);
            if ($result === false) {
                return ApiService::sendJsonResponse("The server failed to reboot.", 400);
            }
            EventLog::newEvent($id, "The server was rebooted.", 'Reboot');
            return ApiService::sendJsonResponse("The server is rebooting.", 200);
        }
        return ApiService::sendJsonResponse("The server failed to reboot.", 400);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public static function hardRebootServer($id): JsonResponse
    {
        $server = TestingServers::getSingleServer($id);
        foreach ($server as $s) {
            HardRebootServerQueue::newHardReboot($s->instanceID);
            return ApiService::sendJsonResponse("The server will hard reboot shortly. Please allow 15 minutes for the process to complete.", 200);
        }
        return ApiService::sendJsonResponse("The server failed to hard reboot.", 400);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public static function resetDns($id): JsonResponse
    {
        RefreshDnsQueue::newRefreshDns($id);
        return ApiService::sendJsonResponse("The server DNS will update shortly. Please allow 15 minutes for the process to complete.", 200);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    private static function modifyServerIp($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['modify_server_ip_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        TestingServers::updateIpAddress($id, $json['ip_address']);
        EventLog::newEvent($id, "The IP Address {$json['ip_address']} has been assigned.", 'IPAddress');
        return ApiService::sendJsonResponse("The IP Address was updated.", 200);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    private static function modifyPrivateServerIp($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['modify_server_ip_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        TestingServers::updatePrivateIpAddress($id, $json['ip_address']);
        EventLog::newEvent($id, "The IP Address {$json['ip_address']} has been assigned.", 'IPAddress');
        return ApiService::sendJsonResponse("The IP Address was updated.", 200);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    private static function modifyDemoServerIp($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['modify_server_ip_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        DemoServers::updateIpAddress($id, $json['ip_address']);
        return ApiService::sendJsonResponse("The IP Address was updated.", 200);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    private static function modifyServerInstance($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['modify_server_instance_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        TestingServers::updateInstance($id, $json['instance']);
        EventLog::newEvent($id, "The instance ID {$json['instance']} has been assigned.", 'Instance');
        return ApiService::sendJsonResponse("The instance was updated.", 200);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    private static function modifyDemoServerInstance($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['modify_server_instance_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        DemoServers::updateInstance($id, $json['instance']);
        return ApiService::sendJsonResponse("The instance was updated.", 200);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    private static function modifyTestingServerSecurity($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['modify_server_security_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        TestingServers::updateSecurityGroup($id, $json['security']);
        return ApiService::sendJsonResponse("The instance was updated.", 200);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    private static function modifyDemoServerSecurity($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['modify_server_security_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        DemoServers::updateSecurityGroup($id, $json['security']);
        return ApiService::sendJsonResponse("The instance was updated.", 200);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    public static function updateServerStatus($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['update_server_status_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        $stat = ServerStatus::newServerStatus($id, $json['status']);
        if (is_object($stat)) {
            // return success
            $server = TestingServers::getSingleServer($id);
            foreach ($server as $s) {

                // The below will update other statuses as the server is being built.
                TestingServers::updateCurrentStatus($s->id, $json['status']);

                if ($json['status'] == "Error") {
                    PendingServerQueue::updateServerStatus($s->queueID, 'error');
                    // we override and use a static status
                    TestingServers::updateCurrentStatus($s->id, 'error');
                    EventLog::newEvent($id, "The server had an error.", 'Error');
                }

                if ($json['status'] == "Deployed") {
                    PendingServerQueue::updateServerStatus($s->queueID, 'deployed');
                    // we override and use a static status
                    TestingServers::updateCurrentStatus($s->id, 'deployed');
                    EventLog::newEvent($id, "The server has been deployed", 'Deployed');
                }
                return ApiService::sendJsonResponse("The status was updated.", 200);
            }
        } else {
            // return error
            return ApiService::sendJsonResponse("The status failed to update.", 400);
        }
        return ApiService::sendJsonResponse("The status failed to update.", 400);
    }

    /**
     * @param $id
     * @param $json
     * @return JsonResponse
     */
    public static function updateDemoServerStatus($id, $json) : JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['update_server_status_fields'];

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        $stat = DemoServerStatus::newServerStatus($id, $json['status']);
        if (is_object($stat)) {
            // return success
            $server = DemoServers::getSingleServer($id);
            foreach ($server as $s) {

                // The below will update other statuses as the server is being built.
                DemoServers::updateCurrentStatus($s->id, $json['status']);

                if ($json['status'] == "Error") {
                    DemoServersQueue::updateServerStatus($s->queueID, 'error');
                    // we override and use a static status
                    DemoServers::updateCurrentStatus($s->id, 'error');
                }

                if ($json['status'] == "Deployed") {
                    DemoServersQueue::updateServerStatus($s->queueID, 'deployed');
                    // we override and use a static status
                    DemoServers::updateCurrentStatus($s->id, 'deployed');
                }
                return ApiService::sendJsonResponse("The status was updated.", 200);
            }
        } else {
            // return error
            return ApiService::sendJsonResponse("The status failed to update.", 400);
        }
        return ApiService::sendJsonResponse("The status failed to update.", 400);
    }

    /**
     * @param $instanceID
     * @param $sleep
     * @return string
     */
    private static function getInstanceIp($instanceID, $sleep): string
    {
        sleep($sleep);
        $instance = AwsService::describeInstance($instanceID);
        if ($instance === false) {
            return $instance;
        }
        $publicIp = "";
        if (isset($instance['Reservations'][0]['Instances'][0]['PublicIpAddress'])) {
            $publicIp = $instance['Reservations'][0]['Instances'][0]['PublicIpAddress'];
        }
        return $publicIp;
    }

    /**
     * @param $type
     * @param $id
     * @param $instanceID
     * @param $dns
     * @param $domain
     * @param $data
     * @return void
     */
    private static function enableDns($type, $id, $instanceID, $dns, $domain, $data): void
    {
        $dns = DnsService::createDns($type, $dns, $domain, $data);
        if ($dns === false) {
            EventLog::newEvent($id, "Error creating DNS A record for {$dns}.{$domain}. Please submit a request to have instance ID {$instanceID} reviewed in AWS.", 'DNS');
            return;
        }
    }

    private static function getHostedZoneId($domain)
    {
        if ($domain == "virtualjobshadow.com") {
            return env('EXPLORE_HOSTED_ZONE_ID');
        } elseif ($domain == "vjsjunior.com") {
            return env('JR_HOSTED_ZONE_ID');
        } elseif ($domain == "vjsjunior-dev.com") {
            return env('JR_HOSTED_ZONE_ID');
        } elseif ($domain == "virtualjobshadow-dev.com") {
            return env('EXPLORE_HOSTED_ZONE_ID');
        } else {
            return false;
        }
    }

    /**
     * @param $dns
     * @return bool
     */
    public static function deleteDns($dns) : bool
    {
        $devLegacyVirtualJobShadowCom = env('dev_legacy_virtualjobshadow_com');
        $devLegacyPlannerVirtualJobShadowCom = env('dev_legacy_planner_virtualjobshadow_com');
        $devLegacyAuthVirtualJobShadowCom = env('dev_legacy_auth_virtualjobshadow_com');
        $devLegacyAdminVirtualJobShadowCom = env('dev_legacy_admin_virtualjobshadow_com');
        $devLegacyVjsJuniorCom = env('dev_legacy_vjsjunior_com');

        self::deleteRoute53($dns . ".dev-legacy" , "virtualjobshadow.com", $devLegacyVirtualJobShadowCom);
        self::deleteRoute53($dns . ".dev-legacy.admin", "virtualjobshadow.com", $devLegacyAdminVirtualJobShadowCom);
        self::deleteRoute53($dns . ".dev-legacy.auth", "virtualjobshadow.com", $devLegacyAuthVirtualJobShadowCom);
        self::deleteRoute53($dns . ".dev-legacy.planner", "virtualjobshadow.com", $devLegacyPlannerVirtualJobShadowCom);
        self::deleteRoute53($dns . ".dev-legacy", "vjsjunior.com", $devLegacyVjsJuniorCom);

        return true;
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public static function deleteServer($id, $json): JsonResponse
    {
        $config = self::getConfig();
        $requiredFields = $config['delete_server_fields'];

        // non-breaking change
        if (empty($json)) {
            $json = ["email" => "default@pathful.com"];
        }

        $check = self::checkContent($json);
        if ($check !== true){
            return ApiService::sendJsonResponse('JSON empty', 400);
        }

        $fieldCheck = self::requiredFields($requiredFields, $json);
        if ($fieldCheck !== true) {
            return $fieldCheck;
        }

        $server = TestingServers::getSingleServer($id);
        foreach ($server as $s) {
            $check = DnsService::checkIfDnsIsProtected($s->dns);
            if ($check === false) {
                // error
                EventLog::newEvent($id, "{$s->dns} is a protected sub domain. The delete has been halted.", "DeleteDNS");
                return ApiService::sendJsonResponse("{$s->dns} is a protected sub domain. The delete has been halted.", 400);
            }

            DeleteTestingServer::newDeletServer($id);
            PendingServerQueue::updateServerStatus($s->queueID, 'terminated');
            TestingServers::updateCurrentStatus($s->id, 'terminated');
            EventLog::newEvent($id, "{$json['email']} has requested to delete this server.", 'DeleteServer');
            return ApiService::sendJsonResponse("{$s->dns} has been deleted.", 200);
        }
        return ApiService::sendJsonResponse("Unknown error deleting server.", 400);
    }

    /**
     * @param $id
     * @param $logContent
     * @return JsonResponse
     */
    public static function collectS3Logs($id, $logContent): JsonResponse
    {
        LogFiles::saveNewLog($id, $logContent);
        TestingServers::setLogIndicator($id);
        return ApiService::sendJsonResponse("The build logs was saved to AWS S3.", 200);
    }

    /**
     * @param $id
     * @return JsonResponse|void
     */
    public static function streamAwsLog($id)
    {
        $dir = "lumen";
        $server = TestingServers::getSingleServer($id);
        foreach ($server as $s) {
            if (is_null($s->log_filename)) {
                return ApiService::sendJsonResponse("The AWS log file does not exist.", 400);
            }
            $s3_filename = $s->log_filename;

        }

        $result = AwsService::getObject('strivven-jenkins-console-logs', $dir . "/" . $s3_filename);
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: Binary");
        header("Content-Disposition: attachment; filename=\"{$s3_filename}\"");
        echo $result['Body'];
        die;
    }

    public static function deleteRoute53($subDomain, $domain, $hostedZoneId): bool
    {
        /*
        $subDomain = $request->query->get('sub_domain');
        $domain = $request->query->get('domain');
        $hostedZoneId = $request->query->get('hosted_zone_id');
        */

        $query = AwsService::queryRecord($subDomain, $domain, $hostedZoneId);

        $publicIp = "";
        if (isset($query['ResourceRecordSets'][0]['ResourceRecords'][0]['Value'])) {
            $publicIp = $query['ResourceRecordSets'][0]['ResourceRecords'][0]['Value'];
        }

        if ($publicIp != "") {
            $deleteDns = AwsService::deleteRecord($subDomain, $domain, $publicIp, $hostedZoneId);
            if ($deleteDns !== false) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function checkTimeDelay($name)
    {
        // locate any possible past servers with the same name
        return TestingServers::checkTimeDelay($name);
    }

    /**
     * @param $ip
     * @return string
     */
    public static function checkIpActive($ip): string
    {
        // This will return a string to bash not a boolean
        $total = TestingServers::checkIpActive($ip);
        if ($total == "0") {
            return "false";
        } else {
            return "true";
        }
    }

    /**
     * @param $length
     * @return string
     */
    private static function generateRandomCode($length): string
    {
        $chars = "abcdefghijkmnopqrstuvwxyz023456789";
        srand((double) microtime() * 1000000);

        $i = 0;
        $string = '';

        while ($i <= $length)
        {
            $num = rand() % 33;
            $tmp = substr($chars, $num, 1);
            $string = $string . $tmp;
            $i++;
        }

        return $string;
    }
}
