<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DelayDnsLog extends Model
{
    protected $table = "delay_dns_log";
    protected $fillable = [
        'id',
        'ticketID',
        'time_to_live',
        'status',
        'date_created',
        'date_updated'
    ];
    public $timestamps = false;

    public static function newDelay($id)
    {
        $date = date("Y-m-d H:i:s");
        $newDelayDate = date("U",strtotime($date." +5 minutes"));

        $delay = new self();
        $delay->ticketID = $id;
        $delay->time_to_live = $newDelayDate;
        $delay->status = "pending";
        $delay->created_at = new \DateTime();
        $delay->date_updated = new \DateTime();
        $delay->save();

        return $delay;
    }

    public static function getDelayData()
    {
        $status = "pending";

        return self::from('delay_dns_log as d')
            ->select(
                'd.id',
                'd.ticketID',
                'd.time_to_live',
                'q.id AS queueID'
            )
            ->join('pending_server_queue as q', function ($innerJoin) {
                $innerJoin->on('q.ticketID', '=', 'd.ticketID');
            })
            ->where(function ($statusFilter) use ($status) {
                $statusFilter->where('d.status', '=', $status);
            })
            ->get();
    }

    public static function updateDelayStatus($id, $status)
    {
        $server = self::find($id);
        $server->status = $status;
        $server->save();
        return $server;
    }
}
