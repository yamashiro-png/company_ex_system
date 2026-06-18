<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPriceHistory extends Model
{
    // 保存を許可する項目
    protected $fillable = [
        'project_id',
        'old_final_price',
        'new_final_price',
        'old_partner_name',
        'new_partner_name',
        'changed_by',
        'approved_by',
        'reason',
        'edit_request_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
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
        return $this->belongsTo(ProjectEditRequest::class, 'edit_request_id');
    }
}
