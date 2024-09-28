<?php

namespace App\Models\v2;

use Illuminate\Database\Eloquent\Model;

class ServerQueue extends Model
{
    protected $table = "v2_server_queue";
    protected $fillable = [
        'id',
        'testing_serverID',
        'status',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function newServerQueue($testing_serverID, $status): ServerQueue
    {
        $queue = new self();
        $queue->testing_serverID = $testing_serverID;
        $queue->status = $status;
        $queue->created_at = new \DateTime();
        $queue->updated_at = new \DateTime();
        $queue->save();
        return $queue;
    }

    public static function getPendingServerList($status)
    {
        return self::from('v2_server_queue as p')
            ->select(
                'p.id',
                'p.status',
                't.dns',
                't.email',
                't.terraform_fileName',
                't.terraform_variable_string'
            )
            ->leftJoin('v2_testing_servers as t', function($leftJoin)
            {
                $leftJoin->on('t.id', '=', 'p.testing_serverID');
            })
            ->where('p.status', $status)
            ->get()
            ;
    }

    public static function getServerQueueId($testing_serverID)
    {
        return self::from('v2_server_queue as p')
            ->select(
                'p.id'
            )
            ->leftJoin('v2_testing_servers as t', function($leftJoin)
            {
                $leftJoin->on('t.id', '=', 'p.testing_serverID');
            })
            ->where('p.testing_serverID', $testing_serverID)
            ->get()
            ;
    }

    public static function updateStatus($id, $status)
    {
        $queue = self::find($id);
        $queue->status = $status;
        $queue->save();
        return $queue;
    }
}
