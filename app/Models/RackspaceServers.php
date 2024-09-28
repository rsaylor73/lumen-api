<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RackspaceServers extends Model
{
    protected $table = "rackspace_servers";
    protected $fillable = [
        'id',
        'name',
        'serverID',
        'status',
        'ipAddress',
        'dmzIpAddress',
        'created_at',
        'modified_at'
    ];
    public $timestamps = false;

    public static function newServer($name, $serverID, $status, $ipAddress, $dmzIpAddress = null)
    {
        $server = new self();
        $server->name = $name;
        $server->serverID = $serverID;
        $server->status = $status;
        $server->ipAddress = $ipAddress;
        if (!is_null($dmzIpAddress)) {
            $server->dmzIpAddress = $dmzIpAddress;
        }
        $server->created_at = new \DateTime();
        $server->modified_at = new \DateTime();
        $server->save();

        return true;
    }

    public static function updateServer($id, $name, $serverID, $status, $ipAddress, $dmzIpAddress = null)
    {
        $server = self::find($id);
        $server->name = $name;
        $server->serverID = $serverID;
        $server->status = $status;
        $server->ipAddress = $ipAddress;
        if (!is_null($dmzIpAddress)) {
            $server->dmzIpAddress = $dmzIpAddress;
        }
        $server->created_at = new \DateTime();
        $server->modified_at = new \DateTime();
        $server->save();

        return true;
    }

    public static function findServerByField($field, $value)
    {
        return self::from('rackspace_servers as r')
            ->select(
                'r.id',
                'r.name',
                'r.dmzIpAddress'
            )
            ->where(function ($nameFilter) use ($field, $value) {
                $findBy = "r.{$field}";
                $nameFilter->where($findBy, '=', $value);
            })
            ->get()
        ;
    }
}
