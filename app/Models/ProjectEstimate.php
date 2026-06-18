<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEstimate extends Model
{
    use HasFactory;
    
    // 保存を許可する項目
    protected $fillable = ['project_id', 'partner_name', 'cost_price', 'partner_completion_date', 'partner_message', 'result'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function exchanges()
    {
        return $this->hasMany(EstimateExchange::class, 'project_estimate_id')->orderBy('exchanged_at', 'asc');
    }

    public function editRequests()
    {
        return $this->hasMany(EstimateEditRequest::class, 'project_estimate_id')->latest();
    }

    public function priceHistories()
    {
        return $this->hasMany(EstimatePriceHistory::class, 'project_estimate_id')->latest();
    }
}