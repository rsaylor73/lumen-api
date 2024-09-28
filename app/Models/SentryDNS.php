<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SentryDNS extends Model
{
    protected $table = "sentry_dns";
    protected $fillable = [
        'id',
        'name',
        'dns',
        'default',
        'date_created'
    ];
    public $timestamps = false;

    public static function listSentry($page, $pageSize): array
    {
        $query = self::from('sentry_dns as s')
            ->select(
                's.id',
                's.name',
                's.dns',
                's.default'
            );
        $total = $query->count();
        $result = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get();

        return
            [
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize,
                    'last_page' => ceil($total / $pageSize)
                ],
                'data' => $result,
            ];
    }

    public static function view($id)
    {
        $query = self::from('sentry_dns as s')
            ->select(
                's.id',
                's.name',
                's.dns',
                's.default',
                's.date_created'
            )
            ->where(function ($filter) use ($id) {
                $filter->where('t.id', $id);
            })
        ;
        return $query->get();
    }

    public static function saveNewSentry($request): bool
    {
        $sentry = new self();
        $sentry->name = $request->input('name');
        $sentry->dns = $request->input('dns');
        if ($request->input('default') == "Yes") {
            $sentry->default = true;
        } else {
            $sentry->default = false;
        }
        $sentry->date_created = new \DateTime();
        return $sentry->save();
    }

    public static function updateSentry($id, $json): bool
    {
        $sentry = self::find($id);
        if (isset($json['name'])) {
            $sentry->name = $json['name'];
        }
        if (isset($json['dns'])) {
            $sentry->dns = $json['dns'];
        }
        if (isset($json['default'])) {
            if ($json['default'] == "Yes") {
                $sentry->default = true;
            } else {
                $sentry->default = false;
            }
        }

        return $sentry->save();
    }

    public static function deleteSentry($id)
    {
        $sentry = self::find($id);
        $sentry->delete();

        return $sentry;
    }
}
