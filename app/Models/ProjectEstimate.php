<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEstimate extends Model
{
    use HasFactory;
    
    // 保存を許可する項目
    protected $fillable = ['project_id', 'partner_name', 'cost_price', 'partner_completion_date', 'partner_message'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}