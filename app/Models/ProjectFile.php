<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    protected $fillable = ['project_id', 'file_name', 'file_path'];
    
    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }
}