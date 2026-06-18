<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AdminManagementController;

// ▼ 新しいダッシュボードのために必要なクラスを追加 ▼
use App\Models\Project;
use Carbon\Carbon;

Route::get('/', function () {
    return view('welcome');
});

// ▼ ダッシュボードの処理をアップデート ▼
Route::get('/dashboard', function () {

    $myId = auth()->id();

    // 1. 自分への通知（申請の受信・自分の申請の結果）
    $notifications = collect();

    // 自分宛ての承認待ち申請（STEP 3: 見積金額）
    foreach (\App\Models\EstimateEditRequest::with(['requester', 'estimate.project'])
        ->where('supervisor_id', $myId)->where('status', 'pending')
        ->latest()->take(10)->get() as $req) {
        $notifications->push([
            'time'    => $req->created_at,
            'type'    => 'incoming',
            'message' => ($req->requester->name ?? '不明') . ' さんから案件「' . ($req->estimate?->project?->name ?? '-') . '」／「' . ($req->estimate->partner_name ?? '-') . '」の見積金額の編集申請が届いています',
            'url'     => route('approvals.index'),
        ]);
    }

    // 自分宛ての承認待ち申請（STEP 4: 最終見積）
    foreach (\App\Models\ProjectEditRequest::with(['requester', 'project'])
        ->where('supervisor_id', $myId)->where('status', 'pending')
        ->latest()->take(10)->get() as $req) {
        $notifications->push([
            'time'    => $req->created_at,
            'type'    => 'incoming',
            'message' => ($req->requester->name ?? '不明') . ' さんから案件「' . ($req->project->name ?? '-') . '」（採用企業：' . ($req->requested_partner_name ?? '-') . '）の最終見積の編集申請が届いています',
            'url'     => route('approvals.index'),
        ]);
    }

    // 自分が出した申請の結果（STEP 3）※消した通知は表示しない
    foreach (\App\Models\EstimateEditRequest::with('estimate.project')
        ->where('requester_id', $myId)->whereIn('status', ['approved', 'rejected'])
        ->whereNull('requester_dismissed_at')
        ->latest('updated_at')->take(10)->get() as $req) {
        $notifications->push([
            'time'        => $req->updated_at,
            'type'        => $req->status,
            'message'     => '案件「' . ($req->estimate?->project?->name ?? '-') . '」／「' . ($req->estimate->partner_name ?? '-') . '」の見積金額の編集申請が' . ($req->status === 'approved' ? '承認されました' : '却下されました'),
            'url'         => $req->estimate?->project ? route('projects.workspace', $req->estimate->project) : route('projects.index'),
            'dismiss_url' => route('notifications.estimate.dismiss', $req),
        ]);
    }

    // 自分が出した申請の結果（STEP 4）※消した通知は表示しない
    foreach (\App\Models\ProjectEditRequest::with('project')
        ->where('requester_id', $myId)->whereIn('status', ['approved', 'rejected'])
        ->whereNull('requester_dismissed_at')
        ->latest('updated_at')->take(10)->get() as $req) {
        $notifications->push([
            'time'        => $req->updated_at,
            'type'        => $req->status,
            'message'     => '案件「' . ($req->project->name ?? '-') . '」（採用企業：' . ($req->requested_partner_name ?? '-') . '）の最終見積の編集申請が' . ($req->status === 'approved' ? '承認されました' : '却下されました'),
            'url'         => $req->project ? route('projects.workspace', $req->project) : route('projects.index'),
            'dismiss_url' => route('notifications.project.dismiss', $req),
        ]);
    }

    $notifications = $notifications->sortByDesc('time')->take(10)->values();

    // 2. 未完了の案件数（'完了' 以外）
    $activeCount = Project::where('status', '!=', '完了')->count();
    
    // 3. 期限間近（あと7日以内）で未完了の案件
    $urgentProjects = Project::where('status', '!=', '完了')
        ->whereNotNull('completion_date')
        ->where('completion_date', '<=', Carbon::now()->addDays(7))
        ->orderBy('completion_date', 'asc')
        ->get();

    // 4. 各ステータスごとの件数
    $allStatuses = Project::STATUS_OPTIONS;
    $counts = Project::select('status', \DB::raw('count(*) as total'))
        ->groupBy('status')
        ->pluck('total', 'status');
        
    $statusBreakdown = [];
    foreach ($allStatuses as $status) {
        $statusBreakdown[$status] = $counts->get($status, 0);
    }

    return view('dashboard', compact('notifications', 'activeCount', 'urgentProjects', 'statusBreakdown'));

})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects', [App\Http\Controllers\ProjectController::class, 'index'])->name('projects.index');
    Route::get('/customers/export-pdf', [CustomerController::class, 'exportPdf'])->name('customers.pdf');
    
    // ※ エラーの原因だった不要な route('file.management') は削除しました
    
    Route::get('/file_manager', [DocumentController::class, 'index'])->middleware(['auth', 'verified'])->name('file_manager');
    Route::post('/file_manager', [DocumentController::class, 'store'])->middleware(['auth', 'verified'])->name('file_manager.store');
    Route::get('/management', [AdminManagementController::class, 'index'])->name('admin.management');
    Route::post('/management/users', [AdminManagementController::class, 'storeUser'])->name('admin.users.store');
    Route::post('/management/under-companies', [AdminManagementController::class, 'storeUnderCompany'])->name('admin.under_companies.store');
    Route::delete('/management/under-companies/{underCompany}', [AdminManagementController::class, 'destroyUnderCompany'])->name('admin.under_companies.destroy');
    Route::delete('/management/customers/{customer}', [AdminManagementController::class, 'destroyCustomer'])->name('admin.customers.destroy');
    Route::delete('/management/projects/{project}', [AdminManagementController::class, 'destroyProject'])->name('admin.projects.destroy');
    Route::delete('/management/users/{user}', [AdminManagementController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::put('/management/users/{user}/supervisor', [AdminManagementController::class, 'updateUserSupervisor'])->name('admin.users.update_supervisor');
    Route::put('/management/users/{user}/profile', [AdminManagementController::class, 'updateUserProfile'])->name('admin.users.update_profile');
    Route::post('/management/accessories', [AdminManagementController::class, 'storeAccessory'])->name('admin.accessories.store');
    Route::delete('/management/accessories/{accessory}', [AdminManagementController::class, 'destroyAccessory'])->name('admin.accessories.destroy');
    Route::get('/file_manager/download/{id}', [DocumentController::class, 'download'])->name('file_manager.download');
    Route::delete('/file_manager/delete/{id}', [DocumentController::class, 'destroy'])->name('file_manager.destroy');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    // プロジェクトファイルのダウンロードと削除
    Route::get('/projects/files/{projectFile}/download', [App\Http\Controllers\ProjectController::class, 'downloadFile'])->name('projects.files.download');
    Route::delete('/projects/files/{projectFile}/delete', [App\Http\Controllers\ProjectController::class, 'deleteFile'])->name('projects.files.delete');
    Route::get('/projects/{project}/workspace', [App\Http\Controllers\ProjectController::class, 'workspace'])->name('projects.workspace');
    Route::put('/projects/{project}', [App\Http\Controllers\ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/generate-email', [App\Http\Controllers\ProjectController::class, 'generateEmail'])->name('projects.generate_email');
    // 依頼先会社とのやり取り記録
    Route::post('/projects/estimates/{estimate}/exchanges', [App\Http\Controllers\ProjectController::class, 'storeExchange'])->name('projects.estimates.exchanges.store');
    // 見積金額の編集申請（上長への承認依頼）
    Route::post('/projects/estimates/{estimate}/edit-requests', [App\Http\Controllers\ProjectController::class, 'storeEditRequest'])->name('projects.estimates.edit_requests.store');
    // 最終見積の編集申請（STEP 4）
    Route::post('/projects/{project}/edit-requests', [App\Http\Controllers\ProjectController::class, 'storeProjectEditRequest'])->name('projects.edit_requests.store');
    // 上長の承認・却下
    Route::get('/approvals', [App\Http\Controllers\ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{editRequest}/approve', [App\Http\Controllers\ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{editRequest}/reject', [App\Http\Controllers\ApprovalController::class, 'reject'])->name('approvals.reject');
    Route::post('/approvals/project/{projectEditRequest}/approve', [App\Http\Controllers\ApprovalController::class, 'approveProject'])->name('approvals.project.approve');
    Route::post('/approvals/project/{projectEditRequest}/reject', [App\Http\Controllers\ApprovalController::class, 'rejectProject'])->name('approvals.project.reject');
    // 通知のクリア
    Route::post('/notifications/estimate/{editRequest}/dismiss', [App\Http\Controllers\NotificationController::class, 'dismissEstimate'])->name('notifications.estimate.dismiss');
    Route::post('/notifications/project/{projectEditRequest}/dismiss', [App\Http\Controllers\NotificationController::class, 'dismissProject'])->name('notifications.project.dismiss');
    Route::post('/notifications/dismiss-all', [App\Http\Controllers\NotificationController::class, 'dismissAll'])->name('notifications.dismiss_all');
    Route::post('/projects/{project}/generate-final-email', [App\Http\Controllers\ProjectController::class, 'generateFinalEmail'])->name('projects.generate_final_email');
    Route::get('/projects/{project}/quotation-pdf', [App\Http\Controllers\ProjectController::class, 'generateQuotationPdf'])->name('projects.quotation_pdf');
    Route::post('/projects/{project}/decision', [App\Http\Controllers\ProjectController::class, 'storeDecision'])->name('projects.decision');
    Route::post('/projects/{project}/order-files', [App\Http\Controllers\ProjectController::class, 'uploadOrderFiles'])->name('projects.order_files.store');
    // 受注後フロー（STEP 5〜8）
    Route::post('/projects/{project}/order-info', [App\Http\Controllers\ProjectController::class, 'confirmOrderInfo'])->name('projects.order_info.confirm');
    Route::post('/projects/{project}/arrival', [App\Http\Controllers\ProjectController::class, 'registerArrival'])->name('projects.arrival.register');
    Route::post('/projects/{project}/arrivals', [App\Http\Controllers\ProjectController::class, 'addArrival'])->name('projects.arrivals.add');
    Route::delete('/projects/{project}/arrivals/{arrival}', [App\Http\Controllers\ProjectController::class, 'deleteArrival'])->name('projects.arrivals.delete');
    // 付属品（案件ごと）
    Route::post('/projects/{project}/accessories', [App\Http\Controllers\ProjectController::class, 'addProjectAccessory'])->name('projects.accessories.add');
    Route::delete('/projects/{project}/accessories/{projectAccessory}', [App\Http\Controllers\ProjectController::class, 'deleteProjectAccessory'])->name('projects.accessories.delete');
    Route::post('/projects/{project}/accessories/arrivals', [App\Http\Controllers\ProjectController::class, 'updateAccessoryArrivals'])->name('projects.accessories.arrivals');
    Route::post('/projects/{project}/shipments', [App\Http\Controllers\ProjectController::class, 'addShipment'])->name('projects.shipments.add');
    Route::delete('/projects/{project}/shipments/{shipment}', [App\Http\Controllers\ProjectController::class, 'deleteShipment'])->name('projects.shipments.delete');
    Route::post('/projects/{project}/shipments/confirm', [App\Http\Controllers\ProjectController::class, 'confirmShipments'])->name('projects.shipments.confirm');
    Route::post('/projects/{project}/deliveries', [App\Http\Controllers\ProjectController::class, 'addDelivery'])->name('projects.deliveries.add');
    Route::delete('/projects/{project}/deliveries/{delivery}', [App\Http\Controllers\ProjectController::class, 'deleteDelivery'])->name('projects.deliveries.delete');
    Route::post('/projects/{project}/invoice-pdf', [App\Http\Controllers\ProjectController::class, 'generateInvoicePdf'])->name('projects.invoice_pdf');
});

require __DIR__.'/auth.php';