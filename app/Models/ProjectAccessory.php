<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectAccessory extends Model
{
    protected $fillable = ['project_id', 'accessory_id', 'planned_count', 'arrived_count'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function accessory()
    {
        return $this->belongsTo(Accessory::class);
    }
}
