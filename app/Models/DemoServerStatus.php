<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DemoServerStatus extends Model
{
    protected $table = "demo_server_status";
    protected $fillable = [
        'id',
        'demoID',
        'status',
        'created_at'
    ];
    public $timestamps = false;

    public static function newServerStatus($demo, $status)
    {
        $stat = new self();
        $stat->demoID = $demo;
        $stat->status = $status;
        $stat->created_at = new \DateTime();
        $stat->save();
        return $stat;
    }
}
