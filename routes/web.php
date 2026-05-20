<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;
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
    
    // 1. 最近のログ
    $logs = Activity::with('causer')->latest()->take(10)->get();
    
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

    return view('dashboard', compact('logs', 'activeCount', 'urgentProjects', 'statusBreakdown'));

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
    Route::get('/file_manager/download/{id}', [DocumentController::class, 'download'])->name('file_manager.download');
    Route::delete('/file_manager/delete/{id}', [DocumentController::class, 'destroy'])->name('file_manager.destroy');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    // パラメーターファイルのダウンロードと削除用のルート
    Route::get('/projects/files/{projectFile}/download', [App\Http\Controllers\ProjectController::class, 'downloadFile'])->name('projects.files.download');
    Route::delete('/projects/files/{projectFile}/delete', [App\Http\Controllers\ProjectController::class, 'deleteFile'])->name('projects.files.delete');
    Route::get('/projects/{project}/workspace', [App\Http\Controllers\ProjectController::class, 'workspace'])->name('projects.workspace');
    Route::put('/projects/{project}', [App\Http\Controllers\ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/generate-email', [App\Http\Controllers\ProjectController::class, 'generateEmail'])->name('projects.generate_email');
    Route::delete('/projects/{project}/parameter-file', [App\Http\Controllers\ProjectController::class, 'deleteParameterFile'])->name('projects.delete_parameter_file');
    Route::get('/projects/{project}/parameter-file/download', [App\Http\Controllers\ProjectController::class, 'downloadParameterFile'])->name('projects.download_parameter_file');
    Route::get('/project-files/{projectFile}/download', [App\Http\Controllers\ProjectController::class, 'downloadFile'])->name('project_files.download');
    Route::delete('/project-files/{projectFile}', [App\Http\Controllers\ProjectController::class, 'deleteFile'])->name('project_files.destroy');
    Route::post('/projects/{project}/generate-final-email', [App\Http\Controllers\ProjectController::class, 'generateFinalEmail'])->name('projects.generate_final_email');
});

require __DIR__.'/auth.php';