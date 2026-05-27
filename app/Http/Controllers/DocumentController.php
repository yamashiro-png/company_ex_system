<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // ① ファイル管理画面を表示する処理
    public function index()
    {
        // 自分がアップロードしたファイルのみ取得（他ユーザーのファイルは非表示）
        $documents = \App\Models\Document::where('user_id', auth()->id())->latest()->get();

        // 画面（View）に、取ってきたデータ（$documents）を渡して表示させる
        return view('file_manager', compact('documents'));
    }

    // ② ファイルをアップロード（保存）する処理
    public function store(Request $request)
    {
        // ✅ セキュリティ修正2：ファイル検証を強化（拡張子、MIME type、サイズ制限）
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:2048',
        ], [
            'file.required' => 'アップロードするファイルを選択してください。',
            'file.file'     => '正常なファイル形式でアップロードしてください。',
            'file.mimes'    => 'PDF、Word、Excel、画像ファイルのみアップロード可能です。',
            'file.max'      => 'ファイルサイズは2MB以下にしてください。',
        ]);

        // （↓↓これより下の保存処理やリダイレクトは、そのまま変更なしでOKです↓↓）
        $path = $request->file('file')->store('documents');

        $document = new \App\Models\Document();
        $document->user_id = auth()->id();
        $document->original_name = $request->file('file')->getClientOriginalName();
        $document->save_path = $path;
        $document->file_size = $request->file('file')->getSize();
        $document->save(); 

        // ③ 「成功したよ！」というメッセージ（success）を持たせて元の画面に戻る！
        return redirect()->route('file_manager')->with('success', 'ファイルのアップロードが完了しました！');
    }
    // ③ ファイルをダウンロードする処理
    public function download($id)
    {
        $document = \App\Models\Document::findOrFail($id);
        
        // ✅ セキュリティ修正3：ファイルアクセス権限チェック（所有者本人のみ）
        if ($document->user_id !== auth()->id()) {
            abort(403, '他のユーザーのファイルにはアクセスできません。');
        }
        
        // 保存されているパスと、元のファイル名を使ってダウンロードさせる
        return Storage::download($document->save_path, $document->original_name);
    }

    // ④ ファイルを削除する処理
    public function destroy($id)
    {
        $document = \App\Models\Document::findOrFail($id);
        
        // ✅ セキュリティ修正4：ファイル削除権限チェック（所有者本人のみ）
        if ($document->user_id !== auth()->id()) {
            abort(403, '他のユーザーのファイルは削除できません。');
        }

        // 1. パソコンの中にある「ファイルの実体」を削除
        Storage::delete($document->save_path);
        
        // 2. データベースにある「記録」を削除
        $document->delete();

        return redirect()->route('file_manager')->with('success', 'ファイルを削除しました。');
    }
}