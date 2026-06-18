<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimateEditRequest extends Model
{
    // 保存を許可する項目
    protected $fillable = [
        'project_estimate_id',
        'requester_id',
        'supervisor_id',
        'reason',
        'requested_cost_price',
        'status',
        'requester_dismissed_at',
    ];

    public function estimate()
    {
        return $this->belongsTo(ProjectEstimate::class, 'project_estimate_id');
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
