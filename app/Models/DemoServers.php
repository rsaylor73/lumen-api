<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DemoServers extends Model
{
    protected $table = "demo_servers";
    protected $fillable = [
        'id',
        'dns',
        'email',
        'current_status',
        'ip_address',
        'instanceID',
        'security_groupID',
        'description',
        'created_at',
        'termination_date',
        'updated_at'
    ];
    public $timestamps = false;

    public static function getServerDetails($id)
    {
        return self::find($id);
    }

    public static function getServers($status, $page, $pageSize, $searchTerm, $orderBy, $sortOrder)
    {
        $query = self::from('demo_servers as d')
            ->select(
                'd.id',
                'd.dns',
                'd.email',
                'd.ip_address',
                'd.instanceID',
                'd.description',
                'd.current_status',
                'q.status',
                'd.created_at',
                'd.termination_date',
                'd.updated_at'
            );
        $query->join('demo_servers_queue as q', function ($innerJoin) {
            $innerJoin->on('q.demoID', '=', 'd.id');
        });
        $query->where(function ($statusFilter) use ($status, $searchTerm) {
            if (!empty($status)) {
                $status = str_replace(' ', '', $status);
                $status = explode(',', $status);
                if (is_array($status)) {
                    $statusFilter->whereIn('d.current_status',$status);
                } else {
                    $statusFilter->where('d.current_status', '=', $status);
                }
            }
            if (!empty($searchTerm)) {
                $statusFilter->where(DB::raw("CONCAT(t.dns, ' ', t.description)"), 'LIKE', "%".$searchTerm."%");
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

    public static function newServer($dns, $email, $description, $status)
    {
        $deleteDate = new \DateTime();
        $deleteDate->add(new \DateInterval('P3D'));

        $server = new self();
        $server->dns = $dns;
        $server->email = $email;
        $server->ip_address = "0.0.0.0";
        $server->instanceID = $status;
        $server->description = $description;
        $server->current_status = "pending";
        $server->created_at = new \DateTime();
        $server->termination_date = $deleteDate;
        $server->updated_at = new \DateTime();
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

    public static function getSingleServer($id)
    {
        return self::from('demo_servers as t')
            ->select(
                't.id',
                't.dns',
                't.email',
                't.ip_address',
                't.instanceID',
                't.description',
                'q.id AS queueID',
                'q.status',
                't.created_at',
                't.updated_at'
            )
            ->join('demo_servers_queue as q', function ($innerJoin) {
                $innerJoin->on('q.demoID', '=', 't.id');
            })
            ->where(function ($idFilter) use ($id) {
                $idFilter->where('t.id', '=', $id);
            })
            ->get();
    }

    public static function getServerByInstanceId($instanceID)
    {
        return self::from('demo_servers as t')
            ->select(
                't.id',
                't.dns',
                't.email',
                't.ip_address',
                't.instanceID',
                't.description',
                'q.id AS queueID',
                'q.status',
                't.created_at',
                't.updated_at'
            )
            ->join('demo_servers_queue as q', function ($innerJoin) {
                $innerJoin->on('q.demoID', '=', 't.id');
            })
            ->where(function ($idFilter) use ($instanceID) {
                $idFilter->where('t.instanceID', '=', $instanceID);
            })
            ->get();
    }

    public static function updateCurrentStatus($id, $status)
    {
        $server = self::find($id);
        $server->current_status = strtolower($status);
        $server->save();
        return $server;
    }

    public static function getServersReadyToDelete()
    {
        $today = date("Y-m-d");

        return self::from('demo_servers as t')
            ->select(
                't.id',
                't.dns',
                't.instanceID',
                't.security_groupID'
            )
            ->where(function ($filter) use ($today) {
                $filter->where('t.termination_date', '=', $today);
                $filter->where('t.current_status', '=', 'deployed');
            })
            ->get()
        ;
    }

    public static function findByInstanceId($instanceID)
    {
        return self::from('demo_servers as t')
            ->select(
                't.id',
                't.dns',
                't.email',
                't.ip_address',
                't.instanceID',
                't.security_groupID',
                't.description',
                't.created_at',
                't.updated_at'
            )
            ->where(function ($filter) use ($instanceID) {
                $filter->where('t.instanceID', '=', $instanceID);
            })
            ->get();
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

        $query = self::from('demo_servers as t')
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
}
