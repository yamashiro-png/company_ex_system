<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use App\Models\UnderCompany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

class AdminManagementController extends Controller
{
    public function index()
    {
        // Admin以上の権限があるかチェック
        Gate::authorize('admin');

        $users = User::all();
        $customers = Customer::all();
        $underCompanies = UnderCompany::all();
        $projects = Project::with('customer')->get();

        return view('admin.management', compact('users', 'customers', 'underCompanies', 'projects'));
    }

    // ユーザー登録処理
    public function storeUser(Request $request)
    {
        Gate::authorize('system_admin');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:system_admin,admin,user',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return back()->with('success', '新しいユーザーを登録しました。');
    }

    // ユーザー削除処理
    public function destroyUser(User $user)
    {
        // 削除は System Admin だけ
        Gate::authorize('system_admin');
        
        if ($user->id === auth()->id()) {
            return back()->with('error', '自分自身は削除できません。');
        }

        $user->delete();
        return back()->with('success', 'ユーザーを削除しました。');
    }

    // 依頼先会社の登録
    public function storeUnderCompany(Request $request)
    {
        Gate::authorize('admin');
        
        // pic_name を追加
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'pic_name' => 'nullable|string|max:255', // ← 追加
            'email' => 'nullable|email'
        ]);
        
        UnderCompany::create($validated);
        return back()->with('success', '依頼先会社を登録しました。');
    }

    //依頼先会社の削除（System Adminのみ）
    public function destroyUnderCompany(UnderCompany $underCompany)
    {
        Gate::authorize('system_admin');
        $underCompany->delete();
        return back()->with('success', '依頼先会社を削除しました。');
    }

    // 顧客の削除（System Adminのみ）
    public function destroyCustomer(Customer $customer)
    {
        Gate::authorize('system_admin');
        $customer->delete();
        return back()->with('success', '顧客データを削除しました。');
    }

    // 案件の削除（System Adminのみ）
    public function destroyProject(Project $project)
    {
        Gate::authorize('system_admin');
        $project->delete();
        return back()->with('success', '案件データを削除しました。');
    }
}