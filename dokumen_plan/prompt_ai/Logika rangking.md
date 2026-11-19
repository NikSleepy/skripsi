KONTEKS PROYEK
Dashboard Siswa Sekolah Dasar. Role: Admin, Wali Kelas, Kepala Sekolah, (opsional) Orang Tua.
Fitur: login per role, master data (siswa, kelas, tahun ajaran, semester, mapel, guru),
input nilai (tugas/UTS/UAS), absensi, ranking kelas, ranking beasiswa, dashboard + grafik,
export laporan PDF/Excel.
Tech: Laravel 11, PHP 8.2, SQLite (dev), Blade + Tailwind (Breeze). Developer: 1 orang.
Data siswa adalah data anak, jadi akses dibatasi per role dan perubahan nilai dicatat di log.

[tempel ERD dari Prompt 2]

Rancang sistem ranking:
A. Ranking kelas: rumus rata-rata nilai, aturan tie-break, dan cara menangani siswa yang kekurangan nilai
B. Ranking beasiswa: usulkan metode (SAW atau TOPSIS), kriteria (nilai, kehadiran,
   penghasilan orang tua, jumlah tanggungan, dll), jenis tiap kriteria (benefit/cost),
   bobot awal, dan cara normalisasi
C. Contoh perhitungan lengkap dengan 5 siswa fiktif, tampilkan langkahnya dalam tabel
D. Alur proses di sistem (kapan ranking dihitung, siapa yang memicu, bagaimana hasil dikunci)
E. Pseudocode atau skeleton service class Laravel untuk perhitungannya