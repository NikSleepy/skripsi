KONTEKS PROYEK
Dashboard Siswa Sekolah Dasar. Role: Admin, Wali Kelas, Kepala Sekolah, (opsional) Orang Tua.
Fitur: login per role, master data (siswa, kelas, tahun ajaran, semester, mapel, guru),
input nilai (tugas/UTS/UAS), absensi, ranking kelas, ranking beasiswa, dashboard + grafik,
export laporan PDF/Excel.
Tech: Laravel 11, PHP 8.2, SQLite (dev), Blade + Tailwind (Breeze). Developer: 1 orang.
Data siswa adalah data anak, jadi akses dibatasi per role dan perubahan nilai dicatat di log.

[tempel ERD]

Buatkan:
1. Struktur modul dan folder Laravel (Controller, Service, Policy, FormRequest)
2. Daftar route (method, URL, controller, middleware, role yang boleh akses)
3. Matriks hak akses: role x fitur (lihat/tambah/ubah/hapus)
4. Rencana validasi input dan pencatatan log aktivitas