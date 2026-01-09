<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Stok Opname {{ $stokOpname ? '- ' . $stokOpname->kode : '' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header h2 {
            font-size: 16px;
            font-weight: 600;
            color: #555;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .info-item {
            display: flex;
            gap: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            font-weight: 500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background: #2d3748;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        tbody tr:hover {
            background: #e9ecef;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .col-no {
            width: 30px;
        }

        .col-kode {
            width: 80px;
        }

        .col-nama {
            width: 200px;
        }

        .col-satuan {
            width: 60px;
        }

        .col-stok {
            width: 80px;
        }

        .col-fisik {
            width: 100px;
            background: #fffef0;
        }

        .col-selisih {
            width: 80px;
        }

        .col-catatan {
            width: auto;
        }

        .input-cell {
            background: #fffef0;
            min-height: 20px;
        }

        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 180px;
            text-align: center;
        }

        .signature-box .title {
            font-weight: 600;
            margin-bottom: 60px;
            font-size: 11px;
        }

        .signature-box .line {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 10px;
            color: #666;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            display: flex;
            justify-content: space-between;
        }

        .notes {
            margin-top: 20px;
            padding: 10px;
            background: #f0f4f8;
            border-radius: 5px;
            font-size: 10px;
        }

        .notes h4 {
            font-size: 11px;
            margin-bottom: 5px;
            color: #2d3748;
        }

        .notes ul {
            padding-left: 15px;
            color: #555;
        }

        .notes li {
            margin-bottom: 3px;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .container {
                padding: 5mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #2d3748;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .print-button:hover {
            background: #1a202c;
        }
    </style>
</head>

<body>
    <button class="print-button no-print" onclick="window.print()">
        🖨️ Print / PDF
    </button>

    <div class="container">
        <div class="header">
            <h1>📋 Form Stok Opname</h1>
            @if ($stokOpname)
                <h2>{{ $stokOpname->kode }} {{ $stokOpname->nama ? '- ' . $stokOpname->nama : '' }}</h2>
            @endif
        </div>

        <div class="info-section">
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-value">{{ $printDate }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Item:</span>
                <span class="info-value">{{ count($items) }} item</span>
            </div>
            <div class="info-item">
                <span class="info-label">Petugas:</span>
                <span class="info-value">____________________</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-no text-center">No</th>
                    <th class="col-kode">Kode</th>
                    <th class="col-nama">Nama Bahan</th>
                    <th class="col-satuan text-center">Satuan</th>
                    <th class="col-stok text-right">Stok Sistem</th>
                    <th class="col-fisik text-center">Stok Fisik</th>
                    <th class="col-selisih text-center">Selisih</th>
                    <th class="col-catatan">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['kode'] }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td class="text-center">{{ $item['satuan'] }}</td>
                        <td class="text-right">{{ number_format($item['stok_sistem'], 2) }}</td>
                        <td class="input-cell"></td>
                        <td class="input-cell"></td>
                        <td class="input-cell"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="notes">
            <h4>📝 Petunjuk Pengisian:</h4>
            <ul>
                <li>Hitung fisik barang dan catat di kolom <strong>"Stok Fisik"</strong></li>
                <li>Hitung selisih: <strong>Stok Fisik - Stok Sistem = Selisih</strong></li>
                <li>Berikan catatan jika ada kondisi khusus (rusak, kadaluarsa, dll)</li>
                <li>Setelah selesai, serahkan form ini ke admin untuk diinput ke sistem</li>
            </ul>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="title">Petugas Opname</div>
                <div class="line">Nama & Tanda Tangan</div>
            </div>
            <div class="signature-box">
                <div class="title">Kepala Gudang</div>
                <div class="line">Nama & Tanda Tangan</div>
            </div>
            <div class="signature-box">
                <div class="title">Admin</div>
                <div class="line">Nama & Tanda Tangan</div>
            </div>
        </div>

        <div class="footer">
            <span>Dicetak pada: {{ $printDate }}</span>
            <span>Halaman 1 dari 1</span>
        </div>
    </div>
</body>

</html>
