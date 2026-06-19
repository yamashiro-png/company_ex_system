<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectShipment extends Model
{
    protected $fillable = ['project_id', 'planned_date', 'planned_count'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // この出荷予定に紐づく出荷実績（STEP 8）
    public function deliveries()
    {
        return $this->hasMany(ProjectDelivery::class, 'shipment_id');
    }
}
