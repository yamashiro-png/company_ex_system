<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->get(); 
        $projects = collect();
        return view('customers.index', compact('customers', 'projects'));
    }

    public function show(Customer $customer)
    {
        $customers = Customer::latest()->get(); 
        $selectedCustomer = $customer;
        $projects = $customer->projects()->latest()->get();
        
        return view('customers.index', compact('customers', 'selectedCustomer', 'projects'));
    }
    public function store(Request $request)
    {
        // 顧客登録は System Admin のみ
        Gate::authorize('system_admin');

        $request->validate(['name' => 'required|string|max:255']);

        // 依頼元企業番号を通番で採番（同時登録でも重複しないようロックして採番）
        \DB::transaction(function () use ($request) {
            $nextNumber = (Customer::query()->lockForUpdate()->max('customer_number') ?? 0) + 1;
            Customer::create([
                'name' => $request->name,
                'customer_number' => $nextNumber,
            ]);
        });
        
        activity()->causedBy($request->user())->log('顧客「' . $request->name . '」を新規登録しました');

        return back()->with('success', '顧客を登録しました。');
    }

    // 更新（名前変更）処理
    public function update(Request $request, Customer $customer)
    {
        // 顧客情報の変更は System Admin のみ
        Gate::authorize('system_admin');
        
        $request->validate(['name' => 'required|string|max:255']);
        
        $oldName = $customer->name;
        $customer->update(['name' => $request->name]);

        activity()->causedBy($request->user())->log('顧客名を「' . $oldName . '」から「' . $customer->name . '」に変更しました');

        return back()->with('success', '顧客名を変更しました。');
    }

    // 削除処理
    public function destroy(Request $request, Customer $customer)
    {
        Gate::authorize('system_admin');
        $name = $customer->name;
        $customer->delete();

        activity()->causedBy($request->user())->log('顧客「' . $name . '」を削除しました');

        return back()->with('success', '顧客を削除しました。');
    }
    public function exportPdf()
    {
        // ✅ セキュリティ修正6：PDFエクスポート権限チェック（管理者のみ）
        Gate::authorize('admin');
        
        // 最新の案件リストと、紐づく顧客データを取得
        $projects = Project::with('customer')->latest()->get();

        // PDF専用の画面（customers.pdf）にデータを渡してPDFを生成
        $pdf = Pdf::loadView('customers.pdf', compact('projects'));

        // 'projects_list.pdf' というファイル名でダウンロードさせる
        return $pdf->download('projects_list.pdf');
    }
}