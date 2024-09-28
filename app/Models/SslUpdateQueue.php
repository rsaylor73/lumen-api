<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SslUpdateQueue extends Model
{
    protected $table = "ssl_update_queue";
    protected $fillable = [
        'id',
        'ticketID',
        'status',
        'sslID',
        'csr',
        'crt',
        'key',
        'ca_bundle',
        'date_created',
        'date_updated'
    ];
    public $timestamps = false;

    public static function newSslQueueRequest($id) {
        $ssl = new self();
        $ssl->ticketID = $id;
        $ssl->status = "Pending";
        $ssl->date_created = new \DateTime();
        $ssl->date_updated = new \DateTime();
        $ssl->save();

        return $ssl;
    }

    public static function pullPendingRequests()
    {
        $query = self::from('ssl_update_queue as s')
            ->select(
                's.id',
                't.id AS ticketID',
                't.dns',
                't.email',
                't.created_at',
                't.ip_address'
            )
        ;

        $query->join('testing_servers as t', function ($innerJoin) {
            $innerJoin->on('t.id', '=', 's.ticketID');
        });

        $query->where(function ($filter) {
            $filter->where('s.status', '=', 'Pending');
        });

        return $query->get();
    }

    public static function markDone($id)
    {
        $queue = self::find($id);
        $queue->status = "Complete";
        $queue->save();

        return true;
    }
}
