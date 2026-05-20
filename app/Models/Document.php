<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    // ✅ セキュリティ修正1：マスアサインメント脆弱性対策
    protected $fillable = ['user_id', 'original_name', 'save_path', 'file_size'];
}
