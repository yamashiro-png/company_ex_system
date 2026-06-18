<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimateExchange extends Model
{
    // 保存を許可する項目
    protected $fillable = ['project_estimate_id', 'exchanged_at', 'inquiry', 'reply'];

    public function estimate()
    {
        return $this->belongsTo(ProjectEstimate::class, 'project_estimate_id');
    }
}
