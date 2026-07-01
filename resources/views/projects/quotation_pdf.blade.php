<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>御見積書</title>
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

        /* 上段：左に宛先、右に見積番号・日付 */
        .top { width: 100%; margin-bottom: 10px; }
        .top .customer {
            font-size: 14px;
            border-bottom: 1px solid #000;
            padding: 0 60px 3px 4px;
            white-space: nowrap;
        }
        .top .meta { text-align: right; font-size: 11px; line-height: 1.9; }
        .top .meta .lbl { margin-right: 8px; }

        /* 中段：左ブロック / 右ブロック */
        .mid { width: 100%; }
        .mid > td { vertical-align: top; }
        .lead { margin: 18px 0 14px; font-size: 11px; }

        .amount-line { font-size: 13px; margin-bottom: 16px; white-space: nowrap; }
        .amount-line .amt { font-size: 16px; font-weight: bold; padding: 0 8px; }
        .amount-line .tax { font-size: 9px; }

        .terms { width: 100%; border-collapse: collapse; font-size: 11px; }
        .terms td { padding: 2.5px 2px; vertical-align: middle; }
        .terms .k { width: 110px; white-space: nowrap; }
        .terms .c { width: 12px; text-align: center; }

        /* 自社情報（右上） */
        .logo { height: 22px; margin-bottom: 2px; }
        .company { font-size: 10px; line-height: 1.65; position: relative; }
        .company .cname { font-size: 14px; margin-bottom: 3px; }
        .company .seal {
            position: absolute; top: 8px; right: 4px;
            width: 62px; height: 62px; opacity: 0.85;
        }

        /* 押印欄 */
        .stamps { border-collapse: collapse; margin-top: 14px; width: 170px; }
        .stamps td { border: 1px solid #000; text-align: center; font-size: 10px; }
        .stamps .head td { padding: 3px 0; }
        .stamps .box td { width: 85px; height: 58px; vertical-align: middle; }
        .stamp-img { max-width: 50px; max-height: 50px; }

        /* 件名 */
        .subject-table { width: 100%; border-collapse: collapse; margin: 14px 0 8px; }
        .subject-table td { border: 1px solid #000; padding: 6px 8px; }
        .subject-table .lbl { width: 96px; text-align: center; letter-spacing: 8px; }

        /* 明細 */
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

        /* 備考 */
        .notes-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .notes-table td { border: 1px solid #000; padding: 4px 8px; font-size: 9.5px; line-height: 1.6; vertical-align: top; }
        .notes-table .lbl { width: 96px; text-align: center; }
    </style>
</head>
<body>

    @php
        $qty = (int) ($project->device_count ?? 0);
        $unitPrice = (float) ($project->final_price ?? 0);
        $workAmount = $unitPrice * $qty;
        $shippingEnabled = (bool) $project->quote_shipping_enabled;
        $shippingAmount = $shippingEnabled ? (float) ($project->quote_shipping_fee ?? 0) : 0;
        $subtotal = $workAmount + $shippingAmount;
        $tax = (int) floor($subtotal * 0.10);
        $total = $subtotal + $tax;
        $logo = public_path('images/report/promote_logo.png');
        $seal = public_path('images/report/company_seal.png');
    @endphp

    <div class="title">御 見 積 書</div>

    {{-- 上段：宛先 ＋ 見積番号/日付 --}}
    <table class="top">
        <tr>
            <td style="width: 55%;">
                <span class="customer">{{ $project->customer->name ?? '' }}　御中</span>
            </td>
            <td class="meta" style="width: 45%;">
                <span class="lbl">見積番号</span>{{ $project->documentNumber('M') }}<br>
                {{ date('Y年m月d日') }}
            </td>
        </tr>
    </table>

    {{-- 中段：左（あいさつ・金額・条件） / 右（ロゴ・自社情報・押印） --}}
    <table class="mid">
        <tr>
            <td style="width: 56%; padding-right: 20px;">
                <div class="lead">下記の通り、御見積申し上げます。</div>

                <div class="amount-line">
                    御見積金額：<span class="amt">¥{{ number_format($subtotal) }}</span><span class="tax">（税別）</span>
                </div>

                <table class="terms">
                    <tr><td class="k">納　　　　期</td><td class="c">：</td><td>ご相談</td></tr>
                    <tr><td class="k">納　入　場　所</td><td class="c">：</td><td>貴社ご指定場所（国内に限る）</td></tr>
                    <tr><td class="k">支　払　条　件</td><td class="c">：</td><td>月末締め翌月末現金払い</td></tr>
                    <tr><td class="k">支　払　方　法</td><td class="c">：</td><td>弊社指定銀行口座振込</td></tr>
                    <tr><td class="k">見 積 有 効 期 限</td><td class="c">：</td><td>3ヶ月</td></tr>
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
            <td class="c-qty">{{ $qty > 0 ? number_format($qty) : '' }}</td>
            <td class="c-unit">¥{{ number_format($unitPrice) }}</td>
            <td class="c-amt">¥{{ number_format($workAmount) }}</td>
        </tr>
        @if($shippingEnabled)
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
        @endif
        @for($i = 0; $i < ($shippingEnabled ? 8 : 10); $i++)
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
            <td class="sum-label sum-total">御見積合計金額</td>
            <td class="c-amt sum-total">¥{{ number_format($total) }}</td>
        </tr>
    </table>

    {{-- 備考 --}}
    <table class="notes-table">
        <tr>
            <td class="lbl" rowspan="2">備考</td>
            <td>
                ・概算見積になります。<br>
                　御社手順に変更がございましたら、再度見積もりを提出いたします。<br>
                ・作業指示、納期等はお打ち合わせにて決定させていただきます。<br>
                @if($shippingEnabled)
                    ・送料は1個ずつの配送概算でございます。荷姿、配送先等により変動いたします。
                @else
                    ・送料につきましては、配送後実費でのご請求となります。
                @endif
            </td>
        </tr>
        <tr><td style="height: 16px;">&nbsp;</td></tr>
    </table>

</body>
</html>
