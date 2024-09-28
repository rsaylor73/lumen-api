<?php

namespace App\Models\v2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestingServers extends Model
{
    protected $table = "v2_testing_servers";
    protected $fillable = [
        'id',
        'ticket',
        'dns',
        'email',
        'terraform_fileName',
        'terraform_variable_string',
        'description',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;

    public static function getServers($status, $page, $pageSize, $searchTerm, $emailFilter, $orderBy, $sortOrder, $dateStartFilter, $dateEndFilter, $textSearch): array
    {
        $today = date("Y-m-d");

        $query = self::from('v2_testing_servers as t')
            ->select(
                't.id',
                't.ticket',
                't.dns',
                't.email',
                't.description',
                'q.status',
                't.created_at',
                't.updated_at',
                DB::raw("DATEDIFF('$today', t.created_at) AS days")
            );
        $query->join('v2_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.testing_serverID', '=', 't.id');
        });
        $query->where(function ($filter) use ($status, $searchTerm, $emailFilter, $dateStartFilter, $dateEndFilter, $textSearch) {
            if (!empty($status)) {
                $status = str_replace(' ', '', $status);
                $status = explode(',', $status);
                if (is_array($status)) {
                    $filter->whereIn('q.status',$status);
                } else {
                    $filter->where('q.status', '=', $status);
                }
            }

            if (!is_null($dateStartFilter) && !is_null($dateEndFilter)) {
                $filter->whereBetween('t.created_at', [$dateStartFilter, $dateEndFilter]);
            }

            if (!empty($textSearch)) {
                $defaultStatus = ['complete', 'terminated', 'building'];
                $filter->where(DB::raw("CONCAT(t.ticket, ' ', t.dns, ' ', t.description)"), 'LIKE', "%".$textSearch."%");
                $filter->whereIn('q.status', $defaultStatus);
            }

            if (!empty($emailFilter)) {
                $filter->where('t.email', 'like', "%{$emailFilter}%");
            }
        });

        if (!empty($orderBy)) {
            if ($sortOrder == "") {
                $sortOrder = "desc";
            }
            $query->orderBy($orderBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

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

    public static function newServer($ticket, $dns, $email, $terraform_fileName, $terraform_variable_string, $description): TestingServers
    {
        $server = new self();
        $server->ticket = $ticket;
        $server->dns = $dns;
        $server->email = $email;
        $server->terraform_fileName = $terraform_fileName;
        $server->terraform_variable_string = $terraform_variable_string;
        $server->description = $description;
        $server->created_at = new \DateTime();
        $server->updated_at = new \DateTime();
        $server->save();

        return $server;
    }

    public static function checkDuplicateTerraformFile($terraform_fileName)
    {
        $status = ["pending", "running", "complete"];

        return self::from('v2_testing_servers as t')

        ->select('t.id')

        ->join('v2_server_queue as q', function ($innerJoin) {
            $innerJoin->on('q.testing_serverID', '=', 't.id');
        })

        ->where(function ($filter) use ($terraform_fileName, $status) {
            $filter->where('t.terraform_fileName', '=', $terraform_fileName);
            $filter->whereIn('q.status', $status);
        })

        ->get();
    }

    public static function checkDuplicateTerraformVar($terraform_variable_string)
    {
        return self::from('v2_testing_servers as t')
            ->select('t.id')
            ->where(function ($filter) use ($terraform_variable_string) {
                $filter->where('t.terraform_variable_string', '=', $terraform_variable_string);
            })
            ->count();
    }
}
