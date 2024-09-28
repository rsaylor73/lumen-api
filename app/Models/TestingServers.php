<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestingServers extends Model
{
    protected $table = "testing_servers";
    protected $fillable = [
        'id',
        'ticket',
        'dns',
        'email',
        'current_status',
        'ip_address',
        'ip_private_address',
        'log_available',
        'instanceID',
        'security_groupID',
        'description',
        'snapshotID',
        'clone_flag',
        'ssh_flag',
        'bypass_sleep_mode',
        'daily_snapshot_flag',
        'sentry_dns',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function getServerDetails($id)
    {
        return self::find($id);
    }

    public static function newServer($ticket, $dns, $email, $description, $status, $cloneFlag, $bypassSleepMode, $sshFlag, $backupFlag, $sentryDns)
    {
        $server = new self();
        $server->ticket = $ticket;
        $server->dns = $dns;
        $server->email = $email;
        $server->ip_address = "0.0.0.0";
        $server->ip_private_address = "0.0.0.0";
        $server->instanceID = $status;
        $server->description = $description;
        $server->clone_flag = $cloneFlag;
        $server->bypass_sleep_mode = $bypassSleepMode;
        $server->ssh_flag = $sshFlag;
        $server->daily_snapshot_flag = $backupFlag;
        if ($sentryDns != "") {
            $server->sentry_dns = $sentryDns;
        }
        $server->created_at = new \DateTime();
        $server->updated_at = new \DateTime();
        $server->save();

        return $server;
    }

    public static function getSingleServer($id)
    {
        return self::from('testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.ip_address',
                't.ip_private_address',
                't.instanceID',
                't.description',
                'q.id AS queueID',
                'q.status',
                'l.filename AS log_filename',
                't.snapshotID',
                't.clone_flag',
                't.bypass_sleep_mode',
                't.daily_snapshot_flag',
                't.ssh_flag',
                't.sentry_dns',
                't.created_at',
                't.updated_at'
            )
            ->join('pending_server_queue as q', function ($innerJoin) {
                $innerJoin->on('q.ticketID', '=', 't.id');
            })
            ->leftJoin('log_files as l', function ($leftJoinLogFiles) {
                $leftJoinLogFiles->on('l.ticketID', '=', 't.id');
            })
            ->where(function ($idFilter) use ($id) {
                $idFilter->where('t.id', '=', $id);
            })
            ->get();
    }

    public static function getServerByInstanceId($instanceID)
    {
        return self::from('testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.ip_address',
                't.ip_private_address',
                't.instanceID',
                't.description',
                'q.id AS queueID',
                'q.status',
                'l.filename AS log_filename',
                't.snapshotID',
                't.clone_flag',
                't.bypass_sleep_mode',
                't.ssh_flag',
                't.sentry_dns',
                't.created_at',
                't.updated_at'
            )
            ->join('pending_server_queue as q', function ($innerJoin) {
                $innerJoin->on('q.ticketID', '=', 't.id');
            })
            ->leftJoin('log_files as l', function ($leftJoinLogFiles) {
                $leftJoinLogFiles->on('l.ticketID', '=', 't.id');
            })
            ->where(function ($idFilter) use ($instanceID) {
                $idFilter->where('t.instanceID', '=', $instanceID);
            })
            ->get();
    }

    public static function getServers($status, $page, $pageSize, $searchTerm, $emailFilter, $orderBy, $sortOrder, $dateStartFilter, $dateEndFilter, $textSearch)
    {
        $today = date("Y-m-d");

        $query = self::from('testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.ip_address',
                't.ip_private_address',
                't.instanceID',
                't.snapshotID',
                't.description',
                't.current_status',
                'q.status',
                't.log_available',
                't.sentry_dns',
                't.created_at',
                't.updated_at',
                DB::raw("DATEDIFF('$today', t.created_at) AS days")
            );
        $query->join('pending_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.ticketID', '=', 't.id');
        });
        $query->where(function ($filter) use ($status, $searchTerm, $emailFilter, $dateStartFilter, $dateEndFilter, $textSearch) {
            if (!empty($status)) {
                $status = str_replace(' ', '', $status);
                $status = explode(',', $status);
                if (is_array($status)) {
                    $filter->whereIn('q.status',$status);
                } else {
                    $filter->where('q.status', '=', $status);
                }
            }

            if (!is_null($dateStartFilter) && !is_null($dateEndFilter)) {
                $filter->whereBetween('t.created_at', [$dateStartFilter, $dateEndFilter]);
            }

            //if (!empty($searchTerm)) {
            //    $statusFilter->where(DB::raw("CONCAT(t.ticket, ' ', t.dns, ' ', t.description)"), 'LIKE', "%".$searchTerm."%");
            //}

            if (!empty($textSearch)) {
                $defaultStatus = ['deployed','terminated','shutdown', 'building'];
                $filter->where(DB::raw("CONCAT(t.ticket, ' ', t.dns, ' ', t.description)"), 'LIKE', "%".$textSearch."%");
                $filter->whereIn('q.status', $defaultStatus);
            }

            if (!empty($emailFilter)) {
                $filter->where('t.email', 'like', "%{$emailFilter}%");
            }
        });

        if (!empty($orderBy)) {
            if ($sortOrder == "") {
                $sortOrder = "desc";
            }
            $query->orderBy($orderBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $total = $query->count();
        $result = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get();

        return
            [
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize,
                    'last_page' => ceil($total / $pageSize)
                ],
                'data' => $result,
            ];
    }

    public static function updateCurrentStatus($id, $status)
    {
        $server = self::find($id);
        $server->current_status = strtolower($status);
        $server->save();
        return $server;
    }

    public static function updateIpAddress($id, $ipAddress)
    {
        $server = self::find($id);
        $server->ip_address = $ipAddress;
        $server->save();
        return $server;
    }

    public static function updatePrivateIpAddress($id, $ipAddress)
    {
        $server = self::find($id);
        $server->ip_private_address = $ipAddress;
        $server->save();
        return $server;
    }

    public static function updateInstance($id, $instance)
    {
        $server = self::find($id);
        $server->instanceID = $instance;
        $server->save();
        return $server;
    }

    public static function updateSecurityGroup($id, $security)
    {
        $server = self::find($id);
        $server->security_groupID = $security;
        $server->save();
        return $server;
    }

    public static function updateSnapshotId($id, $snapshotID)
    {
        $server = self::find($id);
        $server->snapshotID = $snapshotID;
        $server->save();
        return $server;
    }

    public static function setLogIndicator($id)
    {
        $server = self::find($id);
        $server->log_available = true;
        $server->save();
        return $server;
    }

    public static function checkDuplicate($dns)
    {
        $status = ['pending', 'delay', 'building', 'deployed'];

        $query = self::from('testing_servers as t')
            ->select(
                't.id'
            );
        $query->join('pending_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.ticketID', '=', 't.id');
        });
        $query->where(function ($filter) use ($dns, $status) {
            $filter->whereIn('q.status', $status);
            $filter->where('t.dns', '=', $dns);
        });

        return $query->count();
    }

    public static function checkTimeDelay($name)
    {
        $query = self::from('testing_servers as t')
            ->select(
                't.id',
                't.created_at'
            );
        $query->where(function ($filter) use ($name) {
            $filter->where('t.dns', '=', $name);
        });

        return $query->count();
    }

    public static function checkIpActive($ip)
    {
        $status = ['deployed', 'shutdown'];

        $query = self::from('testing_servers as t')
            ->select(
                't.ip_address'
            );
        $query->join('pending_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.ticketID', '=', 't.id');
        });
        $query->where(function ($filter) use ($ip, $status) {
            $filter->where('t.ip_address', '=', $ip);
            $filter->whereIn('q.status', $status);
        });
        return $query->count();
    }

    public static function locatePossibleStaleServers($date1, $date2)
    {
        $status = ['deployed'];

        $query = self::from('testing_servers as t')
            ->select(
                't.id',
                't.dns',
                't.email',
                's.id AS staleID',
                't.created_at',
                't.updated_at'
            )
        ;

        $query->join('pending_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.ticketID', '=', 't.id');
        });

        $query->leftJoin('stale_server as s', function ($leftJoin) {
           $leftJoin->on('s.ticketID', '=', 't.id');
        });

        $query->where(function ($filter) use ($date1, $date2, $status) {
            $filter->whereBetween('t.created_at', [$date1, $date2]);
            $filter->whereIn('q.status', $status);
        });

        return $query->get();
    }

    public static function getSslRenewalForQueue()
    {
        $status = ['deployed'];

        $futureDate = new \DateTime();
        $futureDate->modify('+80 day');
        echo "Test future date: " . $futureDate->format('Y-m-d') . "\n\n";

        $query = self::from('testing_servers as t')
            ->select(
                't.id',
                't.dns',
                't.email',
                't.created_at'
            )
        ;

        $query->join('pending_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.ticketID', '=', 't.id');
        });

        $query->where(function ($filter) use ($status) {
            $futureDate = new \DateTime();
            $futureDate->modify('+80 day');

            $filter->where('t.created_at', '=', $futureDate->format('Y-m-d'));
            $filter->whereIn('q.status', $status);
        });

        return $query->get();
    }

    public static function findByInstanceId($instanceID)
    {
        $query = self::from('testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.ip_address',
                't.instanceID',
                't.description',
                't.snapshotID',
                't.security_groupID',
                'q.status',
                'q.id AS queueID',
                't.created_at',
                't.updated_at'
            )
            ->join('pending_server_queue as q', function ($innerJoin) {
                $innerJoin->on('q.ticketID', '=', 't.id');
            })
            ->where(function ($filter) use ($instanceID) {
                $filter->where('t.instanceID', '=', $instanceID);
            })
            ->get()
        ;

        return $query;
    }

    public static function findServersToGoToSleep()
    {
        $date = date("Y-m-d", strtotime('-120 hour'));

        return self::from('testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.ip_address',
                't.instanceID',
                't.description',
                't.snapshotID',
                't.security_groupID',
                'q.status',
                'q.id AS queueID',
                't.created_at',
                't.updated_at'
            )
        ->join('pending_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.ticketID', '=', 't.id');
        })
        ->where(function ($filter) use ($date) {
            $filter->where('t.bypass_sleep_mode', '!=', true);
            $filter->where('t.created_at', '=', $date);
            $filter->where('q.status', '=', 'deployed');
        })
        ->get();
    }

    public static function findServersToTerminate()
    {
        $date = date("Y-m-d", strtotime('-288 hour'));

        return self::from('testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.ip_address',
                't.instanceID',
                't.description',
                't.snapshotID',
                't.security_groupID',
                'q.status',
                'q.id AS queueID',
                't.created_at',
                't.updated_at'
            )
            ->join('pending_server_queue as q', function ($innerJoin) {
                $innerJoin->on('q.ticketID', '=', 't.id');
            })
            ->where(function ($filter) use ($date) {
                $filter->where('t.bypass_sleep_mode', '!=', true);
                $filter->where('t.created_at', '=', $date);
                $filter->where('q.status', '=', 'shutdown');
            })
            ->get();
    }

    public static function findStuckServers()
    {
        $date = date("Y-m-d");
        $status = array('terminated', 'deployed', 'error', 'shutdown');

        return self::from('testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.ip_address',
                't.instanceID',
                't.description',
                't.snapshotID',
                't.security_groupID',
                't.current_status',
                'q.status',
                'q.id AS queueID',
                't.created_at',
                't.updated_at'
            )
            ->join('pending_server_queue as q', function ($innerJoin) {
                $innerJoin->on('q.ticketID', '=', 't.id');
            })
            ->where(function ($filter) use ($date, $status) {
                $filter->where('t.created_at', '<', $date);
                $filter->whereNotIn('q.status', $status);
            })
            ->get();
    }

    public static function findBySleepFlag()
    {
        $query = self::from('testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.ip_address',
                't.instanceID',
                't.description',
                't.snapshotID',
                't.security_groupID',
                'q.status',
                't.created_at',
                't.updated_at'
            )
            ->where(function ($filter) {
                $filter->where('t.bypass_sleep_mode', '=', true);
            })
            ->get()
        ;

        $query->join('pending_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.ticketID', '=', 't.id');
        });

        return $query;
    }

    public static function getServerStats($date1, $date2): array
    {
        if ($date1 == "") {
            $date1 = date('Y-m-d',(strtotime( '-30 day')));
            $date2 = date("Y-m-d");
        } else {
            $date1 = date("Y-m-d", strtotime($date1));
            $date2 = date("Y-m-d", strtotime($date2));
        }

        $query = self::from('testing_servers as t')
            ->select(
                't.dns',
                't.created_at',
                't.email'
            )
            ->where(function ($filter) use ($date1, $date2) {
                $filter->whereBetween('t.created_at', [$date1, $date2]);
            })
        ;

        $total = $query->count();
        $result = $query->get();

        return
            [
                'meta' => [
                    'total' => $total
                ],
                'data' => $result,
            ];
    }

    public static function getDailyServersBackup()
    {
        $query = self::from('testing_servers as t')
            ->select(
                't.id'
            )
            ->where(function ($filter) {
                $filter->where('t.daily_snapshot_flag', '=', true);
                $filter->where('t.current_status', '=', 'deployed');
            })
            ->get()
        ;

        return $query;
    }
}
