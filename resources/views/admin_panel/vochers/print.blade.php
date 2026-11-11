<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Voucher - {{ $voucher->rvid }}</title>
    <link rel="stylesheet" href="{{ asset('assets/vendors/font-awesome/css/all.min.css') }}">
    <style>
        /* Base Print Styles */
        @media print {
            body {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 20px 0;
            color: #000;
        }

        .receipt-container {
            width: 80mm; /* Standard 80mm thermal paper width */
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Typography */
        h1, h2, h3, p {
            margin: 0;
            padding: 0;
        }

        .company-name {
            font-size: 18px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        
        .company-info {
            font-size: 11px;
            text-align: center;
            color: #333;
            line-height: 1.4;
        }

        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 5px 0;
            border: 1px solid #000;
            padding: 2px;
            border-radius: 3px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        /* Header Details */
        .meta-info {
            font-size: 11px;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
        }
        
        .meta-info-block {
            font-size: 11px;
            margin-bottom: 3px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin: 8px 0;
        }

        .items-table th {
            border-bottom: 1px dashed #000;
            border-top: 1px dashed #000;
            padding: 4px 0;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            padding: 4px 0;
            vertical-align: top;
            border-bottom: 1px dotted #ccc;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }

        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }

        .amount-words {
            font-size: 11px;
            font-style: italic;
            margin: 6px 0;
        }

        /* Summary Box */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin: 8px 0;
        }
        .summary-table td {
            padding: 3px 0;
        }
        .summary-table tr.total-row td {
            font-weight: bold;
            border-top: 1px dashed #000;
            padding-top: 4px;
        }
        .summary-table tr.balance-row td {
            font-size: 12px;
            font-weight: bold;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 4px 0;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 15px;
            line-height: 1.4;
        }

        /* Controls */
        .print-controls {
            width: 80mm;
            margin: 0 auto 15px auto;
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary { background: #000; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
    </style>
</head>

<body>

    <div class="print-controls no-print">
        <a href="javascript:window.print()" class="btn btn-primary">🖨️ Print Voucher</a>
        <a href="{{ route('all_recepit_vochers') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="company-name">{{ \App\Models\Setting::get('company_name', 'prowave technogies') }}</div>
        <div class="company-info">
            <div>{{ \App\Models\Setting::get('company_address', 'Hyderabad') }}</div>
            <div>Ph: {{ \App\Models\Setting::get('company_phone', '0327-9226901') }}</div>
        </div>

        <div class="receipt-title">RECEIPT VOUCHER</div>
        <div class="divider"></div>

        <!-- Meta Info -->
        <div class="meta-info">
            <span><strong>Voucher No:</strong> {{ $voucher->rvid }}</span>
            <span>{{ \Carbon\Carbon::parse($voucher->receipt_date)->format('d/m/Y') }}</span>
        </div>
        
        <!-- Party Info -->
        @if ($party)
            @if(in_array($voucher->type, ['customer', 'walkin']))
                <div class="meta-info-block">
                    <strong>Received From:</strong> {{ $party->customer_name ?? $party->name ?? '-' }}
                </div>
                @if(!empty($party->mobile))
                <div class="meta-info-block">
                    <strong>Phone:</strong> {{ $party->mobile }}
                </div>
                @endif
            @elseif($voucher->type === 'vendor')
                <div class="meta-info-block">
                    <strong>Received From:</strong> {{ $party->name ?? '-' }}
                </div>
                @if(!empty($party->phone))
                <div class="meta-info-block">
                    <strong>Phone:</strong> {{ $party->phone }}
                </div>
                @endif
            @else
                <div class="meta-info-block">
                    <strong>Account:</strong> {{ $party->name ?? '-' }}
                </div>
            @endif
        @else
            <div class="meta-info-block"><strong>Party:</strong> -</div>
        @endif

        <div class="divider"></div>

        <!-- Accounts Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 10%; text-align: center;">#</th>
                    <th style="width: 60%;">Account</th>
                    <th style="width: 30%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $key => $row)
                    <tr>
                        <td style="text-align: center;">{{ $key + 1 }}</td>
                        <td>
                            <strong>{{ $row['account_name'] ?? '-' }}</strong>
                        </td>
                        <td class="text-end" style="font-weight: bold;">
                            {{ number_format($row['amount'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Amount In Words -->
        <div class="amount-words">
            In Words: <strong id="amountInWords">{{ $voucher->total_amount }}</strong>
        </div>

        @if(isset($invoiceAllocations) && $invoiceAllocations->count() > 0)
        <div style="margin-top: 10px; margin-bottom: 4px; font-weight: bold; font-size: 11px; text-transform: uppercase;">
            Settled Sales Invoices:
        </div>
        <table class="items-table" style="font-size: 10px; margin-bottom: 8px;">
            <thead>
                <tr>
                    <th style="width: 25%;">Inv #</th>
                    <th style="width: 20%; text-align: right;">Total</th>
                    <th style="width: 25%; text-align: right;">Settled</th>
                    <th style="width: 30%; text-align: right;">Remaining</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoiceAllocations as $alloc)
                <tr>
                    <td><strong>{{ $alloc->invoice_no }}</strong></td>
                    <td class="text-end">{{ number_format($alloc->invoice_amount, 2) }}</td>
                    <td class="text-end" style="font-weight: bold; color: #16a34a;">{{ number_format($alloc->allocated_amount, 2) }}</td>
                    <td class="text-end">{{ number_format($alloc->remaining_due, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="divider"></div>

        <!-- Summary -->
        @php
            $balanceAfter = $previousBalance - $voucher->total_amount;
        @endphp
        <table class="summary-table">
            <tr>
                <td>Previous Balance</td>
                <td class="text-end">{{ number_format(abs($previousBalance), 2) }} {{ $previousBalance >= 0 ? 'Dr' : 'Cr' }}</td>
            </tr>
            <tr class="total-row">
                <td>Amount Received (−)</td>
                <td class="text-end">{{ number_format($voucher->total_amount, 2) }} Cr</td>
            </tr>
            <tr class="balance-row">
                <td>Balance Remaining</td>
                <td class="text-end">{{ number_format(abs($balanceAfter), 2) }} {{ $balanceAfter >= 0 ? 'Dr' : 'Cr' }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <!-- Footer -->
        <div class="footer">
            <div>Printed: {{ now()->format('d/m/Y H:i') }}</div>
            <div style="font-weight: bold; margin-top: 5px;">Thank You ✓</div>
        </div>
    </div>

    <script>
        function numberToWords(num) {
            const a = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten',
                'Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
            const b = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
            if ((num = num.toString()).length > 9) return 'Overflow';
            let n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
            if (!n) return '';
            let str = '';
            str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + ' Crore ' : '';
            str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + ' Lakh ' : '';
            str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + ' Thousand ' : '';
            str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + ' Hundred ' : '';
            str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) + ' ' : '';
            return str.trim() + ' Only';
        }
        document.addEventListener("DOMContentLoaded", function () {
            let el = document.getElementById("amountInWords");
            if (el) {
                let amount = parseInt(el.innerText);
                el.innerText = numberToWords(amount) || el.innerText;
            }
        });

        window.addEventListener("load", function () {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>

</body>
</html>
