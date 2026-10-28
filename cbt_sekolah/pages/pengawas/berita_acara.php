<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara STS - SMK Negeri 8 Bandar Lampung</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f3f4f6;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        /* Tombol Aksi - Tidak ikut print */
        .action-buttons {
            width: 100%;
            max-width: 900px;
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-start; /* Ubah dari space-between ke flex-start */
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            font-family: Arial, sans-serif;
            transition: all 0.3s;
        }
        
        .btn-print {
            background: #2563eb;
            color: white;
            margin-right: 10px;
        }
        
        .btn-print:hover {
            background: #1d4ed8;
        }
        
        .btn-reset {
            background: #6b7280;
            color: white;
        }
        
        .btn-reset:hover {
            background: #4b5563;
        }
        
        /* Kertas Berita Acara - Format A4 dengan margin normal */
        .kertas {
            max-width: 900px;
            width: 100%;
            background: white;
            padding: 30px 35px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid #d1d5db;
            box-sizing: border-box;
        }
        
        /* Kop Surat dengan logo */
        .kop {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px double #1e293b;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .logo-box {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .logo-fallback {
            width: 100px;
            height: 100px;
            background: #f3f4f6;
            border: 1px dashed #9ca3af;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 10px;
            text-align: center;
        }
        
        .kop-text {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }
        
        .kop-text .pemprov {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #1e293b;
        }
        
        .kop-text .smk {
            font-size: 20px;
            font-weight: 700;
            margin: 3px 0;
            text-transform: uppercase;
            color: #1e293b;
        }
        
        .kop-text .alamat {
            font-size: 12px;
            margin: 3px 0;
            color: #4b5563;
        }
        
        .kop-text .kontak {
            font-size: 11px;
            color: #6b7280;
        }
        
        /* Judul */
        .judul-berita {
            text-align: center;
            font-weight: 800;
            font-size: 22px;
            margin: 15px 0 3px;
            text-transform: uppercase;
            color: #1e293b;
            letter-spacing: 1px;
        }
        
        .judul-sts {
            text-align: center;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 25px;
            text-transform: uppercase;
            color: #2d3748;
        }
        
        /* Form isian */
        .field {
            border: none;
            border-bottom: 1px solid #333;
            padding: 2px 5px;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            text-align: center;
        }
        
        .field:focus {
            outline: none;
            border-bottom: 2px solid #2563eb;
            background: #f0f9ff;
        }
        
        .field-sm {
            width: 70px;
        }
        
        .field-md {
            width: 120px;
        }
        
        .field-lg {
            width: 200px;
        }
        
        .field-xl {
            width: 300px;
        }
        
        .field-xxl {
            width: 400px;
        }
        
        /* Baris teks */
        .baris {
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 14px;
            color: #2d3748;
        }
        
        /* Tabel */
        .tabel-form {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .tabel-form td {
            padding: 5px 5px;
            vertical-align: top;
            color: #2d3748;
        }
        
        .label-td {
            width: 180px;
            font-weight: 500;
        }
        
        .titik-td {
            width: 20px;
            text-align: center;
        }
        
        .sub-section {
            margin-left: 20px;
            margin-top: 5px;
        }
        
        .sub-section .label-td {
            width: 160px;
        }
        
        /* Catatan */
        .catatan {
            margin-top: 25px;
            border-top: 2px dashed #9ca3af;
            padding-top: 15px;
        }
        
        .catatan-label {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .catatan textarea {
            width: 100%;
            border: 1px solid #9ca3af;
            padding: 10px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            min-height: 100px;
            resize: vertical;
            border-radius: 4px;
        }
        
        .catatan textarea:focus {
            outline: none;
            border-color: #2563eb;
        }
        
        /* Tanda tangan */
        .ttd-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .tanggal-box {
            text-align: left;
        }
        
        .pengawas-box {
            text-align: center;
            width: 250px;
        }
        
        .garis-ttd {
            margin-top: 30px;
            margin-bottom: 5px;
        }
        
        /* Penutup */
        .penutup {
            margin-top: 25px;
            font-style: italic;
            font-size: 14px;
            color: #2d3748;
        }
        
        /* MEDIA PRINT */
        @media print {
            @page {
                size: A4;
                margin: 2cm;
            }
            
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .action-buttons {
                display: none !important;
            }
            
            .kertas {
                max-width: 100%;
                padding: 0;
                box-shadow: none;
                border: none;
            }
            
            .field, textarea {
                border: none !important;
                border-bottom: 1px solid black !important;
                background: transparent !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .field:focus, textarea:focus {
                outline: none;
            }
            
            .kop-text .pemprov,
            .kop-text .smk,
            .judul-berita,
            .judul-sts,
            .baris,
            .tabel-form td,
            .penutup,
            .catatan-label,
            .ttd-row {
                color: black;
            }
            
            .logo-box img {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .logo-fallback {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- TOMBOL AKSI -->
    <div class="action-buttons">
        <div>
            <button onclick="window.print()" class="btn btn-print">🖨️ Cetak Berita Acara</button>
            <button onclick="resetForm()" class="btn btn-reset">↻ Reset Form</button>
        </div>
        <!-- Tombol Kembali ke Dashboard DIHAPUS -->
    </div>

    <!-- KERTAS BERITA ACARA -->
    <div class="kertas" id="beritaAcara">
        <!-- KOP SURAT -->
        <div class="kop">
            <!-- Logo Kiri (Lampung) -->
            <div class="logo-box">
                <img src="../../assets/img/lpng.jpg" alt="Logo Provinsi Lampung" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-fallback" style="display: none;">LOGO<br>LAMPUNG</div>
            </div>
            
            <!-- Teks Kop Surat -->
            <div class="kop-text">
                <div class="pemprov">PEMERINTAH PROVINSI LAMPUNG</div>
                <div class="smk">SMK NEGERI 8 BANDAR LAMPUNG</div>
                <div class="alamat">Jl. Imam Bonjol No. 52 Kemiling Bandar Lampung, Lampung 35199</div>
                <div class="kontak">Telp: 0721 - 340 3709 | Website: smkn8l.sch.id | E-mail: smkn8l@yahoo.co.id</div>
            </div>
            
            <!-- Logo Kanan (Sekolah) -->
            <div class="logo-box">
                <img src="../../assets/img/logo_sekolah.png" alt="Logo SMK N 8 Bandar Lampung" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-fallback" style="display: none;">LOGO<br>SEKOLAH</div>
            </div>
        </div>

        <!-- JUDUL BERITA ACARA -->
        <div class="judul-berita">BERITA ACARA</div>
        <div class="judul-sts">SUMATIF TENGAH SEMESTER (STS) GENAP TAHUN PELAJARAN 2025/2026</div>

        <!-- PARAGRAF PEMBUKA -->
        <div class="baris">
            Pada hari ini 
            <input type="text" class="field field-sm" id="hari" placeholder="hari"> 
            tanggal 
            <input type="text" class="field field-sm" id="tanggal" placeholder="tgl"> 
            bulan 
            <input type="text" class="field field-md" id="bulan" placeholder="bulan"> 
            Tahun Dua Ribu Dua Puluh Enam.
        </div>

        <div class="baris">
            Telah diselenggarakan Sumatif Tengah Semester (STS) Genap Tahun Pelajaran 2025/2026. 
            Untuk mata ujian 
            <input type="text" class="field field-md" id="mapel" placeholder="mata ujian"> 
            dari pukul 
            <input type="text" class="field field-sm" id="jam_mulai" placeholder="00.00"> 
            sampai dengan pukul 
            <input type="text" class="field field-sm" id="jam_selesai" placeholder="00.00"> 
            dari
        </div>

        <!-- TABEL DATA PESERTA -->
        <table class="tabel-form">
            <tr>
                <td class="label-td">1. Ruang</td>
                <td class="titik-td">:</td>
                <td>
                    <input type="text" class="field field-sm" id="ruang" placeholder="ruang"> 
                    <span style="margin-left: 8px;">Orang</span>
                </td>
            </tr>
            <tr>
                <td class="label-td">2. Kelas</td>
                <td class="titik-td">:</td>
                <td>
                    <input type="text" class="field field-md" id="kelas" placeholder="kelas"> 
                    <span style="margin-left: 8px;">Orang</span>
                </td>
            </tr>
            <tr>
                <td class="label-td">3. Tempat penyelenggaraan</td>
                <td class="titik-td">:</td>
                <td>
                    <div><strong>SMK Negeri 8 Bandar Lampung</strong></div>
                </td>
            </tr>
        </table>

        <!-- SUB SECTION UNTUK JUMLAH PESERTA -->
        <div class="sub-section">
            <table class="tabel-form">
                <tr>
                    <td class="label-td">a. Jumlah peserta seharusnya</td>
                    <td class="titik-td">:</td>
                    <td>
                        <input type="text" class="field field-sm" id="peserta_seharusnya" placeholder="0"> 
                        <span style="margin-left: 8px;">Orang</span>
                    </td>
                </tr>
                <tr>
                    <td class="label-td">b. Hadir (Ikut Ujian)</td>
                    <td class="titik-td">:</td>
                    <td>
                        <input type="text" class="field field-sm" id="peserta_hadir" placeholder="0"> 
                        <span style="margin-left: 8px;">Orang</span>
                    </td>
                </tr>
                <tr>
                    <td class="label-td">Yakni Nomor</td>
                    <td class="titik-td">:</td>
                    <td>
                        <input type="text" class="field field-xxl" id="nomor_hadir" placeholder="contoh: 1,2,3,4,5,6,7,8,9,10">
                    </td>
                </tr>
                <tr>
                    <td class="label-td">c. Tidak Hadir</td>
                    <td class="titik-td">:</td>
                    <td>
                        <input type="text" class="field field-sm" id="tidak_hadir" placeholder="0"> 
                        <span style="margin-left: 8px;">Orang</span>
                    </td>
                </tr>
                <tr>
                    <td class="label-td">Yakni Nomor</td>
                    <td class="titik-td">:</td>
                    <td>
                        <input type="text" class="field field-xxl" id="nomor_tidak_hadir" placeholder="contoh: 11,12,13,14,15,16,17,18,19,20">
                    </td>
                </tr>
            </table>
        </div>

        <!-- CATATAN -->
        <div class="catatan">
            <div class="catatan-label">2. Catatan selama pelaksanaan Ujian:</div>
            <textarea id="catatan" placeholder="Tulis catatan di sini..."></textarea>
        </div>

        <!-- PENUTUP -->
        <div class="penutup">
            Demikian berita acara ini dibuat dengan sesungguhnya untuk dapat dipergunakan sebagaimana mestinya.
        </div>

        <!-- TANDA TANGAN -->
        <div class="ttd-row">
            <div class="tanggal-box">
                Bandar Lampung, 
                <input type="text" class="field field-sm" id="tgl_buat" placeholder="tgl"> 
                Februari 2026
            </div>
            <div class="pengawas-box">
                Pengawas
                <div class="garis-ttd">
                    <input type="text" class="field field-lg" style="text-align: center;" id="pengawas" placeholder="nama pengawas">
                </div>
                <div style="margin-top: 5px;">
                    NIP 
                    <input type="text" class="field field-md" style="text-align: center;" id="nip" placeholder="NIP">
                </div>
            </div>
        </div>
    </div>

    <script>
        function resetForm() {
            if(confirm('Kosongkan semua field isian?')) {
                document.querySelectorAll('.field, textarea').forEach(field => {
                    field.value = '';
                });
            }
        }

        // Handle error gambar
        document.querySelectorAll('img').forEach(img => {
            img.onerror = function() {
                this.style.display = 'none';
                if (this.nextElementSibling) {
                    this.nextElementSibling.style.display = 'flex';
                }
            };
        });
    </script>
</body>
</html>