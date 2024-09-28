<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ServerStatus extends Model
{
    protected $table = "server_status";
    protected $fillable = [
        'id',
        'ticketID',
        'status',
        'created_at'
    ];
    public $timestamps = false;

    public static function newServerStatus($ticket, $status)
    {
        $stat = new self();
        $stat->ticketID = $ticket;
        $stat->status = $status;
        $stat->created_at = new \DateTime();
        $stat->save();
        return $stat;
    }
}