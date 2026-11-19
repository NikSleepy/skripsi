Kamu adalah UI/UX designer senior. Rancang UI/UX lengkap untuk aplikasi web
"DASHBOARD SISWA SEKOLAH DASAR" berbasis Laravel 11 + Blade + Tailwind CSS.
Developer solo, jadi desain harus sederhana, konsisten, dan mudah diimplementasikan.

KONTEKS PROYEK:
- Role pengguna: Admin, Wali Kelas, Kepala Sekolah, (opsional) Orang Tua.
- Data yang dikelola adalah data anak-anak → prioritas: keamanan, kejelasan,
  tidak ada tombol yang membingungkan per role.
- Perubahan nilai selalu dicatat di log → UI harus menunjukkan status nilai
  (draft / diserahkan / terkunci) secara visual.
- Statistik: nilai menggunakan skala 0–100, ada KKM per mapel, absensi
  (Sakit/Izin/Alpa), nilai Tugas/UTS/UAS.

TUGAS RANCANGAN — jawab ketiga bagian ini:

1. SITEMAP & USER FLOW PER ROLE
   - Sitemap penuh aplikasi (hierarki menu per role, menu yang tidak relevan
     untuk sebuah role TIDAK tampil).
   - User flow singkat untuk alur paling penting:
     a) Admin membuat data siswa baru (termasuk akun orang tua otomatis).
     b) Guru Mapel input nilai → submit → Wali Kelas lihat rekap.
     c) Kepala Sekolah buka dashboard → lihat rekomendasi beasiswa → export.
     d) Orang Tua login → lihat nilai & absensi anak (read-only).

2. DAFTAR HALAMAN & KOMPONEN UTAMANYA
   Buat tabel: Nama Halaman | Role | Komponen Utama | Aksi Primer.
   Minimal mencakup: Login, Dashboard per role, Daftar Siswa, Form Siswa,
   Detail Siswa, Manajemen Kelas/Mapel/Guru/Tahun Ajaran, Input Nilai,
   Rekap Nilai Kelas, Absensi, Ranking Kelas, Ranking Beasiswa,
   Audit Log, Export Laporan, Profil & Ganti Password.

3. DESKRIPSI WIREFRAME TIAP HALAMAN
   Untuk tiap halaman jelaskan: layout (sidebar/topbar), elemen per seksi,
   tombol & aksi, state kosong (mis. "belum ada nilai"), state error
   (validasi form, 403), dan state sukses (toast/flash message).
   Khusus halaman Input Nilai: deskripsikan tabel gaya spreadsheet untuk
   input Tugas/UTS/UAS sekaligus per kelas, dengan highlight otomatis
   sel nilai di bawah KKM.

4. ISI DASHBOARD PER ROLE
   - Admin: kartu ringkasan, grafik, tabel apa saja.
   - Wali Kelas: fokus kelasnya sendiri (kehadiran hari ini, siswa di bawah
     KKM, nilai belum diserahkan).
   - Kepala Sekolah: rata-rata nilai sekolah per kelas, persentase kehadiran,
     tabel rekomendasi beasiswa, grafik distribusi nilai.
   - Orang Tua: nilai anak per mapel, kehadiran bulan ini, grafik tren nilai.
   Sebutkan juga jenis grafik yang tepat (bar, line, donut) untuk tiap data.

5. GAYA VISUAL
   - Palet warna lengkap (hex): 1 warna primer, 1 warna aksen, warna status
     (sukses/warning/error/info). Nuansa ramah anak namapun tetap profesional
     untuk guru & kepala sekolah. Hindari warna mencolak berlebihan.
   - Tipografi: 1 font sans-serif modern (mis. Inter), skala ukuran heading,
     body, label tabel.
   - Pola komponen konsisten: kartu statistik, tabel, badge status, tombol,
     form input, toast notifikasi — sertakan aturan pemakaian masing-masing
     (kapan pakai warna/outline/ghost).

6. RESPONSIVE PRIORITY
   - Tentukan: fitur apa yang wajib lancar di HP (prioritas Orang Tua:
     lihat nilai & absensi), fitur apa yang boleh desktop-first (input nilai
     spreadsheet, export, dashboard analitik Kepala Sekolah).
   - Jelaskan adaptasi layout: sidebar jadi bottom nav/drawer di HP,
   tabel jadi kartu stack di layar kecil, dsb.

ATURAN OUTPUT:
- Bahasa Indonesia.
- Gunakan tabel dan bullet agar mudah dipindah ke dokumentasi proyek.
- Jangan berikan kode; fokus pada keputusan desain dan alasannya singkat.
- Tandai asumsi yang perlu dikonfirmasi sekolah dengan [KONFIRMASI].