<?php

namespace App\Http\Controllers;

use App\Models\EstimateEditRequest;
use App\Models\ProjectEditRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    /**
     * 自分宛ての承認待ち・処理済み申請の一覧
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 自分宛ての承認待ち申請
        $pendingRequests = EstimateEditRequest::with(['estimate.project.customer', 'requester'])
            ->where('supervisor_id', $user->id)
            ->where('status', 'pending')
            ->oldest()
            ->get();

        // 自分宛ての承認待ち（STEP 4: 最終見積）
        $pendingProjectRequests = ProjectEditRequest::with(['project.customer', 'requester'])
            ->where('supervisor_id', $user->id)
            ->where('status', 'pending')
            ->oldest()
            ->get();

        // 自分が処理した直近の申請（参考表示）
        $processedRequests = EstimateEditRequest::with(['estimate.project.customer', 'requester'])
            ->where('supervisor_id', $user->id)
            ->where('status', '!=', 'pending')
            ->latest('updated_at')
            ->take(10)
            ->get();

        $processedProjectRequests = ProjectEditRequest::with(['project.customer', 'requester'])
            ->where('supervisor_id', $user->id)
            ->where('status', '!=', 'pending')
            ->latest('updated_at')
            ->take(10)
            ->get();

        return view('approvals.index', compact('pendingRequests', 'pendingProjectRequests', 'processedRequests', 'processedProjectRequests'));
    }

    /**
     * 申請を承認する（見積金額に自動反映 ＋ 変更履歴を記録）
     */
    public function approve(Request $request, EstimateEditRequest $editRequest)
    {
        $this->authorizeSupervisor($request, $editRequest);

        DB::transaction(function () use ($request, $editRequest) {
            $estimate = $editRequest->estimate;
            $oldPrice = $estimate->cost_price !== null ? (float) $estimate->cost_price : null;
            $newPrice = (float) $editRequest->requested_cost_price;

            // 金額を自動反映
            $estimate->update(['cost_price' => $newPrice]);

            // 変更履歴を「承認による変更」として記録
            $estimate->priceHistories()->create([
                'old_cost_price'  => $oldPrice,
                'new_cost_price'  => $newPrice,
                'changed_by'      => $editRequest->requester_id,
                'approved_by'     => $request->user()->id,
                'reason'          => $editRequest->reason,
                'edit_request_id' => $editRequest->id,
            ]);

            // 申請を承認済みに更新
            $editRequest->update(['status' => 'approved']);
        });

        activity()
            ->causedBy($request->user())
            ->log($editRequest->requester->name . ' の編集申請（' . $editRequest->estimate->partner_name . '）を承認しました');

        return back()->with('success', '申請を承認し、見積金額を更新しました。');
    }

    /**
     * 申請を却下する
     */
    public function reject(Request $request, EstimateEditRequest $editRequest)
    {
        $this->authorizeSupervisor($request, $editRequest);

        $editRequest->update(['status' => 'rejected']);

        activity()
            ->causedBy($request->user())
            ->log($editRequest->requester->name . ' の編集申請（' . $editRequest->estimate->partner_name . '）を却下しました');

        return back()->with('success', '申請を却下しました。');
    }

    /**
     * 申請を承認する（STEP 4: 最終見積・採用企業に自動反映）
     */
    public function approveProject(Request $request, ProjectEditRequest $projectEditRequest)
    {
        $this->authorizeSupervisor($request, $projectEditRequest);

        DB::transaction(function () use ($request, $projectEditRequest) {
            $project = $projectEditRequest->project;
            $oldFinalPrice = $project->final_price !== null ? (float) $project->final_price : null;
            $newFinalPrice = (float) $projectEditRequest->requested_final_price;

            // 変更履歴を「承認による変更」として記録
            $project->priceHistories()->create([
                'old_final_price'  => $oldFinalPrice,
                'new_final_price'  => $newFinalPrice,
                'old_partner_name' => $project->partner_name,
                'new_partner_name' => $projectEditRequest->requested_partner_name,
                'changed_by'       => $projectEditRequest->requester_id,
                'approved_by'      => $request->user()->id,
                'reason'           => $projectEditRequest->reason,
                'edit_request_id'  => $projectEditRequest->id,
            ]);

            // 最終見積金額と採用企業を自動反映
            $project->update([
                'final_price'  => $newFinalPrice,
                'partner_name' => $projectEditRequest->requested_partner_name,
            ]);

            // 採用企業の確定に伴い、各依頼先の受注/失注を反映（見積もり ID で特定）
            // 旧データ（ID 未保存）の場合のみ会社名で判定するフォールバック
            $selectedEstimateId = $projectEditRequest->requested_estimate_id;
            $selectedPartner = $projectEditRequest->requested_partner_name;
            foreach ($project->estimates as $estimate) {
                $isWinner = $selectedEstimateId
                    ? $estimate->id === $selectedEstimateId
                    : $estimate->partner_name === $selectedPartner;
                $estimate->update([
                    'result' => $isWinner ? '受注' : '失注',
                ]);
            }

            // 申請を承認済みに更新
            $projectEditRequest->update(['status' => 'approved']);
        });

        activity()
            ->causedBy($request->user())
            ->log($projectEditRequest->requester->name . ' の最終見積の編集申請（案件「' . $projectEditRequest->project->name . '」）を承認しました');

        return back()->with('success', '申請を承認し、最終見積を更新しました。');
    }

    /**
     * 申請を却下する（STEP 4）
     */
    public function rejectProject(Request $request, ProjectEditRequest $projectEditRequest)
    {
        $this->authorizeSupervisor($request, $projectEditRequest);

        $projectEditRequest->update(['status' => 'rejected']);

        activity()
            ->causedBy($request->user())
            ->log($projectEditRequest->requester->name . ' の最終見積の編集申請（案件「' . $projectEditRequest->project->name . '」）を却下しました');

        return back()->with('success', '申請を却下しました。');
    }

    /**
     * 申請の宛先上長本人か、承認待ち状態かをチェック
     */
    private function authorizeSupervisor(Request $request, EstimateEditRequest|ProjectEditRequest $editRequest): void
    {
        abort_if($editRequest->supervisor_id !== $request->user()->id, 403, 'この申請を処理する権限がありません。');
        abort_if($editRequest->status !== 'pending', 400, 'この申請は既に処理済みです。');
    }
}
