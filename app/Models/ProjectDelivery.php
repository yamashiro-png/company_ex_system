<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDelivery extends Model
{
    protected $fillable = ['project_id', 'shipped_date', 'shipped_count', 'shipping_cost'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
