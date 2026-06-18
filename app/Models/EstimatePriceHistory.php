<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimatePriceHistory extends Model
{
    // 保存を許可する項目
    protected $fillable = [
        'project_estimate_id',
        'old_cost_price',
        'new_cost_price',
        'changed_by',
        'approved_by',
        'reason',
        'edit_request_id',
    ];

    public function estimate()
    {
        return $this->belongsTo(ProjectEstimate::class, 'project_estimate_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function editRequest()
    {
        return $this->belongsTo(EstimateEditRequest::class, 'edit_request_id');
    }
}
