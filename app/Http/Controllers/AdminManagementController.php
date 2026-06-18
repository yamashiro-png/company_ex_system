<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use App\Models\UnderCompany;
use App\Models\Accessory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

class AdminManagementController extends Controller
{
    public function index()
    {
        // Admin以上の権限があるかチェック
        Gate::authorize('admin');

        $users = User::with(['supervisor', 'assignedUnderCompanies'])->get();
        $customers = Customer::all();
        $underCompanies = UnderCompany::all();
        $projects = Project::with('customer')->get();
        $accessories = Accessory::orderBy('name')->get();

        return view('admin.management', compact('users', 'customers', 'underCompanies', 'projects', 'accessories'));
    }

    // 付属品マスタの登録（Admin以上）
    public function storeAccessory(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'name' => 'required|string|max:255|unique:accessories,name',
        ], [
            'name.required' => '付属品名を入力してください。',
            'name.unique'   => 'その付属品はすでに登録されています。',
        ]);

        Accessory::create(['name' => $request->name]);

        return back()->with('success', '付属品「' . $request->name . '」を登録しました。');
    }

    // 付属品マスタの削除（Admin以上）
    public function destroyAccessory(Accessory $accessory)
    {
        Gate::authorize('admin');

        $name = $accessory->name;
        $accessory->delete();

        return back()->with('success', '付属品「' . $name . '」を削除しました。');
    }

    // ユーザー登録処理（Admin以上）
    public function storeUser(Request $request)
    {
        Gate::authorize('admin');

        // 自分の権限以下のロールのみ付与できる（権限昇格の防止）
        $allowedRoles = Gate::allows('system_admin') ? 'system_admin,admin,user' : 'admin,user';

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4',
            'role' => 'required|in:' . $allowedRoles,
            // 上長は管理者・システム管理者のみ
            'supervisor_id' => ['nullable', \Illuminate\Validation\Rule::exists('users', 'id')->whereIn('role', ['admin', 'system_admin'])],
            'company_name' => 'required|string|max:255',
            'assigned_under_company_ids' => 'nullable|array',
            'assigned_under_company_ids.*' => 'exists:under_companies,id',
            'stamp' => 'required|file|mimes:png,jpg,jpeg,bmp|max:5120',
        ], [
            'company_name.required' => '所属会社を選択してください。',
            'supervisor_id.exists' => '上長は管理者・システム管理者から選択してください。',
            'stamp.required' => '印鑑画像を選択してください。',
            'stamp.mimes' => '印鑑画像は png / jpeg / bmp 形式のみアップロードできます。',
            'stamp.max' => '印鑑画像は5MB以下にしてください。',
        ]);

        // 印鑑画像を保存
        $stampPath = $request->file('stamp')->store('stamps', 'public');

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'supervisor_id' => $request->supervisor_id,
            'company_name' => $request->company_name,
            'stamp_path' => $stampPath,
        ]);

        // 担当依頼先会社（複数）を紐付け
        $user->assignedUnderCompanies()->sync($request->assigned_under_company_ids ?? []);

        return back()->with('success', '新しいユーザーを登録しました。');
    }

    // ユーザーの印鑑・所属会社の編集処理（Admin以上）
    public function updateUserProfile(Request $request, User $user)
    {
        Gate::authorize('admin');

        $request->validate([
            'company_name' => 'required|string|max:255',
            'assigned_under_company_ids' => 'nullable|array',
            'assigned_under_company_ids.*' => 'exists:under_companies,id',
            'stamp' => 'nullable|file|mimes:png,jpg,jpeg,bmp|max:5120',
        ], [
            'company_name.required' => '所属会社を選択してください。',
            'stamp.mimes' => '印鑑画像は png / jpeg / bmp 形式のみアップロードできます。',
            'stamp.max' => '印鑑画像は5MB以下にしてください。',
        ]);

        $updateData = [
            'company_name' => $request->company_name,
        ];

        // 印鑑画像が選択された場合のみ差し替え（古い画像は削除）
        if ($request->hasFile('stamp')) {
            if ($user->stamp_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->stamp_path);
            }
            $updateData['stamp_path'] = $request->file('stamp')->store('stamps', 'public');
        }

        $user->update($updateData);

        // 担当依頼先会社（複数）を更新
        $user->assignedUnderCompanies()->sync($request->assigned_under_company_ids ?? []);

        return back()->with('success', $user->name . ' の情報を更新しました。');
    }

    // ユーザーの上長変更処理（Admin以上）
    public function updateUserSupervisor(Request $request, User $user)
    {
        Gate::authorize('admin');

        $request->validate([
            // 上長は管理者・システム管理者のみ
            'supervisor_id' => ['nullable', \Illuminate\Validation\Rule::exists('users', 'id')->whereIn('role', ['admin', 'system_admin'])],
        ], [
            'supervisor_id.exists' => '上長は管理者・システム管理者から選択してください。',
        ]);

        // 自分自身を上長には設定できない
        if ((int) $request->supervisor_id === $user->id) {
            return back()->with('error', '自分自身を上長に設定することはできません。');
        }

        $user->update(['supervisor_id' => $request->supervisor_id ?: null]);

        return back()->with('success', $user->name . ' の上長を更新しました。');
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

    // 依頼先会社の登録（System Adminのみ）
    public function storeUnderCompany(Request $request)
    {
        Gate::authorize('system_admin');
        
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