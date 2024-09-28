<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class EventLog extends Model
{
    protected $table = "event_log";
    protected $fillable = [
        'id',
        'ticketID',
        'section',
        'event',
        'created_time',
        'created_at'
    ];
    public $timestamps = false;

    public static function newEvent($ticket, $event, $section)
    {
        $e = new self();
        $e->ticketID = $ticket;
        $e->event = $event;
        $e->section = $section;
        $e->created_time = new \DateTime();
        $e->created_at = new \DateTime();
        $e->save();

        return $e;
    }
}
