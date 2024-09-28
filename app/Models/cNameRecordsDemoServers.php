<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class cNameRecordsDemoServers extends Model
{
    protected $table = "demo_servers_cname_records";
    protected $fillable = [
        'id',
        'demoID',
        'cname_identifier',
        'domain',
        'cname_value'
    ];
    public $timestamps = false;

    public static function newRecord($demoID, $cname_identifier, $domain, $cname_value)
    {
        $newCname = new self();
        $newCname->demoID = $demoID;
        $newCname->cname_identifier = $cname_identifier;
        $newCname->domain = $domain;
        $newCname->cname_value = $cname_value;
        $newCname->save();

        return $newCname;
    }

    public static function getCnameRecords($demoID)
    {
        $query = self::from('demo_servers_cname_records as c')
            ->select(
                'c.id',
                'c.demoID',
                'c.cname_identifier',
                'c.cname_value',
                'c.domain'
            );
        $query->where(function ($filter) use ($demoID) {
            $filter->where('c.demoID', '=', $demoID);
        });
        return $query->get();
    }
}
