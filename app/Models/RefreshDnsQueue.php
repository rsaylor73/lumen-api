<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RefreshDnsQueue extends Model
{
    protected $table = "refresh_dns_queue";
    protected $fillable = [
        'id',
        'ticketID',
        'status',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function newRefreshDns($ticketID): RefreshDnsQueue
    {
        $queue = new self();
        $queue->ticketID = $ticketID;
        $queue->status = "pending";
        $queue->created_at = new \DateTime();
        $queue->updated_at = new \DateTime();
        $queue->save();

        return $queue;
    }

    public static function getPendingDnsRefreshList($status)
    {
        return RefreshDnsQueue::from('refresh_dns_queue as p')
            ->select(
                'p.id',
                'p.ticketID',
                't.dns',
                't.ip_address'
            )
            ->join('testing_servers as t', function ($innerJoin) {
                $innerJoin->on('t.id', '=', 'p.ticketID');
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
