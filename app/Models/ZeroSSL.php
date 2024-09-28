<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ZeroSSL extends Model
{
    protected $table = "zero_ssl";
    protected $fillable = [
        'id',
        'ticketID',
        'sslID',
        'csr',
        'crt',
        'key',
        'ca_bundle',
        'date_created',
        'date_updated'
    ];
    public $timestamps = false;

    public static function getSSLRecord($ticketID)
    {
        return self::from('zero_ssl as z')
            ->select(
                'z.id',
                'z.ticketID',
                'z.sslID',
                'z.csr',
                'z.crt',
                'z.key',
                'z.ca_bundle',
                'z.date_created',
                'z.date_updated'
            )
            ->where(function ($idFilter) use ($ticketID) {
                $idFilter->where('z.ticketID', '=', $ticketID);
            })
            ->get()
        ;
    }

    public static function newSSLRecord($ticketID, $sslID, $csr, $crt, $key, $ca_bundle)
    {
        $ssl = new self();

        $ssl->ticketID = $ticketID;
        $ssl->sslID = $sslID;
        if ($csr != "") {
            $ssl->csr = $csr;
        }
        if ($key != "") {
            $ssl->key = $key;
        }
        if ($crt != "") {
            $ssl->crt = $crt;
        }
        if ($ca_bundle != "") {
            $ssl->ca_bundle = $ca_bundle;
        }
        $ssl->date_created = new \DateTime();
        $ssl->date_updated = new \DateTime();
        $ssl->save();

        return $ssl;
    }

    public static function updateSSLRecord($id, $ticketID, $sslID, $csr, $crt, $key, $ca_bundle)
    {
        $ssl = self::find($id);

        $ssl->ticketID = $ticketID;
        $ssl->sslID = $sslID;
        if ($csr != "") {
            $ssl->csr = $csr;
        }
        if ($key != "") {
            $ssl->key = $key;
        }
        if ($crt != "") {
            $ssl->crt = $crt;
        }
        if ($ca_bundle != "") {
            $ssl->ca_bundle = $ca_bundle;
        }
        $ssl->date_created = new \DateTime();
        $ssl->date_updated = new \DateTime();
        $ssl->save();

        return $ssl;
    }
}
