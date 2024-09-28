<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SnapshotBackup;

class SnapshotQueue extends Model
{
    protected $table = "snapshot_queue";
    protected $fillable = [
        'id',
        'ticketID',
        'status',
        'snapshotID',
        'date_created',
        'date_updated'
    ];
    public $timestamps = false;

    public static function backup($id)
    {
        $backup = new self();
        $backup->ticketID = $id;
        $backup->status = 'pending';
        $backup->date_created = new \DateTime();
        $backup->date_updated = new \DateTime();
        $backup->save();
    }

    public static function pullPendingRequests()
    {
        $query = self::from('snapshot_queue as s')
            ->select(
                's.id',
                't.id AS ticketID',
                't.instanceID'
            )
        ;

        $query->join('testing_servers as t', function ($innerJoin) {
            $innerJoin->on('t.id', '=', 's.ticketID');
        });

        $query->where(function ($filter) {
            $filter->where('s.status', '=', 'pending');
        });

        $query->take(1);
        return $query->get();
    }

    public static function updateServerStatus($id, $status)
    {
        $server = self::find($id);
        $server->status = $status;
        $server->save();
        return $server;
    }

    public static function saveSnapShot($id, $snapshotID)
    {
        $snap = SnapshotBackup::saveSnapShot($id, $snapshotID);
        $server = self::find($id);

    }
}
