<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectInvoice extends Model
{
    protected $fillable = [
        'project_id',
        'sequence',
        'billing_date',
        'billing_count',
        'billing_shipping_cost',
        'amount_total',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
