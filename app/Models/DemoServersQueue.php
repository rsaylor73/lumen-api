<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DemoServersQueue extends Model
{
    protected $table = "demo_servers_queue";
    protected $fillable = [
        'id',
        'demoID',
        'status',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function newPendingServer($demo, $status)
    {
        $pending = new self();
        $pending->demoID = $demo;
        $pending->status = $status;
        $pending->created_at = new \DateTime();
        $pending->updated_at = new \DateTime();
        $pending->save();
        return $pending;
    }

    public static function getPendingServerList($status)
    {
        return DemoServersQueue::from('demo_servers_queue as p')
            ->select(
                'p.id',
                'p.status',
                'p.demoID',
                'd.dns',
                'd.email',
                'd.created_at'
            )
            ->leftJoin('demo_servers as d', function($leftJoin)
            {
                $leftJoin->on('d.id', '=', 'p.demoID');
            })
            ->where('p.status', $status)
            ->take(1)
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
