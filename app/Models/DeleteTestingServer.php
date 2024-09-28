<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeleteTestingServer extends Model
{
    protected $table = "delete_testing_server";
    protected $fillable = [
        'id',
        'ticketID',
        'status',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function newDeletServer($ticket)
    {
        $pending = new self();
        $pending->ticketID = $ticket;
        $pending->status = 'pending';
        $pending->created_at = new \DateTime();
        $pending->updated_at = new \DateTime();
        $pending->save();
        return $pending;
    }

    public static function getDeleteServers($status)
    {
        return DeleteTestingServer::from('delete_testing_server as d')
            ->select(
                'd.id',
                'd.status',
                'd.ticketID',
                't.dns',
                't.email',
                't.created_at',
                't.security_groupID'
            )
            ->leftJoin('testing_servers as t', function($leftJoin)
            {
                $leftJoin->on('t.id', '=', 'd.ticketID');
            })
            ->where('d.status', $status)
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
