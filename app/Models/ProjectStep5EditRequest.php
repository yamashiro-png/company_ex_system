<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStep5EditRequest extends Model
{
    protected $fillable = [
        'project_id', 'requester_id', 'supervisor_id', 'reason', 'status', 'approved_by',
        'requested_device_model', 'requested_device_count', 'requested_contract_date', 'requested_completion_date', 'requested_delivery_method'
    ];

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_id'); }
}