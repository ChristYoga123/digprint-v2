<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota - {{ $kode }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --text-color: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --bg-light: #f9fafb;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            color: var(--text-color);
            background: #f5f5f5;
            line-height: 1.4;
        }

        /* ==================== THERMAL 80mm ==================== */
        @media print {
            body.size-thermal {
                background: none;
            }
        }

        body.size-thermal {
            font-size: 10px;
        }

        body.size-thermal .nota-container {
            width: 80mm;
            padding: 3mm;
            margin: 0 auto;
            background: #fff;
        }

        body.size-thermal .nota-header {
            text-align: center;
            padding-bottom: 2mm;
            border-bottom: 1px dashed #000;
            margin-bottom: 2mm;
        }

        body.size-thermal .shop-name {
            font-size: 14px;
            font-weight: bold;
        }

        body.size-thermal .shop-info {
            font-size: 8px;
            color: var(--text-muted);
        }

        body.size-thermal .nota-info {
            font-size: 9px;
            margin-bottom: 2mm;
            padding-bottom: 2mm;
            border-bottom: 1px dashed #000;
        }

        body.size-thermal .nota-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
        }

        body.size-thermal .items-table {
            width: 100%;
            font-size: 9px;
            margin-bottom: 2mm;
        }

        body.size-thermal .items-table th,
        body.size-thermal .items-table td {
            text-align: left;
            padding: 1px 0;
            vertical-align: top;
        }

        body.size-thermal .items-table .text-right {
            text-align: right;
        }

        body.size-thermal .item-name {
            font-weight: bold;
        }

        body.size-thermal .item-detail {
            font-size: 8px;
            color: var(--text-muted);
        }

        body.size-thermal .summary-section {
            border-top: 1px dashed #000;
            padding-top: 2mm;
            font-size: 9px;
        }

        body.size-thermal .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
        }

        body.size-thermal .summary-row.total {
            font-weight: bold;
            font-size: 12px;
            padding-top: 2mm;
            margin-top: 2mm;
            border-top: 2px solid #000;
        }

        body.size-thermal .summary-row.sisa {
            color: var(--danger-color);
            font-weight: bold;
        }

        body.size-thermal .footer {
            text-align: center;
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 1px dashed #000;
            font-size: 8px;
        }

        body.size-thermal .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        body.size-thermal .status-lunas {
            background: #d1fae5;
            color: #065f46;
        }

        body.size-thermal .status-belum {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ==================== A5 SIZE ==================== */
        body.size-a5 {
            font-size: 11px;
        }

        body.size-a5 .nota-container {
            width: 148mm;
            min-height: 210mm;
            padding: 8mm;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
        }

        body.size-a5 .nota-header {
            text-align: center;
            padding-bottom: 5mm;
            border-bottom: 2px solid var(--primary-color);
            margin-bottom: 5mm;
        }

        body.size-a5 .shop-name {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
        }

        body.size-a5 .shop-info {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 2mm;
        }

        body.size-a5 .nota-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 3mm;
        }

        body.size-a5 .nota-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm;
            margin-bottom: 5mm;
            padding: 4mm;
            background: var(--bg-light);
            border-radius: 4px;
        }

        body.size-a5 .nota-info-row {
            display: flex;
            gap: 2mm;
        }

        body.size-a5 .nota-info-row .label {
            font-weight: bold;
            min-width: 60px;
        }

        body.size-a5 .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
            font-size: 10px;
        }

        body.size-a5 .items-table th {
            background: var(--primary-color);
            color: #fff;
            padding: 3mm 2mm;
            text-align: left;
        }

        body.size-a5 .items-table td {
            padding: 3mm 2mm;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        body.size-a5 .items-table .text-right {
            text-align: right;
        }

        body.size-a5 .item-name {
            font-weight: bold;
        }

        body.size-a5 .item-detail {
            font-size: 9px;
            color: var(--text-muted);
        }

        body.size-a5 .summary-section {
            margin-left: auto;
            width: 60%;
            padding: 4mm;
            background: var(--bg-light);
            border-radius: 4px;
        }

        body.size-a5 .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 2mm 0;
            border-bottom: 1px solid var(--border-color);
        }

        body.size-a5 .summary-row:last-child {
            border-bottom: none;
        }

        body.size-a5 .summary-row.total {
            font-weight: bold;
            font-size: 14px;
            background: var(--primary-color);
            color: #fff;
            padding: 3mm;
            margin: 2mm -4mm -4mm -4mm;
            border-radius: 0 0 4px 4px;
        }

        body.size-a5 .summary-row.sisa {
            color: var(--danger-color);
            font-weight: bold;
        }

        body.size-a5 .footer {
            text-align: center;
            margin-top: 8mm;
            padding-top: 5mm;
            border-top: 1px solid var(--border-color);
            font-size: 9px;
            color: var(--text-muted);
        }

        body.size-a5 .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        body.size-a5 .status-lunas {
            background: #d1fae5;
            color: #065f46;
        }

        body.size-a5 .status-belum {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ==================== A4 SIZE ==================== */
        body.size-a4 {
            font-size: 12px;
        }

        body.size-a4 .nota-container {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
        }

        body.size-a4 .nota-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 8mm;
            border-bottom: 3px solid var(--primary-color);
            margin-bottom: 8mm;
        }

        body.size-a4 .header-left {
            flex: 1;
        }

        body.size-a4 .header-right {
            text-align: right;
        }

        body.size-a4 .shop-name {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary-color);
        }

        body.size-a4 .shop-info {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3mm;
        }

        body.size-a4 .nota-title {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--text-color);
        }

        body.size-a4 .nota-number {
            font-size: 14px;
            font-weight: bold;
            margin-top: 2mm;
        }

        body.size-a4 .nota-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5mm;
            margin-bottom: 8mm;
            padding: 6mm;
            background: var(--bg-light);
            border-radius: 6px;
            border-left: 4px solid var(--primary-color);
        }

        body.size-a4 .nota-info-row {
            display: flex;
            gap: 3mm;
        }

        body.size-a4 .nota-info-row .label {
            font-weight: bold;
            min-width: 100px;
        }

        body.size-a4 .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8mm;
        }

        body.size-a4 .items-table th {
            background: var(--primary-color);
            color: #fff;
            padding: 4mm 3mm;
            text-align: left;
            font-size: 11px;
        }

        body.size-a4 .items-table td {
            padding: 4mm 3mm;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        body.size-a4 .items-table tbody tr:nth-child(even) {
            background: var(--bg-light);
        }

        body.size-a4 .items-table .text-right {
            text-align: right;
        }

        body.size-a4 .item-name {
            font-weight: bold;
            font-size: 12px;
        }

        body.size-a4 .item-detail {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 1mm;
        }

        body.size-a4 .summary-section {
            margin-left: auto;
            width: 50%;
            padding: 6mm;
            background: var(--bg-light);
            border-radius: 6px;
        }

        body.size-a4 .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3mm 0;
            border-bottom: 1px solid var(--border-color);
        }

        body.size-a4 .summary-row:last-child {
            border-bottom: none;
        }

        body.size-a4 .summary-row.total {
            font-weight: bold;
            font-size: 18px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #fff;
            padding: 5mm;
            margin: 3mm -6mm -6mm -6mm;
            border-radius: 0 0 6px 6px;
        }

        body.size-a4 .summary-row.sisa {
            color: var(--danger-color);
            font-weight: bold;
        }

        body.size-a4 .footer {
            text-align: center;
            margin-top: 12mm;
            padding-top: 8mm;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
        }

        body.size-a4 .footer-signature {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10mm;
            margin-top: 15mm;
            text-align: center;
        }

        body.size-a4 .signature-box {
            padding-top: 20mm;
            border-top: 1px solid #000;
        }

        body.size-a4 .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: bold;
        }

        body.size-a4 .status-lunas {
            background: #d1fae5;
            color: #065f46;
        }

        body.size-a4 .status-belum {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ==================== PRINT MEDIA ==================== */
        @media print {
            body {
                background: none !important;
            }

            .nota-container {
                border: none !important;
                box-shadow: none !important;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Thermal print page */
        body.size-thermal {
            --page-width: 80mm;
        }

        @page thermal {
            size: 80mm auto;
            margin: 0;
        }

        body.size-thermal {
            page: thermal;
        }

        /* A5 print page */
        @page a5 {
            size: A5 portrait;
            margin: 10mm;
        }

        body.size-a5 {
            page: a5;
        }

        /* A4 print page */
        @page a4 {
            size: A4 portrait;
            margin: 15mm;
        }

        body.size-a4 {
            page: a4;
        }

        /* ==================== ACTIONS BAR ==================== */
        .print-actions {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            max-width: 300px;
        }

        .print-actions button,
        .print-actions a {
            padding: 10px 16px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .btn-print {
            background: #10b981;
            color: white;
        }

        .btn-print:hover {
            background: #059669;
        }

        .btn-size {
            background: #6366f1;
            color: white;
        }

        .btn-size:hover {
            background: #4f46e5;
        }

        .btn-size.active {
            background: #4338ca;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }

        .btn-back {
            background: #64748b;
            color: white;
        }

        .btn-back:hover {
            background: #475569;
        }

        .size-selector {
            display: flex;
            gap: 5px;
            width: 100%;
        }
    </style>
</head>

<body class="size-{{ $size }}">
    <!-- Print Actions -->
    <div class="print-actions no-print">
        <div class="size-selector">
            <a href="?transaksi_id={{ $transaksi->id }}&size=thermal"
                class="btn-size {{ $size === 'thermal' ? 'active' : '' }}">📱 Thermal</a>
            <a href="?transaksi_id={{ $transaksi->id }}&size=a5" class="btn-size {{ $size === 'a5' ? 'active' : '' }}">📄
                A5</a>
            <a href="?transaksi_id={{ $transaksi->id }}&size=a4"
                class="btn-size {{ $size === 'a4' ? 'active' : '' }}">📑 A4</a>
        </div>
        <button class="btn-print" onclick="window.print()">🖨️ Cetak</button>
        <button class="btn-back" onclick="window.history.back()">← Kembali</button>
    </div>

    <div class="nota-container">
        @if ($size === 'a4')
            {{-- A4 LAYOUT --}}
            <div class="nota-header">
                <div class="header-left">
                    <div class="shop-name">DIGPRINT</div>
                    <div class="shop-info">
                        Jl. Contoh Alamat No. 123<br>
                        Telp: 0812-3456-7890 | Email: info@digprint.com
                    </div>
                </div>
                <div class="header-right">
                    <div class="nota-title">INVOICE</div>
                    <div class="nota-number">{{ $kode }}</div>
                </div>
            </div>
        @else
            {{-- THERMAL & A5 LAYOUT --}}
            <div class="nota-header">
                <div class="shop-name">DIGPRINT</div>
                <div class="shop-info">Jl. Contoh Alamat No. 123 | Telp: 0812-3456-7890</div>
                @if ($size === 'a5')
                    <div class="nota-title">INVOICE</div>
                @endif
            </div>
        @endif

        <!-- Info Section -->
        <div class="nota-info">
            <div class="nota-info-row">
                <span class="label">No. Nota</span>
                <span>: {{ $kode }}</span>
            </div>
            <div class="nota-info-row">
                <span class="label">Tanggal</span>
                <span>: {{ $tanggal }}</span>
            </div>
            <div class="nota-info-row">
                <span class="label">Customer</span>
                <span>: {{ $customer }}</span>
            </div>
            <div class="nota-info-row">
                <span class="label">Kasir</span>
                <span>: {{ $kasir }}</span>
            </div>
            <div class="nota-info-row">
                <span class="label">Status</span>
                <span>:
                    <span
                        class="status-badge {{ $sisa_tagihan <= 0 ? 'status-lunas' : 'status-belum' }}">{{ $status_pembayaran }}</span>
                </span>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: {{ $size === 'thermal' ? '35%' : '30%' }}">Item</th>
                    <th style="width: 10%" class="text-right">Qty</th>
                    @if ($size !== 'thermal')
                        <th style="width: 15%">Ukuran</th>
                        <th style="width: 15%" class="text-right">Harga</th>
                    @endif
                    <th style="width: {{ $size === 'thermal' ? '25%' : '20%' }}" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item['nama'] }}</div>
                            @if ($item['judul'])
                                <div class="item-detail">{{ $item['judul'] }}</div>
                            @endif
                            @if ($size === 'thermal' && $item['ukuran'] !== '-')
                                <div class="item-detail">{{ $item['ukuran'] }}</div>
                            @endif
                        </td>
                        <td class="text-right">{{ $item['jumlah'] }}</td>
                        @if ($size !== 'thermal')
                            <td>{{ $item['ukuran'] }}</td>
                            <td class="text-right">{{ formatRupiah($item['harga_satuan']) }}</td>
                        @endif
                        <td class="text-right">{{ formatRupiah($item['subtotal']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>{{ formatRupiah($subtotal) }}</span>
            </div>
            @if ($diskon > 0)
                <div class="summary-row" style="color: var(--danger-color);">
                    <span>Diskon</span>
                    <span>- {{ formatRupiah($diskon) }}</span>
                </div>
            @endif
            <div class="summary-row total">
                <span>TOTAL</span>
                <span>{{ formatRupiah($total) }}</span>
            </div>
        </div>

        @if ($total_dibayar > 0 || $sisa_tagihan > 0)
            <!-- Payment Info Section -->
            <div class="summary-section" style="margin-top: 3mm;">
                @if ($total_dibayar > 0)
                    <div class="summary-row" style="color: var(--success-color); font-weight: bold;">
                        <span>Dibayar</span>
                        <span>{{ formatRupiah($total_dibayar) }}</span>
                    </div>
                @endif
                @if ($sisa_tagihan > 0)
                    <div class="summary-row sisa" style="font-weight: bold;">
                        <span>Sisa Tagihan</span>
                        <span>{{ formatRupiah($sisa_tagihan) }}</span>
                    </div>
                @endif
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            @if ($size === 'a4')
                <div class="footer-signature">
                    <div>
                        <div class="signature-box">Customer</div>
                    </div>
                    <div>
                        <div class="signature-box">Admin</div>
                    </div>
                    <div>
                        <div class="signature-box">Kasir</div>
                    </div>
                </div>
            @endif
            <p style="margin-top: {{ $size === 'a4' ? '10mm' : '2mm' }};">
                Terima kasih atas kepercayaan Anda!<br>
                <small>Dicetak: {{ now()->format('d/m/Y H:i') }}</small>
            </p>
        </div>
    </div>
</body>

</html>
