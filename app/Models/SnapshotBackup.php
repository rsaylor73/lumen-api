<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SnapshotBackup extends Model
{
    protected $table = "snapshot_backup";
    protected $fillable = [
        'id',
        'ticketID',
        'snapshotID',
        'created_at'
    ];
    public $timestamps = false;

    public static function saveSnapShot($id, $snapshotID)
    {
        $snap = new self();
        $snap->ticketID = $id;
        $snap->snapshotID = $snapshotID;
        $snap->created_at = new \DateTime();
        $snap->save();

        return $snap;
    }
}
