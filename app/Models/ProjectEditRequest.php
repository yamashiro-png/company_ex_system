<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectEditRequest extends Model
{
    // 保存を許可する項目
    protected $fillable = [
        'project_id',
        'requester_id',
        'supervisor_id',
        'reason',
        'requested_final_price',
        'requested_partner_name',
        'requested_estimate_id',
        'status',
        'requester_dismissed_at',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
