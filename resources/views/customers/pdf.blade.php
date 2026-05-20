<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>請負・案件データ一覧</title>
    <style>
        @font-face {
            font-family: 'ipaexg';
            font-style: normal;
            font-weight: normal;
            src: url('{{ public_path('fonts/ipaexg.ttf') }}') format('truetype');
        }
        /* PDF用のシンプルなスタイル */
        body, h1, table, th, td, div, span {
            font-family: 'ipaexg', sans-serif !important;
        }
        
        body {
            font-size: 12px;
            color: #333;
        }
        h1 {
            text-align: center;
            font-size: 18px;
            font-weight: normal;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: normal !important; 
        }
        .text-right {
            text-align: right;
        }
        .date {
            text-align: right;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="date">出力日: {{ date('Y年m月d日 H:i') }}</div>
    <h1>登録済み 請負・案件データ一覧</h1>

    <table>
        <thead>
            <tr>
                <th>顧客名</th>
                <th>案件・請負業務名</th>
                <th>ステータス</th>
                <th>契約金額</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $project)
            <tr>
                <td>{{ $project->customer->name }}</td>
                <td>{{ $project->name }}</td>
                <td>{{ $project->status }}</td>
                <td class="text-right">{{ $project->price ? '¥'.number_format($project->price) : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>