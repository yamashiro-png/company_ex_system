<?php

namespace App\Http\Controllers;

use App\Models\EstimateEditRequest;
use App\Models\ProjectEditRequest;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * 結果通知（見積金額の申請）を1件消す
     */
    public function dismissEstimate(Request $request, EstimateEditRequest $editRequest)
    {
        $this->authorizeRequester($request, $editRequest->requester_id, $editRequest->status);

        $editRequest->update(['requester_dismissed_at' => now()]);

        return back();
    }

    /**
     * 結果通知（最終見積の申請）を1件消す
     */
    public function dismissProject(Request $request, ProjectEditRequest $projectEditRequest)
    {
        $this->authorizeRequester($request, $projectEditRequest->requester_id, $projectEditRequest->status);

        $projectEditRequest->update(['requester_dismissed_at' => now()]);

        return back();
    }

    /**
     * 自分の結果通知をすべて消す
     */
    public function dismissAll(Request $request)
    {
        $userId = $request->user()->id;

        EstimateEditRequest::where('requester_id', $userId)
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNull('requester_dismissed_at')
            ->update(['requester_dismissed_at' => now()]);

        ProjectEditRequest::where('requester_id', $userId)
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNull('requester_dismissed_at')
            ->update(['requester_dismissed_at' => now()]);

        return back()->with('success', '通知をすべてクリアしました。');
    }

    /**
     * 自分が申請者で、処理済み（承認・却下）の通知だけ消せる
     */
    private function authorizeRequester(Request $request, int $requesterId, string $status): void
    {
        abort_if($requesterId !== $request->user()->id, 403, 'この通知を操作する権限がありません。');
        abort_if($status === 'pending', 400, '承認待ちの申請の通知は消せません。');
    }
}
