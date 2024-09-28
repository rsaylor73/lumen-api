<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RenewSslQueue extends Model
{
    protected $table = "renew_ssl_queue";
    protected $fillable = [
        'id',
        'ticketID',
        'status',
        'log',
        'date_created',
        'date_updated'
    ];
    public $timestamps = false;

    public static function renewSSL($id)
    {
        $ssl = new self();
        $ssl->ticketID = $id;
        $ssl->status = 'pending';
        $ssl->date_created = new \DateTime();
        $ssl->date_updated = new \DateTime();
        $ssl->save();
    }

    public static function pendingSSLRequests()
    {
        return self::from('renew_ssl_queue as q')
            ->select(
                'q.id',
                'q.ticketID',
                't.instanceID',
                't.ip_address',
                't.security_groupID',
                't.email',
                't.dns'
            )
            ->join('testing_servers as t', function ($innerJoin) {
                $innerJoin->on('t.id', '=', 'q.ticketID');
            })
            ->where(function ($filter) {
                $filter->where('q.status', '=', 'pending');
            })
            ->get();
    }

    public static function updateCurrentStatus($id, $status)
    {
        $queue = self::find($id);
        $queue->status = strtolower($status);
        $queue->save();
        return $queue;
    }
}
