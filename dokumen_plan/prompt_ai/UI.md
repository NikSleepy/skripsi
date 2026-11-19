KONTEKS PROYEK
Dashboard Siswa Sekolah Dasar. Role: Admin, Wali Kelas, Kepala Sekolah, (opsional) Orang Tua.
Fitur: login per role, master data (siswa, kelas, tahun ajaran, semester, mapel, guru),
input nilai (tugas/UTS/UAS), absensi, ranking kelas, ranking beasiswa, dashboard + grafik,
export laporan PDF/Excel.
Tech: Laravel 11, PHP 8.2, SQLite (dev), Blade + Tailwind (Breeze). Developer: 1 orang.
Data siswa adalah data anak, jadi akses dibatasi per role dan perubahan nilai dicatat di log.

[tempel daftar fitur dan role]

Rancang UI/UX:
1. Sitemap dan user flow per role
2. Daftar halaman beserta komponen utamanya
3. Deskripsi wireframe tiap halaman (layout, elemen, tombol, state kosong, state error)
4. Isi dashboard per role (kartu ringkasan, grafik apa saja, tabel apa saja)
5. Saran gaya visual: palet warna, tipografi, dan pola komponen yang konsisten, cocok untuk lingkungan sekolah
6. Responsive: mana yang prioritas di HP, mana di desktop