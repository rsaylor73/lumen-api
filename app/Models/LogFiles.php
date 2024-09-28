<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class LogFiles extends Model
{
    protected $table = "log_files";
    protected $fillable = [
        'id',
        'ticketID',
        'filename',
        'created_time',
        'created_at'
    ];
    public $timestamps = false;

    public static function saveNewLog($ticketID, $fileName)
    {
        $log = new self();
        $log->ticketID = $ticketID;
        $log->filename = $fileName;
        $log->created_time = new \DateTime();
        $log->created_at = new \DateTime();
        $log->save();
        return $log;
    }
}
