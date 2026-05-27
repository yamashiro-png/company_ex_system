<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\UnderCompany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * 案件の新規登録
     */
    public function store(Request $request)
    {
        Gate::authorize('admin');
        
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'pic_email' => 'nullable|email|max:255',
        ]);

        $validated['status'] = '見積もり待ち';

        $project = Project::create($validated);

        activity()
            ->causedBy($request->user())
            ->log('顧客「' . $project->customer->name . '」に新しい案件「' . $project->name . '」を登録しました');

        return back()->with('success', '案件を新規登録しました！');
    }

    /**
     * 案件作業画面（Workspace）
     */
    public function workspace(Project $project)
    {
        $project->load(['customer', 'files', 'estimates']);
        $partners = UnderCompany::orderBy('name', 'asc')->pluck('name');

        return view('projects.workspace', compact('project', 'partners'));
    }

    /**
     * STEP 2: 見積もり依頼メールの生成
     */
    public function generateEmail(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $request->validate(['partner_name' => 'required|string']);

        $project->estimates()->create([
            'partner_name' => $request->partner_name
        ]);

        $project->update(['status' => '見積もり依頼中']);

        $template = "{$request->partner_name} 様\n\n" .
                    "いつもお世話になっております。\n" .
                    "以下の案件について、お見積もりをお願いしたくご連絡いたしました。\n\n" .
                    "【案件名】: {$project->name}\n" .
                    "【機種】: {$project->device_model}\n" .
                    "【数量】: {$project->device_count}\n" .
                    "【希望納期】: {$project->completion_date}\n\n" .
                    "ご確認のほど、よろしくお願いいたします。";

        return back()->with('generated_email', $template);
    }

    /**
     * STEP 1, 3, 4: 案件情報の更新
     */
    public function update(Request $request, Project $project)
    {
        Gate::authorize('admin');

        // 1. バリデーション（final_price を追加）
        $validated = $request->validate([
            'device_model' => 'nullable|string',
            'device_count' => 'nullable|integer',
            'contract_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'price' => 'nullable|numeric',       // STEP 1用
            'final_price' => 'nullable|numeric', // STEP 4用
            'partner_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'parameter_input_type' => 'nullable|in:file,text', // 値をホワイトリストで制限
            'parameter_text' => 'nullable|string',
        ]);

        // 2. バリデーション済みデータのみを使用（$request->only() は使わない）
        $updateData = array_filter($validated, fn($v) => $v !== null);

        // 3. ファイルアップロード（STEP 1）
        if ($request->hasFile('parameter_files')) {
            // ファイル種別・サイズのバリデーション（DocumentController と同じ基準）
            $request->validate([
                'parameter_files.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            ], [
                'parameter_files.*.mimes' => 'PDF・Word・Excel・画像ファイルのみアップロード可能です。',
                'parameter_files.*.max'   => 'ファイルサイズは10MB以下にしてください。',
            ]);

            foreach ($request->file('parameter_files') as $file) {
                $path = $file->store('project_files', 'public');
                $project->files()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        // 4. 相見積もり回答の保存処理（STEP 3）
        if ($request->has('estimates')) {
            // 見積回答フィールドを個別にバリデーション
            $request->validate([
                'estimates.*.cost_price'              => 'nullable|numeric|min:0',
                'estimates.*.partner_completion_date' => 'nullable|date',
                'estimates.*.partner_message'         => 'nullable|string|max:2000',
            ]);

            $hasAnswer = false;
            foreach ($request->estimates as $id => $data) {
                if (!empty($data['cost_price'])) $hasAnswer = true;

                // $id を整数にキャストし、必ずこの案件の見積もりのみ更新する
                $project->estimates()->where('id', (int) $id)->update([
                    'cost_price'              => $data['cost_price'] ?? null,
                    'partner_completion_date' => $data['partner_completion_date'] ?? null,
                    'partner_message'         => $data['partner_message'] ?? null,
                ]);
            }

            if ($hasAnswer && $project->status === '見積もり依頼中') {
                $updateData['status'] = '見積もり依頼待ち';
            }
        }

        // 5. ステータス進行の判定（STEP 4）
        // 最終見積(final_price)と採用企業が選ばれたら「見積もり結果待ち」へ
        if ($request->filled('final_price') && $request->filled('partner_name')) {
            if ($project->status === '見積もり依頼待ち') {
                $updateData['status'] = '見積もり結果待ち';
            }
        }

        $project->update($updateData);

        return back()->with('success', '情報を更新しました。');
    }

    public function downloadFile(ProjectFile $projectFile)
    {
        if (Storage::disk('public')->exists($projectFile->file_path)) {
            return Storage::disk('public')->download($projectFile->file_path, $projectFile->file_name);
        }
        return back()->with('error', 'ファイルが見つかりません。');
    }

    public function deleteFile(ProjectFile $projectFile)
    {
        Gate::authorize('admin');

        Storage::disk('public')->delete($projectFile->file_path);
        $projectFile->delete();
        return back()->with('success', 'ファイルを削除しました。');
    }

    public function index(Request $request)
    {
        $query = Project::with(['customer', 'estimates']);

        // ホワイトリストで許可するカラム名・方向のみ受け付ける（SQLインジェクション対策）
        $allowedSorts = ['name', 'status', 'device_model', 'device_count', 'price', 'completion_date', 'created_at', 'customer_name', 'reply_status'];
        $allowedDirections = ['asc', 'desc'];

        $sort = in_array($request->get('sort'), $allowedSorts) ? $request->get('sort') : 'created_at';
        $direction = in_array($request->get('direction'), $allowedDirections) ? $request->get('direction') : 'desc';

        // 検索フィルター
        if ($request->filled('search_name')) {
            $query->where('name', 'like', '%' . $request->search_name . '%');
        }
        if ($request->filled('search_customer')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search_customer . '%');
            });
        }
        if ($request->filled('search_status')) {
            $query->where('status', $request->search_status);
        }
        if ($request->filled('search_device')) {
            $query->where('device_model', 'like', '%' . $request->search_device . '%');
        }

        if ($sort === 'reply_status') {
            $query->orderByRaw(
                '(SELECT COUNT(*) FROM project_estimates WHERE project_id = projects.id AND cost_price IS NOT NULL) ' . $direction
            );
        } else {
            $query->orderBy($sort, $direction);
        }

        $projects = $query->get();
        return view('projects.index', compact('projects'));
    }

    public function generateFinalEmail(Request $request, Project $project)
    {
        Gate::authorize('admin');

        // 顧客情報の取得（リレーションが貼られている前提）
        $customer = $project->customer;

        // completion_date が未設定の場合も安全に処理する
        $completionDateText = $project->completion_date
            ? \Carbon\Carbon::parse($project->completion_date)->format('Y年m月d日')
            : '未定';

        $template = "{$customer->name}\n" .
                    "{$project->pic_name} 様\n\n" .
                    "いつも大変お世話になっております。\n" .
                    "JCCの〇〇（ご自身の名前）でございます。\n\n" .
                    "過日ご依頼いただきました案件につきまして、\n" .
                    "お見積もりが整いましたので、以下の通りご案内申し上げます。\n\n" .
                    "【案件名】: {$project->name}\n" .
                    "【御見積金額】: ¥" . number_format($project->final_price) . "- (税別)\n" .
                    "【完了予定日】: {$completionDateText}\n\n" .
                    "ご査収のほど、よろしくお願い申し上げます。";

        return back()->with('generated_final_email', $template);
    }
}