<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDelivery extends Model
{
    protected $fillable = ['project_id', 'shipment_id', 'shipped_date', 'shipped_count', 'shipping_cost'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // 対応する出荷予定（STEP 7）
    public function shipment()
    {
        return $this->belongsTo(ProjectShipment::class, 'shipment_id');
    }
}
