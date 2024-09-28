<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class HardRebootServerQueue extends Model
{
    protected $table = "hard_reboot_server_queue";
    protected $fillable = [
        'id',
        'instanceID',
        'status',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function newHardReboot($instanceID): HardRebootServerQueue
    {
        $queue = new self();
        $queue->instanceID = $instanceID;
        $queue->status = "pending";
        $queue->created_at = new \DateTime();
        $queue->updated_at = new \DateTime();
        $queue->save();

        return $queue;
    }

    public static function getPendingHardRebootList($status)
    {
        return HardRebootServerQueue::from('hard_reboot_server_queue as p')
            ->select(
                'p.id',
                'p.instanceID'
            )
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
