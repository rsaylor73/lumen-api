<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PendingServerQueue extends Model
{
    protected $table = "pending_server_queue";
    protected $fillable = [
        'id',
        'ticketID',
        'status',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function newPendingServer($ticket, $status)
    {
        $pending = new self();
        $pending->ticketID = $ticket;
        $pending->status = $status;
        $pending->created_at = new \DateTime();
        $pending->updated_at = new \DateTime();
        $pending->save();
        return $pending;
    }

    public static function newImportedPendingServer($ticket)
    {
        $pending = new self();
        $pending->ticketID = $ticket;
        $pending->status = 'deployed';
        $pending->created_at = new \DateTime();
        $pending->updated_at = new \DateTime();
        $pending->save();
        return $pending;
    }

    public static function getPendingServerList($status)
    {
        return PendingServerQueue::from('pending_server_queue as p')
            ->select(
                'p.id',
                'p.status',
                't.ticket',
                't.dns',
                't.email',
                't.clone_flag',
                't.ssh_flag',
                't.created_at',
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
