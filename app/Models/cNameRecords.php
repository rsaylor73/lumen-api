<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class cNameRecords extends Model
{
    protected $table = "cname_records";
    protected $fillable = [
        'id',
        'ticketID',
        'cname_identifier',
        'domain',
        'cname_value'
    ];
    public $timestamps = false;

    public static function newRecord($ticketID, $cname_identifier, $domain, $cname_value)
    {
        $newCname = new cNameRecords();
        $newCname->ticketID = $ticketID;
        $newCname->cname_identifier = $cname_identifier;
        $newCname->domain = $domain;
        $newCname->cname_value = $cname_value;
        $newCname->save();

        return $newCname;
    }

    public static function getCnameRecords($ticketID)
    {
        $query = self::from('cname_records as c')
            ->select(
                'c.id',
                'c.ticketID',
                'c.cname_identifier',
                'c.cname_value',
                'c.domain'
            );
        $query->where(function ($ticketFilter) use ($ticketID) {
            $ticketFilter->where('c.ticketID', '=', $ticketID);
        });
        return $query->get();
    }
}
