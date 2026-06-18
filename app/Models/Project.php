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
    'project_number',
    'customer_id', 'name', 'pic_name', 'pic_email', 'own_pic_id', 'device_model', 'os', 'device_count', 'has_accessory',
    'arrival_method', 'delivery_method', 'shipment_confirmed_at',
    'status', 'price', 'final_price', 'quote_shipping_enabled', 'quote_shipping_fee', 'contract_date', 'completion_date', 'arrival_date', 'arrival_count', 'shipping_date', 'shipping_cost',
    'billing_date', 'billing_count', 'billing_shipping_cost', 'notes',
    'parameter_text', 'parameter_input_type', 'parameter_file_path',
    'partner_name', // ← これを追加
    'cost_price', 'partner_completion_date', 'partner_message'
    ];

    public const OS_OPTIONS = [
        'android',
        'ios',
        'windows',
        'mac',
        'chrome',
    ];
    public const METHOD_OPTIONS = [
        '分納',
        '一括納品',
        '不明',
    ];
    public const ACCESSORY_OPTIONS = [
        '有',
        '無',
    ];
    public const STATUS_OPTIONS = [
        '見積もり待ち',
        '見積もり依頼中',
        '見積もり依頼待ち',
        '見積もり結果待ち',
        '受注確定',
        '入荷登録情報待ち',
        '出荷情報登録待ち',
        '出荷情報待ち',
        '納品済み',
        '失注',
        '案件完了',
    ];

    // 受注後（受注確定以降）のステータス。旧「物品入荷待ち」「完了」も互換のため含める
    public const POST_ORDER_STATUSES = [
        '受注確定',
        '入荷登録情報待ち',
        '出荷情報登録待ち',
        '出荷情報待ち',
        '納品済み',
        '物品入荷待ち',
        '案件完了',
        '完了',
    ];
    /**
     * 帳票番号の生成
     * 記号（M=見積 / J=受注 / S=請求）＋ 依頼元企業番号2桁 ＋ 案件番号6桁
     * 例：M01000001
     */
    public function documentNumber(string $prefix): string
    {
        $customerNumber = str_pad($this->customer->customer_number ?? 0, 2, '0', STR_PAD_LEFT);
        $projectNumber = str_pad($this->project_number ?? 0, 6, '0', STR_PAD_LEFT);

        return $prefix . $customerNumber . $projectNumber;
    }

    // 「この案件は、1つの顧客に属している」という関係を定義
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 自社担当者
    public function ownPic()
    {
        return $this->belongsTo(User::class, 'own_pic_id');
    }
    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }
    public function estimates()
    {
        return $this->hasMany(ProjectEstimate::class);
    }

    public function editRequests()
    {
        return $this->hasMany(ProjectEditRequest::class)->latest();
    }

    public function priceHistories()
    {
        return $this->hasMany(ProjectPriceHistory::class)->latest();
    }

    public function shipments()
    {
        return $this->hasMany(ProjectShipment::class)->orderBy('planned_date');
    }

    public function arrivals()
    {
        return $this->hasMany(ProjectArrival::class)->orderBy('arrived_date');
    }

    public function invoices()
    {
        return $this->hasMany(ProjectInvoice::class)->orderBy('sequence');
    }

    public function deliveries()
    {
        return $this->hasMany(ProjectDelivery::class)->orderBy('shipped_date');
    }

    public function projectAccessories()
    {
        return $this->hasMany(ProjectAccessory::class)->with('accessory')->orderBy('id');
    }
}