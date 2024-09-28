<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class UnitTests extends Model
{
    protected $table = "unit_tests";
    protected $fillable = [
        'id',
        'ticketID',
        'vjs_status',
        'vjs_tests',
        'vjs_passes',
        'vjs_assertions',
        'jr_status',
        'jr_tests',
        'jr_passes',
        'jr_assertions',
        'sac_status',
        'sac_tests',
        'sac_passes',
        'sac_assertions',
        'planner_status',
        'planner_tests',
        'planner_passes',
        'planner_assertions',
        'auth_status',
        'auth_tests',
        'auth_passes',
        'auth_assertions',
        'created_time',
        'created_at'
    ];
    public $timestamps = false;

    public static function getUnitTestingRecord($ticketID)
    {
        return self::from('unit_tests as u')
            ->select(
                'u.id',
                'u.ticketID',
                'u.vjs_status',
                'u.vjs_tests',
                'u.vjs_passes',
                'u.vjs_assertions',
                'u.jr_status',
                'u.jr_tests',
                'u.jr_passes',
                'u.jr_assertions',
                'u.sac_status',
                'u.sac_tests',
                'u.sac_passes',
                'u.sac_assertions',
                'u.planner_status',
                'u.planner_tests',
                'u.planner_passes',
                'u.planner_assertions',
                'u.auth_status',
                'u.auth_tests',
                'u.auth_passes',
                'u.auth_assertions',
                'u.created_time',
                'u.created_at'
            )
            ->where(function ($idFilter) use ($ticketID) {
                $idFilter->where('u.ticketID', '=', $ticketID);
            })
            ->get()
        ;
    }

    public static function saveUnitTestRecord($type, $ticketID, $json, $id = null)
    {
        if ($type == "new") {
            $unitTest = new self();
        } else {
            $unitTest = self::find($id);
        }
        $unitTest->ticketID = $ticketID;

        if (isset($json['vjs_status'])) {
            $unitTest->vjs_status = $json['vjs_status'];
        }
        if (isset($json['vjs_tests'])) {
            $unitTest->vjs_tests = $json['vjs_tests'];
        }
        if (isset($json['vjs_passes'])) {
            $unitTest->vjs_passes = $json['vjs_passes'];
        }
        if (isset($json['vjs_assertions'])) {
            $unitTest->vjs_assertions = $json['vjs_assertions'];
        }

        if (isset($json['jr_status'])) {
            $unitTest->jr_status = $json['jr_status'];
        }
        if (isset($json['jr_tests'])) {
            $unitTest->jr_tests = $json['jr_tests'];
        }
        if (isset($json['jr_passes'])) {
            $unitTest->jr_passes = $json['jr_passes'];
        }
        if (isset($json['jr_assertions'])) {
            $unitTest->jr_assertions = $json['jr_assertions'];
        }

        if (isset($json['sac_status'])) {
            $unitTest->sac_status = $json['sac_status'];
        }
        if (isset($json['sac_tests'])) {
            $unitTest->sac_tests = $json['sac_tests'];
        }
        if (isset($json['sac_passes'])) {
            $unitTest->sac_passes = $json['sac_passes'];
        }
        if (isset($json['sac_assertions'])) {
            $unitTest->sac_assertions = $json['sac_assertions'];
        }

        if (isset($json['planner_status'])) {
            $unitTest->planner_status = $json['planner_status'];
        }
        if (isset($json['planner_tests'])) {
            $unitTest->planner_tests = $json['planner_tests'];
        }
        if (isset($json['planner_passes'])) {
            $unitTest->planner_passes = $json['planner_passes'];
        }
        if (isset($json['planner_assertions'])) {
            $unitTest->planner_assertions = $json['planner_assertions'];
        }

        if (isset($json['auth_status'])) {
            $unitTest->auth_status = $json['auth_status'];
        }
        if (isset($json['auth_tests'])) {
            $unitTest->auth_tests = $json['auth_tests'];
        }
        if (isset($json['auth_passes'])) {
            $unitTest->auth_passes = $json['auth_passes'];
        }
        if (isset($json['auth_assertions'])) {
            $unitTest->auth_assertions = $json['auth_assertions'];
        }

        $unitTest->created_at = new \DateTime();
        $unitTest->created_time = new \DateTime();
        $unitTest->save();

        return $unitTest;
    }
}
