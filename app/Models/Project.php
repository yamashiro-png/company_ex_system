<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    // 保存を許可する項目
    // 現在のコード
    protected $fillable = [
    'customer_id', 'name', 'pic_name', 'pic_email', 'device_model', 'device_count', 
    'status', 'price', 'final_price', 'contract_date', 'completion_date', 'notes', 
    'parameter_text', 'parameter_file_path',
    'partner_name', // ← これを追加
    'cost_price', 'partner_completion_date', 'partner_message'
    ];
    public const STATUS_OPTIONS = [
        '見積もり待ち',
        '見積もり依頼中',
        '見積もり依頼待ち',
        '見積もり結果待ち',
        '物品入荷待ち',
        '失注',
        '完了',
    ];
    // 「この案件は、1つの顧客に属している」という関係を定義
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }
    public function estimates()
    {
        return $this->hasMany(ProjectEstimate::class);
    }
}