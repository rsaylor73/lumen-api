<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class StaleServer extends Model
{
    protected $table = "stale_server";
    protected $fillable = [
        'id',
        'ticketID',
        'date_added',
        'next_date_check',
        'ssl_renewal',
        'delete_protection',
        'on_report',
        'date_created',
        'date_updated'
    ];

    public $timestamps = false;

    public static function newStaleServer($id)
    {
        $stale = new self();
        $stale->ticketID = $id;
        $stale->date_added = date("Y-m-d");
        $stale->next_date_check = date('Y-m-d', strtotime("+1 month"));
        $stale->on_report = true;
        $stale->date_created = new \DateTime();
        $stale->date_updated = new \DateTime();
        $stale->save();

        return $stale;
    }

    public static function emailReport()
    {
        return self::from('stale_server as s')
            ->select(
                's.id',
                't.dns',
                't.created_at',
                't.email'
            )
            ->join('testing_servers as t', function ($innerJoin) {
                $innerJoin->on('t.id', '=', 's.ticketID');
            })
            ->where(function ($filter) {
                $filter->where('s.on_report', '=', true);
            })
            ->get()
        ;
    }

    public static function setReportSent($id)
    {
        $stale = self::find($id);
        $stale->on_report = false;
        $stale->save();
        return $stale;
    }
}
