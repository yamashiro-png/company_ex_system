<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>御請求書</title>
    <style>
        @font-face {
            font-family: 'ipaexg';
            font-style: normal;
            font-weight: normal;
            src: url('{{ public_path('fonts/ipaexg.ttf') }}') format('truetype');
        }
        * { font-family: 'ipaexg', sans-serif !important; box-sizing: border-box; }
        @page { margin: 12mm 12mm; }
        body { font-size: 10px; color: #000; margin: 0; }

        .title {
            text-align: center;
            font-size: 26px;
            letter-spacing: 14px;
            margin: 0 0 18px;
            padding-left: 14px;
        }

        table { border-collapse: collapse; }
        td { vertical-align: top; }

        .top { width: 100%; margin-bottom: 10px; }
        .top .customer {
            font-size: 14px;
            border-bottom: 1px solid #000;
            padding: 0 60px 3px 4px;
            white-space: nowrap;
        }
        .top .meta { text-align: right; font-size: 11px; line-height: 1.9; }
        .top .meta .lbl { margin-right: 8px; }

        .mid { width: 100%; }
        .mid > td { vertical-align: top; }
        .lead { margin: 18px 0 14px; font-size: 11px; }

        .amount-line { font-size: 13px; margin-bottom: 16px; white-space: nowrap; }
        .amount-line .amt { font-size: 16px; font-weight: bold; padding: 0 8px; }
        .amount-line .tax { font-size: 9px; }

        .terms { width: 100%; border-collapse: collapse; font-size: 11px; }
        .terms td { padding: 2.5px 2px; vertical-align: middle; }
        .terms .k { width: 120px; white-space: nowrap; }
        .terms .c { width: 12px; text-align: center; }

        .logo { height: 22px; margin-bottom: 2px; }
        .company { font-size: 10px; line-height: 1.65; position: relative; }
        .company .cname { font-size: 14px; margin-bottom: 3px; }
        .company .regno { margin-top: 3px; font-size: 9px; }
        .company .seal {
            position: absolute; top: 8px; right: 4px;
            width: 62px; height: 62px; opacity: 0.85;
        }

        .stamps { border-collapse: collapse; margin-top: 14px; width: 170px; }
        .stamps td { border: 1px solid #000; text-align: center; font-size: 10px; }
        .stamps .head td { padding: 3px 0; }
        .stamps .box td { width: 85px; height: 58px; vertical-align: middle; }
        .stamp-img { max-width: 50px; max-height: 50px; }

        .subject-table { width: 100%; border-collapse: collapse; margin: 14px 0 8px; }
        .subject-table td { border: 1px solid #000; padding: 6px 8px; }
        .subject-table .lbl { width: 96px; text-align: center; letter-spacing: 8px; }

        .detail { width: 100%; border-collapse: collapse; }
        .detail th, .detail td { border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 20px; }
        .detail th { background: #f8d7b8; text-align: center; font-weight: normal; }
        .detail .c-no { width: 7%; text-align: center; }
        .detail .c-item { width: 53%; }
        .detail .c-qty { width: 10%; text-align: center; }
        .detail .c-unit { width: 15%; text-align: right; }
        .detail .c-amt { width: 15%; text-align: right; }
        .detail .sum-label { text-align: center; }
        .detail .sum-total { background: #f8d7b8; }
        .detail .nb { border: none; }

        .bank { margin-top: 14px; font-size: 9.5px; line-height: 1.8; }
    </style>
</head>
<body>

    @php
        $unitPrice = (float) ($project->final_price ?? 0);
        $qty = (int) ($project->billing_count ?? 0);
        $workAmount = $unitPrice * $qty;
        $shippingAmount = (float) ($project->billing_shipping_cost ?? 0);
        $subtotal = $workAmount + $shippingAmount;
        $tax = (int) floor($subtotal * 0.10);
        $total = $subtotal + $tax;
        $billDate = $project->billing_date ? \Carbon\Carbon::parse($project->billing_date) : now();
        $logo = public_path('images/report/promote_logo.png');
        $seal = public_path('images/report/company_seal.png');
    @endphp

    <div class="title">御 請 求 書</div>

    {{-- 上段：宛先 ＋ 請求番号/日付 --}}
    <table class="top">
        <tr>
            <td style="width: 55%;">
                <span class="customer">{{ $project->customer->name ?? '' }}　御中</span>
            </td>
            <td class="meta" style="width: 45%;">
                <span class="lbl">請求番号</span>{{ $invoiceNo ?? $project->documentNumber('S') }}<br>
                {{ $billDate->format('Y年m月d日') }}
            </td>
        </tr>
    </table>

    {{-- 中段：左 / 右 --}}
    <table class="mid">
        <tr>
            <td style="width: 56%; padding-right: 20px;">
                <div class="lead">下記の通り、御請求申し上げます。</div>

                <div class="amount-line">
                    御請求金額：<span class="amt">¥{{ number_format($total) }}</span><span class="tax">（税込）</span>
                </div>

                <table class="terms">
                    <tr><td class="k">お 支 払 い 期 限</td><td class="c">：</td><td>指定日付の翌々月末</td></tr>
                    <tr><td class="k">お 支 払 い 方 法</td><td class="c">：</td><td>弊社指定銀行口座振込</td></tr>
                </table>
            </td>

            <td style="width: 44%;">
                @if(file_exists($logo))
                    <img class="logo" src="{{ $logo }}" alt="Promote">
                @endif
                <div class="company">
                    <div class="cname">株式会社プロモート</div>
                    〒150-0002<br>
                    東京都渋谷区渋谷一丁目17番8号<br>
                    松岡渋谷ビル２階<br>
                    TEL03-5774-5835　FAX03-5774-5834
                    <div class="regno">登録番号：T2020001055931</div>
                    @if(file_exists($seal))
                        <img class="seal" src="{{ $seal }}" alt="印">
                    @endif
                </div>

                <table class="stamps">
                    <tr class="head"><td>責任者</td><td>担当者</td></tr>
                    <tr class="box">
                        <td>上長印</td>
                        <td>
                            @if($project->ownPic && $project->ownPic->stamp_path && file_exists(public_path('storage/' . $project->ownPic->stamp_path)))
                                <img class="stamp-img" src="{{ public_path('storage/' . $project->ownPic->stamp_path) }}" alt="作業者印">
                            @else
                                作業者印
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- 件名（全幅） --}}
    <table class="subject-table">
        <tr>
            <td class="lbl">件　名</td>
            <td>{{ $project->name }}</td>
        </tr>
    </table>

    {{-- 明細 --}}
    <table class="detail">
        <tr>
            <th class="c-no">No.</th>
            <th class="c-item">内容/項目</th>
            <th class="c-qty">数量</th>
            <th class="c-unit">単価</th>
            <th class="c-amt">金額</th>
        </tr>
        <tr>
            <td class="c-no">1</td>
            <td class="c-item">{{ $project->name }} キッティング作業 {{ $project->device_model }}</td>
            <td class="c-qty">{{ number_format($qty) }}</td>
            <td class="c-unit">¥{{ number_format($unitPrice) }}</td>
            <td class="c-amt">¥{{ number_format($workAmount) }}</td>
        </tr>
        <tr>
            <td class="c-no">&nbsp;</td>
            <td class="c-item"></td><td class="c-qty"></td><td class="c-unit"></td><td class="c-amt"></td>
        </tr>
        <tr>
            <td class="c-no">2</td>
            <td class="c-item">配送費</td>
            <td class="c-qty">1 式</td>
            <td class="c-unit">¥{{ number_format($shippingAmount) }}</td>
            <td class="c-amt">¥{{ number_format($shippingAmount) }}</td>
        </tr>
        @for($i = 0; $i < 9; $i++)
        <tr>
            <td class="c-no">&nbsp;</td>
            <td class="c-item"></td><td class="c-qty"></td><td class="c-unit"></td><td class="c-amt"></td>
        </tr>
        @endfor
        <tr>
            <td class="nb" colspan="3"></td>
            <td class="sum-label">小計</td>
            <td class="c-amt">¥{{ number_format($subtotal) }}</td>
        </tr>
        <tr>
            <td class="nb" colspan="3"></td>
            <td class="sum-label">消費税等（税率10％）</td>
            <td class="c-amt">¥{{ number_format($tax) }}</td>
        </tr>
        <tr>
            <td class="nb" colspan="3"></td>
            <td class="sum-label sum-total">御請求合計金額</td>
            <td class="c-amt sum-total">¥{{ number_format($total) }}</td>
        </tr>
    </table>

    <div class="bank">
        取引銀行:<br>
        三井住友銀行　渋谷駅前支店　　普通　4800280　株式会社プロモート<br>
        ※恐れ入りますが、振込手数料は貴社にてご負担頂きますよう、宜しくお願い申し上げます。
    </div>

</body>
</html>
