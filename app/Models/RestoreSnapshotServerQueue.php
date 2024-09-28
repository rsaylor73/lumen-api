<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RestoreSnapshotServerQueue extends Model
{
    protected $table = "restore_snapshot_server_queue";
    protected $fillable = [
        'id',
        'ticketID',
        'status',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function addSnapshotToQueue($ticketID, $status)
    {
        $queue = new self();
        $queue->ticketID = $ticketID;
        $queue->status = $status;
        $queue->created_at = new \DateTime();
        $queue->updated_at = new \DateTime();
        $queue->save();
        return $queue;
    }

    public static function getPendingServerList($status)
    {
        return RestoreSnapshotServerQueue::from('restore_snapshot_server_queue as p')
            ->select(
                'p.id',
                'p.status',
                'p.ticketID',
                't.ticket',
                't.dns',
                't.email',
                't.clone_flag',
                't.created_at',
                't.snapshotID',
                't.instanceID',
                't.sentry_dns'
            )
            ->leftJoin('testing_servers as t', function($leftJoin)
            {
                $leftJoin->on('t.id', '=', 'p.ticketID');
            })
            ->where('p.status', $status)
            ->get()
            ;
    }

    public static function updateServerStatus($id, $status)
    {
        $server = self::find($id);
        $server->status = $status;
        $server->save();
        return $server;
    }
}
