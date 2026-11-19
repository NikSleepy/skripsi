KONTEKS PROYEK
Dashboard Siswa Sekolah Dasar. Role: Admin, Wali Kelas, Kepala Sekolah, (opsional) Orang Tua.
Fitur: login per role, master data (siswa, kelas, tahun ajaran, semester, mapel, guru),
input nilai (tugas/UTS/UAS), absensi, ranking kelas, ranking beasiswa, dashboard + grafik,
export laporan PDF/Excel.
Tech: Laravel 11, PHP 8.2, SQLite (dev), Blade + Tailwind (Breeze). Developer: 1 orang.
Data siswa adalah data anak, jadi akses dibatasi per role dan perubahan nilai dicatat di log.

## 2. Kebutuhan Sistem

### 2.1 Kebutuhan Fungsional (*Functional Requirements*)

| ID | Deskripsi Kebutuhan | Prioritas |
|---|---|---|
| FR01 | Sistem menyediakan fitur login dengan otorisasi berbasis role: Admin, Kepala Sekolah, Wali Kelas, Guru Mapel, dan Orang Tua. | Must |
| FR02 | Admin dapat mengelola master data siswa, kelas, tahun ajaran, semester, mata pelajaran, dan guru. | Must |
| FR03 | Wali Kelas dapat menginput dan mengedit nilai mata pelajaran yang menjadi tanggung jawabnya di kelas yang ditugaskan, serta melihat rekap nilai akhir. | Must |
| FR04 | Guru Mapel hanya dapat menginput nilai untuk mata pelajaran dan kelas yang ditugaskan kepadanya, misalnya Agama atau PJOK. | Must |
| FR05 | Wali Kelas dapat mencatat absensi harian siswa dengan status Sakit, Izin, atau Alpa. | Must |
| FR06 | Sistem menyimpan riwayat perubahan nilai yang mencatat pengguna pengubah, waktu perubahan, dan *new value*. | Must |
| FR07 | Sistem menghitung ranking kelas secara otomatis berdasarkan total atau rata-rata nilai. | Must |
| FR08 | Sistem menghasilkan rekomendasi ranking beasiswa berdasarkan formula kombinasi pendapatan orang tua dan nilai akademik. | Must |
| FR09 | Orang Tua dapat melihat absensi dan rapor anaknya sendiri dalam mode *read-only*. | Should |
| FR10 | Kepala Sekolah dapat melihat dashboard berisi grafik ringkasan akademik, seperti rata-rata nilai sekolah dan persentase kehadiran. | Should |
| FR11 | Sistem menyediakan ekspor rekap nilai dan absensi ke format PDF dan Excel. | Should |

### 2.2 Kebutuhan Non-Fungsional (*Non-Functional Requirements*)

| ID | Kategori | Deskripsi Kebutuhan | Prioritas |
|---|---|---|---|
| NFR01 | Keamanan | Data siswa dibatasi secara ketat berdasarkan role dan relasi pengguna. Orang Tua A tidak boleh mengakses data Siswa B, termasuk melalui manipulasi URL atau request. | Must |
| NFR02 | Kompatibilitas Database | Query dan relasi Eloquent disusun agar kompatibel dengan SQLite selama pengembangan dan dapat dimigrasikan ke MySQL atau PostgreSQL tanpa perubahan pada *business logic* sebisa mungkin. | Must |
| NFR03 | Responsivitas | UI/UX yang dibangun dengan Blade dan Tailwind CSS responsif untuk desktop, tablet, dan ponsel, terutama untuk pengguna Orang Tua. | Must |
| NFR04 | Performa | Waktu muat halaman laporan dan proses ekspor PDF/Excel ditargetkan tidak melebihi lima detik pada spesifikasi server standar. | Should |

Rancang database relasional:
1. ERD dalam format Mermaid (erDiagram), lengkap dengan PK, FK, dan kardinalitas
2. Data dictionary: tabel, kolom, tipe data, constraint, keterangan
3. Tabel khusus untuk: bobot kriteria beasiswa, hasil ranking per periode, dan log perubahan nilai
4. Pastikan data per tahun ajaran dan semester tersimpan tanpa menimpa data lama
5. Sebutkan index yang perlu dibuat
Jelaskan alasan desain pada bagian yang tidak jelas.