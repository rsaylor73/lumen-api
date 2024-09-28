<?php

namespace App\Models\v2;

use Illuminate\Database\Eloquent\Model;

class DeleteDemoServerQueue extends Model
{
    protected $table = "v2_delete_demo_server_queue";
    protected $fillable = [
        'id',
        'demo_serverID',
        'status',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function newDeleteQueue($demo_serverID, $status)
    {
        $queue = new self();
        $queue->demo_serverID = $demo_serverID;
        $queue->status = $status;
        $queue->created_at = new \DateTime();
        $queue->updated_at = new \DateTime();
        $queue->save();
        return $queue;
    }

    public static function getServerList($status)
    {
        return self::from('v2_delete_demo_server_queue as p')
            ->select(
                'p.id',
                'p.demo_serverID',
                'p.status',
                't.dns',
                't.email',
                't.terraform_fileName',
                't.terraform_variable_string'
            )
            ->leftJoin('v2_demo_servers as t', function($leftJoin)
            {
                $leftJoin->on('t.id', '=', 'p.demo_serverID');
            })
            ->where('p.status', $status)
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
