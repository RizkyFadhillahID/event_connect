<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Evaluasi & Pertanggungjawaban: {{ $event->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            line-height: 1.4;
            font-size: 11px;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        
        .container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Kop Surat Resmi */
        .kop-surat {
            border-bottom: 3px double #000000;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .kop-logo-placeholder {
            width: 50px;
            height: 50px;
            background: #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: #4b5563;
            margin-right: 15px;
            border: 1px solid #cbd5e1;
        }

        .kop-text {
            flex-grow: 1;
        }

        .kop-text h1 {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kop-text p {
            margin: 0;
            font-size: 10px;
            color: #555555;
        }

        .document-title {
            text-align: center;
            margin: 20px 0 25px 0;
        }

        .document-title h2 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 5px 0;
            letter-spacing: 1px;
        }

        .document-title p {
            margin: 0;
            font-size: 10px;
            color: #666666;
            font-style: italic;
        }

        /* Section layout */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #333333;
            padding-bottom: 3px;
            margin-top: 25px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            background-color: #f8fafc;
        }

        .info-grid-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 0;
            font-size: 10.5px;
            vertical-align: top;
        }

        .info-table td.label {
            color: #666666;
            width: 120px;
        }

        .info-table td.value {
            font-weight: 600;
        }

        /* Detailed Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.data-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-weight: 700;
            text-align: left;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 9.5px;
            vertical-align: middle;
        }

        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8.5px;
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-warning { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge-neutral { background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }

        .evaluation-text {
            background-color: #fafafa;
            border-left: 3px solid #64748b;
            padding: 10px 14px;
            font-style: italic;
            font-size: 11px;
            margin-bottom: 15px;
            white-space: pre-line;
            line-height: 1.5;
        }

        /* Budget overview grid */
        .budget-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 15px;
        }

        .budget-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            background-color: #f8fafc;
        }

        .budget-box .b-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .budget-box .b-val {
            font-size: 13px;
            font-weight: 700;
        }

        /* Signature block */
        .signoff-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signoff-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
            margin-top: 20px;
        }

        .sign-box {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 110px;
        }

        .sign-title {
            font-size: 10px;
            color: #555555;
            text-transform: uppercase;
        }

        .sign-line {
            border-bottom: 1px solid #000000;
            width: 80%;
            margin: 0 auto 4px auto;
        }

        .sign-name {
            font-weight: 600;
            font-size: 10.5px;
        }

        .sign-role {
            font-size: 9px;
            color: #666666;
        }

        /* Print formatting */
        @media print {
            body {
                background: #ffffff;
                color: #000000;
            }
            .container {
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
        }
        
        .no-print-bar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .no-print-bar button {
            background-color: #38bdf8;
            color: #0f172a;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 11px;
            transition: background 0.15s;
        }

        .no-print-bar button:hover {
            background-color: #0ea5e9;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Top Action bar (hidden when printed) -->
    <div class="no-print-bar no-print">
        <div style="font-weight: 600; font-size: 12px;">Pratinjau Dokumen Cetak Formal (PDF)</div>
        <div style="display:flex; gap:8px">
            <button onclick="window.print()">Cetak / Simpan Ke PDF</button>
            <button onclick="window.close()" style="background-color: #4b5563; color: #ffffff;">Tutup Halaman</button>
        </div>
    </div>

    <!-- Kop Surat Resmi -->
    <div class="kop-surat">
        <div class="kop-logo-placeholder">EC</div>
        <div class="kop-text">
            <h1>{{ $organization->name }}</h1>
            <p>Layanan Event Management &amp; Platform Kolaborasi - EventConnect Portal</p>
            <p>Alamat: {{ $organization->email }} &bull; Hubungi Support Tenant untuk Bantuan</p>
        </div>
    </div>

    <!-- Judul Dokumen -->
    <div class="document-title">
        <h2>Laporan Evaluasi &amp; Pertanggungjawaban Acara</h2>
        <p>Nomor Dokumen: EC-REP/{{ $event->id }}/{{ date('Y') }} &bull; Tanggal Diterbitkan: {{ $publishDate }}</p>
    </div>

    <!-- Informasi Event -->
    <div class="section-title">I. Profil &amp; Ringkasan Informasi Acara</div>
    <div class="info-grid">
        <div class="info-card">
            <table class="info-table">
                <tr>
                    <td class="label">Nama Acara</td>
                    <td class="value">{{ $event->name }}</td>
                </tr>
                <tr>
                    <td class="label">Kategori / Jenis</td>
                    <td class="value">{{ $event->category ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Lokasi / Tempat</td>
                    <td class="value">{{ $event->location }}</td>
                </tr>
                <tr>
                    <td class="label">Deskripsi Singkat</td>
                    <td class="value" style="font-weight: normal; font-style: italic;">{{ $event->description ?? '—' }}</td>
                </tr>
            </table>
        </div>
        <div class="info-card">
            <table class="info-table">
                <tr>
                    <td class="label">Tanggal Mulai</td>
                    <td class="value">{{ $event->start_date ? $event->start_date->format('d M Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Selesai</td>
                    <td class="value">{{ $event->end_date ? $event->end_date->format('d M Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Waktu Pelaksanaan</td>
                    <td class="value">{{ $event->start_time ?? '—' }} s.d {{ $event->end_time ?? '—' }} WIB</td>
                </tr>
                <tr>
                    <td class="label">Tamu Registrasi (RSVP)</td>
                    <td class="value">{{ $guests->count() }} orang</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Realisasi Keuangan -->
    <div class="section-title">II. Pertanggungjawaban Finansial &amp; Anggaran</div>
    <div class="budget-summary-grid">
        <div class="budget-box">
            <div class="b-label">Plafond Anggaran</div>
            <div class="b-val">Rp {{ number_format($event->budget, 0, ',', '.') }}</div>
        </div>
        <div class="budget-box">
            <div class="b-label">Rencana Alokasi</div>
            <div class="b-val" style="color:#4b5563">Rp {{ number_format($allocations->sum('allocated_amount'), 0, ',', '.') }}</div>
        </div>
        <div class="budget-box">
            <div class="b-label">Realisasi Terpakai</div>
            <div class="b-val" style="color:#dc2626">Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}</div>
        </div>
        <div class="budget-box">
            <div class="b-label">Sisa Anggaran</div>
            <div class="b-val" style="color:#059669">Rp {{ number_format($event->budget - $expenses->sum('amount'), 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Rencana Alokasi Anggaran -->
    <div style="font-weight: 700; margin-bottom: 6px; font-size: 10px; color: #555;">A. Rencana Alokasi Pos Anggaran</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Kategori Pos Anggaran</th>
                <th style="width: 25%;">Nominal Alokasi (Rp)</th>
                <th style="width: 35%;">Catatan Rencana</th>
            </tr>
        </thead>
        <tbody>
            @forelse($allocations as $index => $alloc)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $alloc->category }}</strong></td>
                    <td>Rp {{ number_format($alloc->allocated_amount, 0, ',', '.') }}</td>
                    <td>{{ $alloc->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="color: #888;">Belum ada rencana alokasi anggaran yang dicatatkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Rincian Pengeluaran Aktual -->
    <div style="font-weight: 700; margin-bottom: 6px; font-size: 10px; color: #555;">B. Rincian Histori Transaksi Pengeluaran Aktual</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 25%;">Keterangan Transaksi</th>
                <th style="width: 15%;">Kategori Pos</th>
                <th style="width: 15%;">Jumlah (Rp)</th>
                <th style="width: 15%;">Vendor &amp; Metode</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%;">Dicatat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $exp)
                <tr>
                    <td class="text-center">{{ $exp->spent_at ? date('d-m-Y', strtotime($exp->spent_at)) : '—' }}</td>
                    <td><strong>{{ $exp->title }}</strong></td>
                    <td>{{ $exp->category }}</td>
                    <td class="text-right">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                    <td>{{ $exp->vendor_name ?? '—' }}<br><span style="font-size:8.5px;color:#666">Metode: {{ strtoupper($exp->payment_method) }}</span></td>
                    <td class="text-center">
                        @if($exp->payment_status === 'paid')
                            <span class="badge badge-success">Lunas</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </td>
                    <td>{{ $exp->user?->name ? explode(' ', $exp->user->name)[0] : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #888;">Belum ada catatan transaksi pengeluaran aktual.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Evaluasi Kinerja Tim & Tugas -->
    <div class="section-title">III. Evaluasi Distribusi &amp; Kinerja Tugas Tim</div>
    <div style="margin-bottom: 10px; font-size: 10px; color: #555;">
        Berikut adalah rincian tugas tim pelaksana acara beserta status penyelesaiannya:
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Nama / Deskripsi Tugas</th>
                <th style="width: 20%;">Penanggung Jawab (PIC)</th>
                <th style="width: 15%;">Batas Waktu</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 20%;">Catatan Progres</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $t)
                <tr>
                    <td><strong>{{ $t->title }}</strong></td>
                    <td>{{ $t->assignee?->name ?? 'Belum Ditugaskan' }}</td>
                    <td class="text-center">{{ $t->due_date ? date('d-m-Y', strtotime($t->due_date)) : '—' }}</td>
                    <td class="text-center">
                        @if($t->status === 'completed')
                            <span class="badge badge-success">Selesai</span>
                        @elseif($t->status === 'in_progress')
                            <span class="badge badge-warning">Pengerjaan</span>
                        <!-- check overdue -->
                        @elseif($t->status !== 'completed' && $t->due_date && strtotime($t->due_date) < time())
                            <span class="badge badge-danger">Terlambat</span>
                        @else
                            <span class="badge badge-neutral">{{ $t->status }}</span>
                        @endif
                    </td>
                    <td>{{ $t->description ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="color: #888;">Belum ada data tugas yang ditambahkan untuk event ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Logistik & Distribusi Aset Gudang -->
    <div class="section-title">IV. Distribusi &amp; Status Pengembalian Logistik</div>
    <div style="margin-bottom: 10px; font-size: 10px; color: #555;">
        Daftar barang inventaris yang didistribusikan untuk pelaksanaan acara:
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Nama Barang &amp; Serial</th>
                <th style="width: 10%;">Jumlah</th>
                <th style="width: 20%;">Penanggung Jawab</th>
                <th style="width: 15%;">Pinjam</th>
                <th style="width: 15%;">Kembali</th>
                <th style="width: 15%;">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logistics as $logi)
                <tr>
                    <td><strong>{{ $logi->inventory?->item_name ?? 'Alat' }}</strong><br><span style="font-size:8.5px;color:#666">{{ $logi->inventory?->serial_number ?? '—' }}</span></td>
                    <td class="text-center">{{ $logi->quantity }} Unit</td>
                    <td>{{ $logi->user?->name ?? '—' }}</td>
                    <td class="text-center">{{ $logi->borrowed_at ? date('d-m-Y', strtotime($logi->borrowed_at)) : '—' }}</td>
                    <td class="text-center">{{ $logi->returned_at ? date('d-m-Y', strtotime($logi->returned_at)) : 'Belum Kembali' }}</td>
                    <td class="text-center">
                        @if($logi->returned_at)
                            @if($logi->return_status === 'complete')
                                <span class="badge badge-success">Lengkap</span>
                            @elseif($logi->return_status === 'damaged')
                                <span class="badge badge-danger">Rusak</span>
                            @else
                                <span class="badge badge-warning">Kurang</span>
                            @endif
                        @else
                            <span class="badge badge-neutral" style="color: #f59e0b">Dipinjam</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="color: #888;">Belum ada logistik yang dideploy untuk event ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Kehadiran Tamu Undangan (RSVP) -->
    <div class="section-title">V. Kehadiran Tamu Undangan (RSVP) &amp; Check-In</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Nama Lengkap Tamu</th>
                <th style="width: 25%;">Kontak / Email</th>
                <th style="width: 15%;">Kategori</th>
                <th style="width: 15%;">Status RSVP</th>
                <th style="width: 15%;">Check-In Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($guests as $guest)
                <tr>
                    <td><strong>{{ $guest->name }}</strong></td>
                    <td>{{ $guest->phone ?? '—' }}<br><span style="font-size:8.5px;color:#666">{{ $guest->email ?? '—' }}</span></td>
                    <td class="text-center">{{ $guest->category ?? 'Umum' }}</td>
                    <td class="text-center">
                        @if($guest->rsvp_status === 'attending')
                            <span class="badge badge-success">Konfirmasi</span>
                        @elseif($guest->rsvp_status === 'declined')
                            <span class="badge badge-danger">Batal</span>
                        @else
                            <span class="badge badge-neutral">Rencana</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($guest->checkin_status)
                            <span class="badge badge-success" style="font-size: 8px;">Hadir ({{ $guest->checked_in_at ? $guest->checked_in_at->format('H:i') : '' }})</span>
                        @else
                            <span class="badge badge-neutral" style="color: #991b1b">Absen</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="color: #888;">Belum ada data tamu undangan terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Catatan Evaluasi Evaluasi PM -->
    <div class="section-title">VI. Analisis &amp; Rekomendasi Project Manager</div>
    
    <div style="font-weight: 700; margin-bottom: 6px; font-size: 10px; color: #555;">A. Catatan Evaluasi Pasca-Event</div>
    <div class="evaluation-text">
        "{{ $report->evaluation_notes ?? 'Belum ada catatan evaluasi dari Project Manager.' }}"
    </div>

    <div style="font-weight: 700; margin-bottom: 6px; font-size: 10px; color: #555;">B. Saran &amp; Rekomendasi Utama Masa Depan</div>
    <div class="evaluation-text" style="border-left-color: #0ea5e9;">
        "{{ $report->recommendations ?? 'Belum ada saran rekomendasi dari Project Manager.' }}"
    </div>

    <!-- Lembar Tanda Tangan (Sign-off) -->
    <div class="signoff-section">
        <div style="text-align: right; font-size: 10px; margin-bottom: 15px; color:#555">
            Dibuat di: Jakarta, Tanggal: {{ $publishDate }}
        </div>
        <div style="text-align: center; font-weight: 700; font-size: 11px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
            Lembar Pengesahan &amp; Tanda Tangan Pertanggungjawaban
        </div>
        <div class="signoff-grid">
            <div class="sign-box">
                <div class="sign-title">Disiapkan Oleh,</div>
                <div>
                    <div class="sign-line"></div>
                    <div class="sign-name">{{ $report->user->name ?? 'Project Manager' }}</div>
                    <div class="sign-role">Project Manager (PM)</div>
                </div>
            </div>
            <div class="sign-box">
                <div class="sign-title">Mengetahui,</div>
                <div>
                    <div class="sign-line"></div>
                    <div class="sign-name">_____________________</div>
                    <div class="sign-role">Koordinator Logistik &amp; Acara</div>
                </div>
            </div>
            <div class="sign-box">
                <div class="sign-title">Disetujui Oleh,</div>
                <div>
                    <div class="sign-line"></div>
                    <div class="sign-name">_____________________</div>
                    <div class="sign-role">Direktur Utama Tenant / Klien</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Automatically trigger printing dialog on load
    window.addEventListener('DOMContentLoaded', (event) => {
        // Delay slightly for render stability
        setTimeout(() => {
            window.print();
        }, 800);
    });
</script>
</body>
</html>
