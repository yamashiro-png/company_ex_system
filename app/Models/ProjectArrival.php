<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectArrival extends Model
{
    protected $fillable = ['project_id', 'arrived_date', 'arrived_count'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
