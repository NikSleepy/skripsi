KONTEKS PROYEK
Dashboard Siswa Sekolah Dasar. Role: Admin, Wali Kelas, Kepala Sekolah, (opsional) Orang Tua.
Fitur: login per role, master data (siswa, kelas, tahun ajaran, semester, mapel, guru),
input nilai (tugas/UTS/UAS), absensi, ranking kelas, ranking beasiswa, dashboard + grafik,
export laporan PDF/Excel.
Tech: Laravel 11, PHP 8.2, SQLite (dev), Blade + Tailwind (Breeze). Developer: 1 orang.
Data siswa adalah data anak, jadi akses dibatasi per role dan perubahan nilai dicatat di log.

[tempel user story dan aturan ranking]

Buatkan:
1. Test case utama per fitur (tabel: ID, skenario, langkah, hasil yang diharapkan)
2. Test case khusus perhitungan ranking, termasuk kasus nilai sama dan data kosong
3. Test hak akses antar role
4. Checklist UAT untuk pihak sekolah