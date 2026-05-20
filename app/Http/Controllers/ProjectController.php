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
        ]);

        // 2. 送信されたデータだけを抽出（上書き防止）
        $updateData = $request->only([
            'device_model', 'device_count', 'contract_date', 
            'completion_date', 'price', 'final_price', 'partner_name', 'notes', 'parameter_input_type', 'parameter_text'
        ]);

        // 3. ファイルアップロード（STEP 1）
        if ($request->hasFile('parameter_files')) {
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
            $hasAnswer = false;
            foreach ($request->estimates as $id => $data) {
                if (!empty($data['cost_price'])) $hasAnswer = true;
                
                $project->estimates()->where('id', $id)->update([
                    'cost_price' => $data['cost_price'],
                    'partner_completion_date' => $data['partner_completion_date'],
                    'partner_message' => $data['partner_message'],
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
        Storage::disk('public')->delete($projectFile->file_path);
        $projectFile->delete();
        return back()->with('success', 'ファイルを削除しました。');
    }

    public function index(Request $request)
    {
        $query = Project::with(['customer', 'estimates']);
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        if ($sort === 'reply_status') {
            $query->orderByRaw('(SELECT COUNT(*) FROM project_estimates WHERE project_id = projects.id AND cost_price IS NOT NULL) ' . $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $projects = $query->get();
        return view('projects.index', compact('projects'));
    }

    public function generateFinalEmail(Request $request, Project $project)
    {
        // 顧客情報の取得（リレーションが貼られている前提）
        $customer = $project->customer;
        
        $template = "{$customer->name}\n" .
                    "{$project->pic_name} 様\n\n" .
                    "いつも大変お世話になっております。\n" .
                    "JCCの〇〇（ご自身の名前）でございます。\n\n" .
                    "過日ご依頼いただきました案件につきまして、\n" .
                    "お見積もりが整いましたので、以下の通りご案内申し上げます。\n\n" .
                    "【案件名】: {$project->name}\n" .
                    "【御見積金額】: ¥" . number_format($project->final_price) . "- (税別)\n" .
                    "【完了予定日】: " . (\Carbon\Carbon::parse($project->completion_date)->format('Y年m月d日')) . "\n\n" .
                    "ご査収のほど、よろしくお願い申し上げます。";

        return back()->with('generated_final_email', $template);
    }
}