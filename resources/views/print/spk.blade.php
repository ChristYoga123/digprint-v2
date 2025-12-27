<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK - {{ $transaksiProduk->transaksi->kode }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 80mm 125mm;
            margin: 3mm 0 0 0;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 9px;
            line-height: 1.2;
            background: #f5f5f5;
        }

        .sheet {
            width: 80mm;
            min-height: 115mm;
            padding: 4mm 3mm 3mm 3mm;
            background: #fff;
            page-break-after: always;
            margin: 0 auto 10px auto;
            border: 1px solid #ddd;
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3mm;
            padding-bottom: 2mm;
            border-bottom: 2px solid #000;
        }

        .header-left {
            display: flex;
            flex-direction: column;
        }

        .spk-title {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .nota-number {
            font-size: 10px;
            font-weight: bold;
            margin-top: 2px;
        }

        .header-right {
            text-align: right;
        }

        .divisi-box {
            background: #000;
            color: #fff;
            padding: 4px 8px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }

        .info-table tr {
            border-bottom: 1px solid #ccc;
        }

        .info-table tr:last-child {
            border-bottom: none;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .info-table .label {
            width: 28%;
            font-weight: bold;
            color: #333;
        }

        .info-table .separator {
            width: 3%;
            text-align: center;
        }

        .info-table .value {
            width: 69%;
            word-break: break-word;
        }

        /* Detail Section */
        .detail-section {
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 1px dashed #999;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table tr {
            border-bottom: 1px solid #eee;
        }

        .detail-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .detail-table .label {
            width: 28%;
            font-weight: bold;
            color: #333;
        }

        .detail-table .separator {
            width: 3%;
            text-align: center;
        }

        .detail-table .value {
            width: 69%;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 4mm;
            padding-top: 3mm;
            border-top: 1px dashed #999;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            width: 48%;
        }

        .signature-box .title {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 2px;
        }

        .signature-box .name {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 12mm;
        }

        .signature-box .line {
            border-top: 1px solid #000;
            padding-top: 2px;
            font-size: 7px;
        }

        .signature-box .status {
            font-size: 8px;
            font-style: italic;
            color: #666;
        }

        /* Footer */
        .footer {
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 7px;
            color: #666;
        }

        /* Print Specific Styles */
        @media print {
            body {
                background: none;
            }

            .sheet {
                border: none;
                margin: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Print Button (hidden when printing) */
        .print-actions {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .print-actions button {
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .btn-print {
            background: #4CAF50;
            color: white;
        }

        .btn-print:hover {
            background: #45a049;
        }

        .btn-back {
            background: #2196F3;
            color: white;
        }

        .btn-back:hover {
            background: #1976D2;
        }

        /* Sheet counter */
        .sheet-counter {
            position: absolute;
            top: 3mm;
            right: 3mm;
            font-size: 8px;
            color: #999;
        }

        .sheet-wrapper {
            position: relative;
        }
    </style>
</head>

<body>
    <!-- Print Actions -->
    <div class="print-actions no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak SPK</button>
        <button class="btn-back" onclick="window.history.back()">← Kembali</button>
    </div>

    @foreach ($sheets as $index => $sheet)
        <div class="sheet-wrapper">
            <div class="sheet">
                <div class="sheet-counter no-print">
                    Lembar {{ $index + 1 }} dari {{ count($sheets) }}
                </div>

                <!-- Header -->
                <div class="header">
                    <div class="header-left">
                        <div class="spk-title">SPK</div>
                        <div class="nota-number">{{ $sheet['nota'] }}</div>
                    </div>
                    <div class="header-right">
                        <div class="divisi-box">{{ strtoupper($sheet['divisi']) }}</div>
                    </div>
                </div>

                <!-- Info Section -->
                <table class="info-table">
                    <tr>
                        <td class="label">Nota</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $sheet['nota'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tgl Order</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $sheet['tgl_order'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Dateline</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $sheet['dateline'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Pemesan</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $sheet['pemesan'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nama File</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $sheet['nama_file'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Produk</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $sheet['nama_produk'] }}</td>
                    </tr>
                </table>

                <!-- Detail Section -->
                <div class="detail-section">
                    <table class="detail-table">
                        <tr>
                            <td class="label">Jenis Cetak</td>
                            <td class="separator">:</td>
                            <td class="value">{{ $sheet['jenis_cetak'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Bahan</td>
                            <td class="separator">:</td>
                            <td class="value">{{ $sheet['bahan'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Satuan</td>
                            <td class="separator">:</td>
                            <td class="value">{{ $sheet['satuan'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Ukuran</td>
                            <td class="separator">:</td>
                            <td class="value">{{ $sheet['ukuran'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Jml Cetak</td>
                            <td class="separator">:</td>
                            <td class="value">{{ $sheet['jml_cetak'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Finishing</td>
                            <td class="separator">:</td>
                            <td class="value">{{ $sheet['finishing'] }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="title">Paraf Desain</div>
                        <div class="name">({{ $sheet['created_by'] }})</div>
                        <div class="line">Desainer</div>
                    </div>
                    <div class="signature-box">
                        <div class="title">Operator</div>
                        <div class="status">{{ $sheet['operator'] }}</div>
                        <div class="line" style="margin-top: 12mm;">Operator</div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    Dicetak pada: {{ $sheet['datetime_print'] }} | Proses: {{ $sheet['kategori_proses'] }} (Urutan
                    #{{ $sheet['urutan_proses'] }})
                </div>
            </div>
        </div>
    @endforeach

    <script>
        // Optional: Auto print when page loads (uncomment if needed)
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>
