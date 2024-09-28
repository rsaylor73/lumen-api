<?php

namespace App\Models\v2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DemoServers extends Model
{
    protected $table = "v2_demo_servers";
    protected $fillable = [
        'id',
        'dns',
        'email',
        'terraform_fileName',
        'terraform_variable_string',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function newServer($dns, $email, $terraform_fileName, $terraform_variable_string): DemoServers
    {
        $server = new self();
        $server->dns = $dns;
        $server->email = $email;
        $server->terraform_fileName = $terraform_fileName;
        $server->terraform_variable_string = $terraform_variable_string;
        $server->created_at = new \DateTime();
        $server->updated_at = new \DateTime();
        $server->save();

        return $server;
    }

    public static function serversToDelete($date)
    {
        return self::from('v2_demo_servers as d')
            ->select(
                'd.id',
                'd.terraform_fileName',
                'q.id as qId'
            )
            ->leftJoin('v2_demo_server_queue as q', function($leftJoin)
            {
                $leftJoin->on('d.id', '=', 'q.demo_serverID');
            })
            ->where(function ($filter) use ($date) {
                $filter->where('d.created_at', '=', $date);
                $filter->where('q.status', '=', 'complete');
            })
            ->get()
        ;
    }

    public static function checkDuplicateTerraformFile($terraform_fileName)
    {
        return self::from('v2_demo_servers as t')
            ->select('t.id')
            ->where(function ($filter) use ($terraform_fileName) {
                $filter->where('t.terraform_fileName', '=', $terraform_fileName);
            })
            ->count();
    }

    public static function checkDuplicateTerraformVar($terraform_variable_string)
    {
        return self::from('v2_demo_servers as t')
            ->select('t.id')
            ->where(function ($filter) use ($terraform_variable_string) {
                $filter->where('t.terraform_variable_string', '=', $terraform_variable_string);
            })
            ->count();
    }
}
