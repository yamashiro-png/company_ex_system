<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EstimateEditRequest;
use App\Models\Project;
use App\Models\ProjectAccessory;
use App\Models\ProjectArrival;
use App\Models\ProjectDelivery;
use App\Models\ProjectEstimate;
use App\Models\ProjectFile;
use App\Models\ProjectShipment;
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
        // 案件登録は全ロール（user / admin / system_admin）が可能

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'pic_email' => 'nullable|email|max:255',
            'own_pic_id' => 'required|exists:users,id',
            // STEP 1 の基本情報（登録時に入力）
            'device_model' => 'required|string|max:255',
            'os' => 'required|in:' . implode(',', Project::OS_OPTIONS),
            'device_count' => 'required|integer|min:1',
            'has_accessory' => 'required|in:' . implode(',', Project::ACCESSORY_OPTIONS),
            'contract_date' => 'required|date',
            'arrival_method' => 'required|in:' . implode(',', Project::METHOD_OPTIONS),
            'completion_date' => 'required|date',
            'delivery_method' => 'required|in:' . implode(',', Project::METHOD_OPTIONS),
            'parameter_input_type' => 'nullable|in:file,text',
            'parameter_text' => 'nullable|string',
            'parameter_files.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ], [
            'own_pic_id.required' => '自社担当者を選択してください。',
            'device_model.required' => '対象機種名を入力してください。',
            'os.required' => 'OSを選択してください。',
            'device_count.required' => '台数を入力してください。',
            'has_accessory.required' => '付属品有無を選択してください。',
            'contract_date.required' => '開始予定日を入力してください。',
            'arrival_method.required' => '入荷方法を選択してください。',
            'completion_date.required' => '納品予定日を入力してください。',
            'delivery_method.required' => '納品方法を選択してください。',
            'parameter_files.*.mimes' => '手順書はPDF・Word・Excel・画像ファイルのみアップロード可能です。',
            'parameter_files.*.max'   => 'ファイルサイズは10MB以下にしてください。',
        ]);

        unset($validated['parameter_files']);
        $validated['status'] = '見積もり待ち';

        // 案件番号を通番で採番（同時登録でも重複しないようロックして採番）
        $project = \DB::transaction(function () use ($validated) {
            $nextNumber = (Project::query()->lockForUpdate()->max('project_number') ?? 0) + 1;
            $validated['project_number'] = $nextNumber;
            return Project::create($validated);
        });

        // パラメーターファイルの保存
        if ($request->hasFile('parameter_files')) {
            foreach ($request->file('parameter_files') as $file) {
                $path = $file->store('project_files', 'public');
                $project->files()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        activity()
            ->causedBy($request->user())
            ->log('顧客「' . $project->customer->name . '」に新しい案件「' . $project->name . '」（案件番号：' . $project->project_number . '）を登録しました');

        return back()->with('success', '案件を登録しました（案件番号：' . str_pad($project->project_number, 6, '0', STR_PAD_LEFT) . '）');
    }

    /**
     * 案件作業画面（Workspace）
     */
    public function workspace(Project $project)
    {
        $project->load([
            'customer',
            'ownPic',
            'files',
            'shipments',
            'arrivals',
            'deliveries',
            'invoices',
            'projectAccessories.accessory',
            'editRequests',
            'priceHistories.changedBy',
            'priceHistories.approvedBy',
            'estimates.exchanges',
            'estimates.editRequests',
            'estimates.priceHistories.changedBy',
            'estimates.priceHistories.approvedBy',
            'step5EditRequests',
        ]);
        $partners = UnderCompany::orderBy('name', 'asc')->pluck('name');
        $accessoryMaster = \App\Models\Accessory::orderBy('name')->get();

        return view('projects.workspace', compact('project', 'partners', 'accessoryMaster'));
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
                    "【OS】: {$project->os}\n" .
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
            'os' => 'nullable|in:' . implode(',', \App\Models\Project::OS_OPTIONS),
            'device_count' => 'nullable|integer',
            'has_accessory' => 'nullable|in:' . implode(',', \App\Models\Project::ACCESSORY_OPTIONS),
            'contract_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'arrival_method' => 'nullable|in:' . implode(',', \App\Models\Project::METHOD_OPTIONS),   // STEP 1: 入荷方法
            'delivery_method' => 'nullable|in:' . implode(',', \App\Models\Project::METHOD_OPTIONS),  // STEP 1: 納品方法
            'arrival_date' => 'nullable|date',          // STEP 6: 入荷日
            'arrival_count' => 'nullable|integer|min:0', // STEP 6: 入荷台数
            'shipping_date' => 'nullable|date',           // STEP 7: 出荷日
            'shipping_cost' => 'nullable|numeric|min:0',  // STEP 7: 出荷費用
            'billing_date' => 'nullable|date',                    // STEP 8: 請求日
            'billing_count' => 'nullable|integer|min:0',          // STEP 8: 請求台数
            'billing_shipping_cost' => 'nullable|numeric|min:0',  // STEP 8: 請求する出荷費用
            'final_price' => 'nullable|numeric', // STEP 4用
            'quote_shipping_enabled' => 'nullable|in:0,1', // STEP 4: 送料入力ON/OFF
            'quote_shipping_fee' => 'nullable|numeric|min:0', // STEP 4: 送料金額
            'selected_estimate_id' => 'nullable|integer', // STEP 4: 採用する見積もり（ID で特定）
            'notes' => 'nullable|string',
            'parameter_input_type' => 'nullable|in:file,text', // 値をホワイトリストで制限
            'parameter_text' => 'nullable|string',
        ]);

        // 2. バリデーション済みデータのみを使用（$request->only() は使わない）
        $updateData = array_filter($validated, fn($v) => $v !== null);

        // STEP 4: 採用する見積もりを ID で特定（同名・金額違いの取り違えを防止）
        $selectedEstimate = $request->filled('selected_estimate_id')
            ? $project->estimates()->find((int) $request->selected_estimate_id)
            : null;
        if ($selectedEstimate) {
            $updateData['partner_name'] = $selectedEstimate->partner_name;
        }

        // STEP 4: 送料入力のON/OFF処理（OFF時は送料を消し、備考に「実費精算」を反映）
        if ($request->has('quote_shipping_enabled')) {
            $shippingEnabled = $request->quote_shipping_enabled == '1';
            $updateData['quote_shipping_enabled'] = $shippingEnabled;
            $updateData['quote_shipping_fee'] = $shippingEnabled && $request->filled('quote_shipping_fee')
                ? $request->quote_shipping_fee
                : null;
        }

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
                'estimates.*.cost_price'      => 'nullable|numeric|min:0',
                'estimates.*.partner_message' => 'nullable|string|max:2000',
            ]);

            // 重複チェック：会社名と金額が完全に同じ見積もりは登録不可
            // （更新後の全見積もりの「会社名＋金額」を突き合わせて判定）
            $resultingPairs = [];
            foreach ($project->estimates as $est) {
                $newCost = $est->cost_price !== null ? (float) $est->cost_price : null;
                $submitted = $request->estimates[$est->id] ?? null;
                if (is_array($submitted) && array_key_exists('cost_price', $submitted)) {
                    $v = $submitted['cost_price'];
                    $newCost = ($v !== '' && $v !== null) ? (float) $v : null;
                }
                if ($newCost === null) continue;

                $pairKey = $est->partner_name . '|' . $newCost;
                if (isset($resultingPairs[$pairKey])) {
                    $dupMessage = '「' . $est->partner_name . '」で金額が同じ見積もり（¥' . number_format($newCost) . '）が重複しています。会社名と金額が完全に同じ見積もりは登録できません。金額を変えてください。';
                    if ($request->expectsJson()) {
                        return response()->json(['ok' => false, 'message' => $dupMessage], 422);
                    }
                    return back()->with('error', $dupMessage);
                }
                $resultingPairs[$pairKey] = true;
            }

            $hasAnswer = false;
            foreach ($request->estimates as $id => $data) {
                if (!empty($data['cost_price'])) $hasAnswer = true;

                // $id を整数にキャストし、必ずこの案件の見積もりのみ対象にする
                $estimate = $project->estimates()->find((int) $id);
                if (!$estimate) continue;

                // 送信されたフィールドのみ更新する（備考だけの保存で金額が消えないように）
                $updateFields = [];
                if (array_key_exists('cost_price', $data)) {
                    $updateFields['cost_price'] = $data['cost_price'] !== '' ? $data['cost_price'] : null;
                }
                if (array_key_exists('partner_message', $data)) {
                    $updateFields['partner_message'] = $data['partner_message'] !== '' ? $data['partner_message'] : null;
                }

                if ($updateFields === []) continue;

                // 金額が変わる場合は変更履歴を自動記録
                if (array_key_exists('cost_price', $updateFields)) {
                    $oldPrice = $estimate->cost_price !== null ? (float) $estimate->cost_price : null;
                    $newPrice = $updateFields['cost_price'] !== null ? (float) $updateFields['cost_price'] : null;

                    if ($newPrice !== null && $oldPrice !== $newPrice) {
                        $estimate->priceHistories()->create([
                            'old_cost_price' => $oldPrice,
                            'new_cost_price' => $newPrice,
                            'changed_by'     => $request->user()->id,
                        ]);
                    }
                }

                $estimate->update($updateFields);
            }

            if ($hasAnswer && $project->status === '見積もり依頼中') {
                $updateData['status'] = '見積もり依頼待ち';
            }
        }

        // 5. ステータス進行の判定（STEP 4）
        // 最終見積(final_price)と採用企業が選ばれたら「見積もり結果待ち」へ
        if ($request->filled('final_price') && $selectedEstimate) {
            if ($project->status === '見積もり依頼待ち') {
                $updateData['status'] = '見積もり結果待ち';
            }
        }

        // 最終見積が変わる場合は変更履歴を自動記録（STEP 4）
        if (array_key_exists('final_price', $updateData)) {
            $oldFinalPrice = $project->final_price !== null ? (float) $project->final_price : null;
            $newFinalPrice = (float) $updateData['final_price'];
            $oldPartner = $project->partner_name;
            $newPartner = $updateData['partner_name'] ?? $oldPartner;

            if ($oldFinalPrice !== $newFinalPrice || $oldPartner !== $newPartner) {
                $project->priceHistories()->create([
                    'old_final_price'  => $oldFinalPrice,
                    'new_final_price'  => $newFinalPrice,
                    'old_partner_name' => $oldPartner,
                    'new_partner_name' => $newPartner,
                    'changed_by'       => $request->user()->id,
                ]);
            }
        }

        $project->update($updateData);

        // STEP 4: 採用見積もりが確定したら、各依頼先の受注/失注を反映（ID で特定）
        if ($selectedEstimate) {
            $this->applyEstimateResults($project, $selectedEstimate->id);
        }

        if ($request->expectsJson()) {
            // STEP 4（最終見積）は全社回答済みで表示される。表示が新たに必要になったら画面側で再描画する
            $allAnswered = $project->estimates()->count() > 0
                && $project->estimates()->get()->every(fn ($e) => !empty($e->cost_price) || !empty($e->partner_completion_date) || !empty($e->partner_message));

            return response()->json([
                'ok'           => true,
                'message'      => '保存しました。',
                'all_answered' => $allAnswered,
            ]);
        }

        return back()->with('success', '情報を更新しました。');
    }

    /**
     * 最終選定の結果を各依頼先（見積もり）に反映する
     * 採用された見積もり = 受注 / それ以外 = 失注（同名・金額違いを正しく区別するため ID で判定）
     */
    private function applyEstimateResults(Project $project, int $selectedEstimateId): void
    {
        foreach ($project->estimates as $estimate) {
            $estimate->update([
                'result' => $estimate->id === $selectedEstimateId ? '受注' : '失注',
            ]);
        }
    }

    /**
     * STEP 3: 依頼先会社とのやり取り記録の追加
     */
    public function storeExchange(Request $request, ProjectEstimate $estimate)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'exchanged_at' => 'required|date',
            'inquiry'      => 'nullable|required_without:reply|string|max:5000',
            'reply'        => 'nullable|string|max:5000',
        ], [
            'exchanged_at.required'       => 'やり取りの日付を入力してください。',
            'inquiry.required_without'    => '問い合わせ内容または回答内容のどちらかを入力してください。',
        ]);

        $estimate->exchanges()->create($validated);

        return back()->with('success', $estimate->partner_name . ' とのやり取りを記録しました。');
    }

    /**
     * STEP 3: 見積金額の編集申請（上長への承認依頼）
     */
    public function storeEditRequest(Request $request, ProjectEstimate $estimate)
    {
        Gate::authorize('admin');

        $user = $request->user();

        // 上長が未登録の場合は申請できない
        if (!$user->supervisor_id) {
            return back()->with('error', '上長が登録されていないため申請できません。マスター設定で上長を登録してください。');
        }

        // 同じ見積もりに承認待ちの申請がある場合は二重申請を防ぐ
        if ($estimate->editRequests()->where('status', 'pending')->exists()) {
            return back()->with('error', 'この見積もりには承認待ちの申請が既にあります。');
        }

        $validated = $request->validate([
            'reason'               => 'required|string|max:2000',
            'requested_cost_price' => 'required|numeric|min:0',
        ], [
            'reason.required'               => '編集理由を入力してください。',
            'requested_cost_price.required' => '申請する金額を入力してください。',
            'requested_cost_price.numeric'  => '金額は数値で入力してください。',
        ]);

        $estimate->editRequests()->create([
            'requester_id'         => $user->id,
            'supervisor_id'        => $user->supervisor_id,
            'reason'               => $validated['reason'],
            'requested_cost_price' => $validated['requested_cost_price'],
            'status'               => 'pending',
        ]);

        activity()
            ->causedBy($user)
            ->log($estimate->partner_name . ' の見積金額の編集を ' . $user->supervisor->name . ' に申請しました');

        return back()->with('success', $user->supervisor->name . ' に編集申請を送信しました。承認をお待ちください。');
    }

    /**
     * STEP 4: 最終見積の編集申請（上長への承認依頼）
     */
    public function storeProjectEditRequest(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $user = $request->user();

        // 上長が未登録の場合は申請できない
        if (!$user->supervisor_id) {
            return back()->with('error', '上長が登録されていないため申請できません。マスター設定で上長を登録してください。');
        }

        // 同じ案件に承認待ちの申請がある場合は二重申請を防ぐ
        if ($project->editRequests()->where('status', 'pending')->exists()) {
            return back()->with('error', 'この案件の最終見積には承認待ちの申請が既にあります。');
        }

        $validated = $request->validate([
            'reason'                  => 'required|string|max:2000',
            'requested_final_price'   => 'required|numeric|min:0',
            'requested_estimate_id'   => ['required', \Illuminate\Validation\Rule::exists('project_estimates', 'id')->where('project_id', $project->id)],
        ], [
            'reason.required'                 => '編集理由を入力してください。',
            'requested_final_price.required'  => '申請する金額を入力してください。',
            'requested_final_price.numeric'   => '金額は数値で入力してください。',
            'requested_estimate_id.required'  => '採用する依頼先企業を選択してください。',
            'requested_estimate_id.exists'    => '採用する依頼先企業の選択が正しくありません。',
        ]);

        // 採用する見積もり（会社名を申請内容に記録）
        $requestedEstimate = $project->estimates()->findOrFail((int) $validated['requested_estimate_id']);

        $project->editRequests()->create([
            'requester_id'            => $user->id,
            'supervisor_id'           => $user->supervisor_id,
            'reason'                  => $validated['reason'],
            'requested_final_price'   => $validated['requested_final_price'],
            'requested_estimate_id'   => $requestedEstimate->id,
            'requested_partner_name'  => $requestedEstimate->partner_name,
            'status'                  => 'pending',
        ]);

        activity()
            ->causedBy($user)
            ->log('案件「' . $project->name . '」の最終見積の編集を ' . $user->supervisor->name . ' に申請しました');

        return back()->with('success', $user->supervisor->name . ' に最終見積の編集申請を送信しました。承認をお待ちください。');
    }

    /**
     * STEP 5: 受注情報の編集申請（上長への承認依頼）
     */
    public function storeStep5EditRequest(Request $request, Project $project)
    {
        Gate::authorize('admin');
        $user = $request->user();

        if (!$user->supervisor_id) {
            return back()->with('error', '上長が登録されていないため申請できません。');
        }

        // 重複申請チェック
        if ($project->step5EditRequests()->where('status', 'pending')->exists()) {
            return back()->with('error', 'この案件の受注情報には、既に承認待ちの申請があります。');
        }

        $validated = $request->validate([
            'reason'                    => 'required|string|max:2000',
            'requested_device_model'    => 'required|string|max:255',
            'requested_device_count'    => 'required|integer|min:1',
            'requested_contract_date'   => 'required|date',
            'requested_completion_date' => 'required|date',
            'requested_delivery_method' => 'required|in:' . implode(',', Project::METHOD_OPTIONS),
        ]);

        $project->step5EditRequests()->create([
            'requester_id'              => $user->id,
            'supervisor_id'             => $user->supervisor_id,
            'reason'                    => $validated['reason'],
            'requested_device_model'    => $validated['requested_device_model'],
            'requested_device_count'    => $validated['requested_device_count'],
            'requested_contract_date'   => $validated['requested_contract_date'],
            'requested_completion_date' => $validated['requested_completion_date'],
            'requested_delivery_method' => $validated['requested_delivery_method'],
            'status'                    => 'pending',
        ]);

        activity()
            ->causedBy($user)
            ->log('案件「' . $project->name . '」の受注情報の編集を ' . $user->supervisor->name . ' に申請しました');

        return back()->with('success', $user->supervisor->name . ' に編集申請を送信しました。承認をお待ちください。');
    }

    /**
     * STEP 5・6: 区分付きファイルのアップロード（受注書・手順書・入荷パラメータ）
     */
    public function uploadOrderFiles(Request $request, Project $project)
    {
        Gate::authorize('admin');

        // 区分ごとの表示名（ホワイトリストを兼ねる）
        $categoryLabels = [
            'order_form'        => '受注書',
            'manual'            => '手順書',
            'arrival_parameter' => 'パラメータ',
            'shipping_data'     => '出荷データ',
        ];

        $request->validate([
            'category'      => 'required|in:' . implode(',', array_keys($categoryLabels)),
            'order_files'   => 'required|array',
            'order_files.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ], [
            'order_files.required' => 'アップロードするファイルを選択してください。',
            'order_files.*.mimes'  => 'PDF・Word・Excel・画像ファイルのみアップロード可能です。',
            'order_files.*.max'    => 'ファイルサイズは10MB以下にしてください。',
        ]);

        $label = $categoryLabels[$request->category];

        foreach ($request->file('order_files') as $file) {
            $path = $file->store('project_files', 'public');
            $project->files()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'category'  => $request->category,
            ]);
        }

        activity()
            ->causedBy($request->user())
            ->log('案件「' . $project->name . '」に' . $label . 'ファイルをアップロードしました');

        return back()->with('success', $label . 'ファイルをアップロードしました。');
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
        $allowedSorts = ['project_number', 'name', 'status', 'device_model', 'device_count', 'price', 'completion_date', 'created_at', 'customer_name', 'reply_status'];
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
        $customers = \App\Models\Customer::orderBy('name')->get();
        $users = \App\Models\User::orderBy('name')->get();
        return view('projects.index', compact('projects', 'customers', 'users'));
    }

    /**
     * 生成したPDFを案件の添付ファイルとして自動保存する
     */
    private function storeGeneratedPdf(Project $project, string $content, string $fileName, string $category, bool $replace = false): void
    {
        // 同区分の既存ファイルを置き換える場合は古いものを削除（見積書など最新だけ残したいケース）
        if ($replace) {
            foreach ($project->files()->where('category', $category)->get() as $old) {
                Storage::disk('public')->delete($old->file_path);
                $old->delete();
            }
        }

        $path = 'project_files/' . \Illuminate\Support\Str::uuid() . '.pdf';
        Storage::disk('public')->put($path, $content);

        $project->files()->create([
            'file_path' => $path,
            'file_name' => $fileName,
            'category'  => $category,
        ]);
    }

    /**
     * STEP 4: 見積書PDFの生成
     */
    public function generateQuotationPdf(Project $project)
    {
        Gate::authorize('admin');

        // 最終見積が確定していない場合は生成できない
        if (!$project->final_price) {
            return back()->with('error', '最終見積が確定していないため、見積書を生成できません。');
        }

        $project->load(['customer', 'ownPic']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('projects.quotation_pdf', compact('project'));

        $fileName = '見積書_' . $project->documentNumber('M') . '_' . $project->name . '.pdf';

        // 1回だけレンダリングし、同じ内容を保存＆ダウンロードに使う（二重レンダリング防止）
        $content = $pdf->output();
        $this->storeGeneratedPdf($project, $content, $fileName, 'quotation', true);

        activity()
            ->causedBy(auth()->user())
            ->log('案件「' . $project->name . '」の見積書PDFを生成しました');

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition(
                \Symfony\Component\HttpFoundation\HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, 'quotation.pdf'
            ),
        ]);
    }

    /**
     * STEP 4: 受注・失注の最終判定
     */
    public function storeDecision(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $request->validate([
            'decision' => 'required|in:won,lost',
        ]);

        // 最終見積が未確定なら判定できない
        if (!$project->final_price) {
            return back()->with('error', '最終見積が確定していないため、受注・失注の判定はできません。');
        }

        if ($request->decision === 'won') {
            $project->update(['status' => '受注確定']);
            activity()->causedBy($request->user())->log('案件「' . $project->name . '」を受注しました');
            return back()->with('success', '受注おめでとうございます！ステータスを「受注確定」に変更しました。');
        }

        $project->update(['status' => '失注']);
        activity()->causedBy($request->user())->log('案件「' . $project->name . '」が失注となりました');
        return back()->with('success', 'ステータスを「失注」に変更しました。');
    }

    /**
     * STEP 5: 受注情報の確定（受注書作成）→ 入荷登録情報待ち
     */
    public function confirmOrderInfo(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'device_model'    => 'required|string|max:255',
            'device_count'    => 'required|integer|min:1',
            'contract_date'   => 'required|date',
            'completion_date' => 'required|date',
            'delivery_method' => 'required|in:' . implode(',', Project::METHOD_OPTIONS),
        ], [
            'device_model.required'    => '機種名を入力してください。',
            'device_count.required'    => '台数を入力してください。',
            'contract_date.required'   => '開始予定日を入力してください。',
            'completion_date.required' => '納品予定日を入力してください。',
            'delivery_method.required' => '納品方法を選択してください。',
        ]);

        $validated['status'] = '入荷登録情報待ち';
        $project->update($validated);

        activity()->causedBy($request->user())->log('案件「' . $project->name . '」の受注情報を確定しました');

        return back()->with('success', '受注情報を確定しました。ステータスを「入荷登録情報待ち」に変更しました。');
    }

    /**
     * STEP 6: 入荷情報の登録 → 出荷情報登録待ち
     */
    public function registerArrival(Request $request, Project $project)
    {
        Gate::authorize('admin');

        if (!$project->files()->where('category', 'manual')->exists()) {
            return back()->with('error', '手順書ファイルがアップロードされていません。先にファイルをアップロードしてください。');
        }

        if (!$project->files()->where('category', 'arrival_parameter')->exists()) {
            return back()->with('error', 'パラメータファイルがアップロードされていません。先にファイルをアップロードしてください。');
        }

        $validated = $request->validate([
            'arrival_date'  => 'required|date',
            'arrival_count' => 'required|integer|min:0',
        ], [
            'arrival_date.required'  => '入荷日を入力してください。',
            'arrival_count.required' => '入荷台数を入力してください。',
        ]);

        $orderedCount = (int) $project->device_count;
        if ($orderedCount > 0 && $validated['arrival_count'] > $orderedCount) {
            return back()->with('error', '入荷台数（' . number_format($validated['arrival_count']) . '台）が受注台数（' . number_format($orderedCount) . '台）を超えています。正しい台数を入力してください。');
        }

        $validated['status'] = '出荷情報登録待ち';
        $project->update($validated);

        activity()->causedBy($request->user())->log('案件「' . $project->name . '」の入荷情報を登録しました');

        return back()->with('success', '入荷情報を登録しました。ステータスを「出荷情報登録待ち」に変更しました。');
    }

    /**
     * STEP 6（分納）: 入荷記録を1件追加（複数回の分割入荷に対応）
     * 1件目の登録でステータスを「出荷情報登録待ち」へ進める
     */
    public function addArrival(Request $request, Project $project)
    {
        Gate::authorize('admin');

        if (!$project->files()->where('category', 'manual')->exists()) {
            return back()->with('error', '手順書ファイルがアップロードされていません。先にファイルをアップロードしてください。');
        }

        if (!$project->files()->where('category', 'arrival_parameter')->exists()) {
            return back()->with('error', 'パラメータファイルがアップロードされていません。先にファイルをアップロードしてください。');
        }

        $validated = $request->validate([
            'arrived_date'  => 'required|date',
            'arrived_count' => 'required|integer|min:1',
        ], [
            'arrived_date.required'  => '入荷日を入力してください。',
            'arrived_count.required' => '入荷台数を入力してください。',
        ]);

        $addingCount  = (int) $validated['arrived_count'];
        $currentTotal = (int) $project->arrivals()->sum('arrived_count');
        $orderedCount = (int) $project->device_count;

        if ($orderedCount > 0 && $currentTotal + $addingCount > $orderedCount) {
            $remain = $orderedCount - $currentTotal;
            return back()->with('error', '入荷台数の合計が受注台数（' . number_format($orderedCount) . '台）を超えています。今回追加できる残りは ' . number_format(max($remain, 0)) . ' 台です。');
        }

        $project->arrivals()->create($validated);
        $this->syncArrivalSummary($project);

        // 最初の1件が登録されたらステータスを進める
        if (in_array($project->status, ['入荷登録情報待ち', '物品入荷待ち', '受注確定'], true)) {
            $project->update(['status' => '出荷情報登録待ち']);
        }

        return back()->with('success', '入荷情報を登録しました。');
    }

    /**
     * STEP 6（分納）: 入荷記録の削除
     */
    public function deleteArrival(Request $request, Project $project, ProjectArrival $arrival)
    {
        Gate::authorize('admin');

        if ($arrival->project_id !== $project->id) {
            abort(404);
        }
        $arrival->delete();
        $this->syncArrivalSummary($project);

        return back()->with('success', '入荷記録を削除しました。');
    }

    /**
     * 分割入荷の合計を案件本体に反映（請求台数の初期値などで利用）
     */
    private function syncArrivalSummary(Project $project): void
    {
        $project->load('arrivals');
        $project->update([
            'arrival_count' => (int) $project->arrivals->sum('arrived_count'),
            'arrival_date'  => $project->arrivals->max('arrived_date'),
        ]);
    }

    /**
     * STEP 5: 付属品を追加（商品名＋台数）
     */
    public function addProjectAccessory(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'accessory_id'  => 'required|exists:accessories,id',
            'planned_count' => 'required|integer|min:1',
        ], [
            'accessory_id.required'  => '付属品を選択してください。',
            'planned_count.required' => '台数を入力してください。',
        ]);

        $project->projectAccessories()->create([
            'accessory_id'  => $validated['accessory_id'],
            'planned_count' => $validated['planned_count'],
            'arrived_count' => 0,
        ]);

        return back()->with('success', '付属品を追加しました。');
    }

    /**
     * STEP 5: 付属品の削除
     */
    public function deleteProjectAccessory(Request $request, Project $project, ProjectAccessory $projectAccessory)
    {
        Gate::authorize('admin');

        if ($projectAccessory->project_id !== $project->id) {
            abort(404);
        }
        $projectAccessory->delete();

        return back()->with('success', '付属品を削除しました。');
    }

    /**
     * STEP 6: 付属品の入荷数を更新（複数まとめて）
     */
    public function updateAccessoryArrivals(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $request->validate([
            'accessories'                 => 'array',
            'accessories.*.arrived_count' => 'nullable|integer|min:0',
        ]);

        foreach ($request->input('accessories', []) as $id => $data) {
            $pa = $project->projectAccessories()->find((int) $id);
            if (!$pa) continue;
            $count = isset($data['arrived_count']) && $data['arrived_count'] !== '' ? (int) $data['arrived_count'] : 0;
            $pa->update(['arrived_count' => min($count, $pa->planned_count)]);
        }

        return back()->with('success', '付属品の入荷数を更新しました。');
    }

    /**
     * STEP 7: 出荷予定の追加（分納の1行分・仮登録）
     */
    public function addShipment(Request $request, Project $project)
    {
        Gate::authorize('admin');

        // 出荷が全て終わるまで（出荷情報登録待ち／出荷情報待ち）は編集可。それ以降は不可
        if (!in_array($project->status, ['出荷情報登録待ち', '出荷情報待ち'], true)) {
            return back()->with('error', 'この段階では出荷予定を編集できません。');
        }

        $validated = $request->validate([
            'shipments'                 => 'required|array|min:1',
            'shipments.*.planned_date'  => 'required|date',
            'shipments.*.planned_count' => 'required|integer|min:1',
        ], [
            'shipments.required'                 => '出荷予定を1件以上入力してください。',
            'shipments.*.planned_date.required'  => '出荷予定日を入力してください。',
            'shipments.*.planned_count.required' => '出荷予定台数を入力してください。',
            'shipments.*.planned_count.min'      => '出荷予定台数は1以上で入力してください。',
        ]);

        // 追加分の合計が受注台数を超えないかチェック（既存の予定との合算）
        $addingTotal  = array_sum(array_column($validated['shipments'], 'planned_count'));
        $currentTotal = (int) $project->shipments()->sum('planned_count');
        $orderedCount = (int) $project->device_count;

        if ($orderedCount > 0 && $currentTotal + $addingTotal > $orderedCount) {
            $remain = $orderedCount - $currentTotal;
            return back()->with('error', '出荷予定台数の合計が受注台数（' . number_format($orderedCount) . '台）を超えています。今回追加できる残りは ' . number_format(max($remain, 0)) . ' 台です。');
        }

        foreach ($validated['shipments'] as $row) {
            $project->shipments()->create([
                'planned_date'  => $row['planned_date'],
                'planned_count' => $row['planned_count'],
            ]);
        }

        return back()->with('success', count($validated['shipments']) . '件の出荷予定を追加しました。');
    }

    /**
     * STEP 7: 出荷予定の削除（仮登録中のみ）
     */
    public function deleteShipment(Request $request, Project $project, ProjectShipment $shipment)
    {
        Gate::authorize('admin');

        if ($shipment->project_id !== $project->id) {
            abort(404);
        }

        // 出荷が全て終わるまで（出荷情報登録待ち／出荷情報待ち）は削除可。それ以降は不可
        if (!in_array($project->status, ['出荷情報登録待ち', '出荷情報待ち'], true)) {
            return back()->with('error', 'この段階では出荷予定を削除できません。');
        }

        $shipment->delete();

        return back()->with('success', '出荷予定を削除しました。');
    }

    /**
     * STEP 7: 出荷予定の確定 → 出荷情報待ち
     * 分納で月をまたぐ場合に備え、全数に達していなくても1件以上あれば確定できる。
     * （超過は不可。残数は後から追加して再確定できる）
     */
    public function confirmShipments(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $totalPlanned = (int) $project->shipments()->sum('planned_count');
        $orderedCount = (int) $project->device_count;

        if ($totalPlanned < 1) {
            return back()->with('error', '出荷予定を1件以上追加してから確定してください。');
        }
        if ($totalPlanned > $orderedCount) {
            return back()->with('error', '出荷予定台数の合計（' . number_format($totalPlanned) . '台）が受注台数（' . number_format($orderedCount) . '台）を超えています。');
        }

        $project->update([
            'shipment_confirmed_at' => now(),
            'status' => '出荷情報待ち',
        ]);

        $remaining = $orderedCount - $totalPlanned;
        $msg = '出荷予定を確定しました。ステータスを「出荷情報待ち」に変更しました。';
        if ($remaining > 0) {
            $msg .= '（未出荷の残 ' . number_format($remaining) . ' 台は、後で出荷予定を追加して再度確定できます）';
        }

        activity()->causedBy($request->user())->log('案件「' . $project->name . '」の出荷予定を確定しました');

        return back()->with('success', $msg);
    }

    /**
     * STEP 8: 納期情報を1件追加（出荷便ごと・複数回登録可）
     * 1件目の登録でステータスを「納品済み」へ進める
     */
    public function addDelivery(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'shipment_id'   => ['nullable', \Illuminate\Validation\Rule::exists('project_shipments', 'id')->where('project_id', $project->id)],
            'shipped_date'  => 'required|date',
            'shipped_count' => 'nullable|integer|min:1',
            'shipping_cost' => 'nullable|numeric|min:0',
        ], [
            'shipped_date.required'  => '出荷日を入力してください。',
            'shipment_id.exists'     => '対応する出荷予定の選択が正しくありません。',
        ]);

        $project->deliveries()->create($validated);

        // 案件本体にも最新の出荷日・出荷費用を保持（請求の初期値などで利用）
        $project->load('deliveries');
        $update = [
            'shipping_date' => $project->deliveries->max('shipped_date'),
            'shipping_cost' => $validated['shipping_cost'] ?? $project->shipping_cost,
        ];
        // 1件目を登録したらステータスを進める
        if (in_array($project->status, ['出荷情報待ち'], true)) {
            $update['status'] = '納品済み';
        }
        $project->update($update);

        activity()->causedBy($request->user())->log('案件「' . $project->name . '」の納期情報を登録しました');

        return back()->with('success', '納期情報を登録しました。');
    }

    /**
     * STEP 8: 納期情報の削除
     */
    public function deleteDelivery(Request $request, Project $project, ProjectDelivery $delivery)
    {
        Gate::authorize('admin');

        if ($delivery->project_id !== $project->id) {
            abort(404);
        }
        $delivery->delete();

        return back()->with('success', '納期情報を削除しました。');
    }

    /**
     * STEP 9: 請求書PDFの生成（入力値を保存してからPDFをダウンロード）
     */
    public function generateInvoicePdf(Request $request, Project $project)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'billing_date'  => 'required|date',
        ], [
            'billing_month.required'    => '請求対象月を選択してください。',
            'billing_month.date_format' => '請求対象月の形式が正しくありません。',
            'billing_date.required'     => '請求日を選択してください。',
        ]);

        // AJAX（画面遷移なし）のときはエラーを JSON で返す
        $wantsJson = $request->expectsJson();
        $fail = fn (string $msg) => $wantsJson
            ? response()->json(['ok' => false, 'message' => $msg], 422)
            : back()->with('error', $msg);

        $billingMonth = $validated['billing_month'];

        // 同じ月を二重に請求しない
        if ($project->invoices()->where('billing_month', $billingMonth)->exists()) {
            return $fail($billingMonth . ' は既に請求済みです。');
        }

        // 請求対象月に出荷（STEP 8 納期情報）した分を集計し、今回の請求台数とする
        $monthDeliveries = $project->deliveries()->get()
            ->filter(fn ($d) => \Carbon\Carbon::parse($d->shipped_date)->format('Y-m') === $billingMonth);

        if ($monthDeliveries->isEmpty()) {
            return $fail($billingMonth . ' の出荷（STEP 8 納期情報）が登録されていません。出荷を登録してから請求してください。');
        }

        $billingCount        = (int) $monthDeliveries->sum('shipped_count');
        $billingShippingCost = (float) $monthDeliveries->sum('shipping_cost');

        if ($billingCount < 1) {
            return $fail($billingMonth . ' の出荷台数が0台のため請求できません。STEP 8 の出荷台数をご確認ください。');
        }

        // 累計請求台数が受注台数を超えないかチェック
        $alreadyBilled = (int) $project->invoices()->sum('billing_count');
        $orderedCount  = (int) $project->device_count;
        if ($orderedCount > 0 && $alreadyBilled + $billingCount > $orderedCount) {
            return $fail('請求台数の合計が受注台数（' . number_format($orderedCount) . '台）を超えます。出荷台数をご確認ください。');
        }

        // 金額計算（税込）：作業費（単価×今回台数）＋ 出荷費用 ＋ 消費税10%
        $unitPrice   = (float) ($project->final_price ?? 0);
        $subtotal    = $unitPrice * $billingCount + $billingShippingCost;
        $amountTotal = $subtotal + (int) floor($subtotal * 0.10);

        // 請求履歴を1件追加（案件内の通番＝枝番）
        $sequence = (int) $project->invoices()->max('sequence') + 1;
        $invoice = $project->invoices()->create([
            'sequence'              => $sequence,
            'billing_month'         => $billingMonth,
            'billing_date'          => $validated['billing_date'],
            'billing_count'         => $billingCount,
            'billing_shipping_cost' => $billingShippingCost,
            'amount_total'          => $amountTotal,
        ]);

        // ===== 終了ルーティン（2つの完了フラグで案件完了を判定）=====
        $billedTotal = $alreadyBilled + $billingCount;
        // 請求完了フラグ：全請求書を出力（累計請求台数＝受注台数）
        $billingDone = $orderedCount > 0 && $billedTotal >= $orderedCount;
        // 入荷完了フラグ：端末（入荷数＝受注台数）＋ 付属品（各入荷数＝登録台数）
        $terminalArrived = $orderedCount > 0 && (int) $project->arrivals()->sum('arrived_count') >= $orderedCount;
        $accessoriesArrived = $project->projectAccessories()->get()
            ->every(fn ($a) => (int) $a->arrived_count >= (int) $a->planned_count);
        $arrivalDone = $terminalArrived && $accessoriesArrived;

        // PDF 用に今回の請求内容を案件にも反映
        $update = [
            'billing_date'          => $validated['billing_date'],
            'billing_count'         => $billingCount,
            'billing_shipping_cost' => $billingShippingCost,
        ];

        $completionNote = '';
        if ($billingDone && $arrivalDone) {
            // 両フラグOK → 案件完了
            $update['status'] = '案件完了';
        } else {
            $update['status'] = '納品済み';
            // 請求は完了したが入荷が未完了の場合は注意喚起（仕様：入荷画面へ）
            if ($billingDone && !$arrivalDone) {
                $arrivedTotal = (int) $project->arrivals()->sum('arrived_count');
                $completionNote = '※ 全数の請求は完了しましたが、入荷が未完了（' . number_format($arrivedTotal) . '/' . number_format($orderedCount) . '台）のため案件完了になりません。STEP 6 で残りの入荷を登録してください。';
            }
        }
        $project->update($update);
        $project->load(['customer', 'ownPic']);

        // 帳票番号：分納案件は1回目から枝番（-01, -02 …）を付ける。一括納品は枝番なし。
        $invoiceNo = $project->documentNumber('S')
            . ($project->delivery_method === '分納' ? '-' . str_pad($sequence, 2, '0', STR_PAD_LEFT) : '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('projects.invoice_pdf', [
            'project'   => $project,
            'invoiceNo' => $invoiceNo,
        ]);

        $fileName = '請求書_' . $invoiceNo . '_' . $project->name . '.pdf';

        activity()
            ->causedBy($request->user())
            ->log('案件「' . $project->name . '」の請求書（' . $invoiceNo . '）を生成しました');

        // 入荷未完了などの注意は次回の画面表示でフラッシュ表示
        if ($completionNote !== '') {
            session()->flash('error', $completionNote);
        }

        // 1回だけレンダリングし、同じ内容を保存＆ダウンロードに使う（二重レンダリング防止）
        $content = $pdf->output();
        // 生成したPDFを添付ファイルとして自動保存（請求書は月ごとに1件ずつ残す）
        $this->storeGeneratedPdf($project, $content, $fileName, 'invoice', false);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition(
                \Symfony\Component\HttpFoundation\HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, 'invoice.pdf'
            ),
            'X-Invoice-No'        => $invoiceNo, // AJAX 用：ダウンロードファイル名に使う
        ]);
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