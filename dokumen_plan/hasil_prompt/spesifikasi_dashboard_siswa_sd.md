# Spesifikasi Proyek Dashboard Siswa Sekolah Dasar

> Dokumen spesifikasi proyek yang disusun untuk mendukung pengerjaan oleh **solo developer**.

## Daftar Isi

1. [Scope (Ruang Lingkup)](#1-scope-ruang-lingkup)
2. [Kebutuhan Sistem](#2-kebutuhan-sistem)
3. [User Story dan Acceptance Criteria](#3-user-story-dan-acceptance-criteria)
4. [Asumsi Proyek](#4-asumsi-proyek)

---

## 1. Scope (Ruang Lingkup)

### 1.1 In-Scope

Fitur yang termasuk dalam ruang lingkup pengerjaan proyek:

- **Autentikasi dan RBAC (Role-Based Access Control)**
  - Login dan pembatasan hak akses untuk lima role: Admin, Kepala Sekolah, Wali Kelas, Guru Mapel, dan Orang Tua.
- **Manajemen Master Data**
  - CRUD data siswa, termasuk data pendapatan orang tua untuk kebutuhan rekomendasi beasiswa.
  - Manajemen kelas, tahun ajaran, semester, mata pelajaran, dan guru.
- **Manajemen Akademik**
  - Input nilai Tugas, UTS, dan UAS.
  - Pencatatan absensi siswa.
- **Audit Trail**
  - Pencatatan otomatis setiap perubahan nilai siswa, termasuk pengguna yang melakukan perubahan, waktu perubahan, dan nilai baru (*new value*).
- **Pemrosesan Data dan Pemeringkatan**
  - Kalkulasi ranking kelas berdasarkan akumulasi atau rata-rata nilai.
  - Ranking rekomendasi beasiswa berdasarkan kombinasi pendapatan orang tua dan nilai akademik.
- **Pelaporan dan Visualisasi**
  - Dashboard analitik dengan grafik ringkasan.
  - Ekspor laporan ke format PDF dan Excel.

### 1.2 Out-of-Scope

Fitur yang tidak termasuk dalam ruang lingkup pengerjaan:

- **Sistem Pembayaran:** tidak ada integrasi *payment gateway* atau pencatatan pembayaran SPP.
- **Learning Management System (LMS):** tidak ada fitur unggah tugas atau materi oleh guru maupun siswa.
- **Sistem Pesan (Chat):** tidak ada komunikasi *real-time* antar pengguna di dalam sistem.
- **Audit Log Kompleks:** log hanya mencatat *new value*, tanpa menyimpan *old value* atau komparasi histori nilai secara lengkap.

---

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

---

## 3. User Story dan Acceptance Criteria

### 3.1 Role: Admin

#### US-01 — Manajemen Master Data

**User Story**

Sebagai Admin, saya ingin menambah, mengedit, dan menghapus data siswa beserta data pendapatan orang tuanya, agar sistem memiliki data yang valid untuk kebutuhan rapor dan kalkulasi rekomendasi beasiswa.

**Acceptance Criteria**

- [ ] Form input siswa memuat nama, NISN, kelas, dan pendapatan orang tua.
- [ ] Data siswa berhasil tersimpan di database setelah validasi berhasil.
- [ ] Admin dapat mencari siswa berdasarkan NISN atau nama.
- [ ] Sistem memvalidasi data wajib dan mencegah NISN duplikat sesuai aturan sekolah.

### 3.2 Role: Wali Kelas

#### US-02 — Manajemen Nilai dan Rekapitulasi

**User Story**

Sebagai Wali Kelas, saya ingin menginput nilai mata pelajaran yang menjadi tanggung jawab saya dan melihat nilai dari Guru Mapel, agar saya dapat memeriksa rekap rapor dan ranking kelas.

**Acceptance Criteria**

- [ ] Wali Kelas hanya dapat melihat siswa di kelas yang ditugaskan kepadanya.
- [ ] Wali Kelas dapat menginput atau mengedit nilai Tugas, UTS, dan UAS untuk mata pelajaran yang menjadi kewenangannya.
- [ ] Wali Kelas dapat melihat nilai yang telah diserahkan Guru Mapel.
- [ ] Sistem menghitung dan menampilkan ranking kelas ketika proses **Generate Rekap** dijalankan.
- [ ] Sistem tidak mengizinkan Wali Kelas mengubah nilai yang menjadi kewenangan Guru Mapel tanpa mekanisme revisi yang sah.

### 3.3 Role: Guru Mapel

#### US-03 — Input Nilai Mata Pelajaran

**User Story**

Sebagai Guru Mapel, saya ingin memilih kelas yang ditugaskan dan memasukkan nilai untuk mata pelajaran yang saya ampu, agar proses input nilai tidak sepenuhnya dibebankan kepada Wali Kelas.

**Acceptance Criteria**

- [ ] Guru Mapel hanya dapat melihat kelas dan mata pelajaran yang ditugaskan kepadanya.
- [ ] Guru Mapel hanya dapat menginput dan mengedit nilai untuk penugasan yang menjadi kewenangannya.
- [ ] Sistem menolak permintaan perubahan nilai di luar kewenangan dengan respons HTTP **403 Forbidden**.
- [ ] Nilai yang telah diserahkan dapat dilihat Wali Kelas pada rekap kelas.

### 3.4 Role: Kepala Sekolah

#### US-04 — Monitoring Dashboard dan Rekomendasi Beasiswa

**User Story**

Sebagai Kepala Sekolah, saya ingin melihat grafik performa sekolah dan daftar rekomendasi penerima beasiswa, agar saya dapat mendukung pengambilan keputusan akademik dan bantuan finansial.

**Acceptance Criteria**

- [ ] Dashboard menampilkan grafik statistik kehadiran dan rata-rata nilai per kelas.
- [ ] Tersedia tabel **Rekomendasi Beasiswa** yang mengurutkan siswa berdasarkan skor gabungan pendapatan orang tua dan nilai akademik.
- [ ] Rumus dan bobot perhitungan ranking didokumentasikan serta diterapkan secara konsisten.
- [ ] Tabel rekomendasi dapat diekspor ke PDF dan Excel.
- [ ] Hasil ranking ditampilkan sebagai rekomendasi, bukan keputusan final penerima bantuan apabila keputusan tersebut berada pada kewenangan pihak lain.

### 3.5 Role: Orang Tua

#### US-05 — Memantau Nilai Anak

**User Story**

Sebagai Orang Tua, saya ingin login untuk melihat nilai dan absensi anak saya tanpa dapat mengubah data, agar saya dapat memantau perkembangan akademiknya.

**Acceptance Criteria**

- [ ] Orang Tua dapat login menggunakan kredensial akun yang terhubung dengan anaknya.
- [ ] Sistem menampilkan nilai dan absensi hanya untuk anak yang terhubung dengan akun Orang Tua tersebut.
- [ ] Dashboard Orang Tua hanya menampilkan data yang relevan dengan anaknya.
- [ ] Tidak ada tombol tambah, edit, atau hapus pada antarmuka Orang Tua.
- [ ] Backend tetap menolak akses baca maupun perubahan terhadap data siswa lain, meskipun URL atau request dimanipulasi.

---

## 4. Asumsi Proyek

### 4.1 Arsitektur Database dan ORM

Pengembangan menggunakan SQLite, dengan rencana migrasi ke MySQL atau PostgreSQL pada lingkungan produksi. Query database akan mengutamakan Eloquent ORM dan menghindari SQL yang spesifik terhadap satu *database engine*, sejauh memungkinkan.

Migrasi database, tipe data, indeks, constraint, dan pengujian kompatibilitas tetap perlu diperiksa sebelum perpindahan ke produksi.

### 4.2 Implementasi Audit Trail

Audit Trail diimplementasikan menggunakan Eloquent Events, misalnya melalui **Observer** pada proses perubahan data nilai.

Log minimal mencatat:

- Identitas nilai yang diubah.
- Pengguna yang melakukan perubahan.
- Waktu perubahan.
- Nilai baru (*new value*).

Sesuai ruang lingkup, nilai lama (*old value*) tidak disimpan. Implementasi perlu mencakup proses perubahan melalui jalur aplikasi yang digunakan agar perubahan nilai tidak terlewat.

### 4.3 Kalkulasi Ranking Beasiswa

Perhitungan ranking beasiswa diasumsikan menggunakan metode skoring linier atau pembobotan sederhana. Contoh awal:

- 60% skor kondisi finansial.
- 40% skor nilai akademik.

Bobot tersebut masih merupakan **asumsi awal** dan harus dikonfirmasi dengan pihak sekolah sebelum diterapkan.

Karena pendapatan dan nilai akademik memiliki arah preferensi berbeda, data perlu dinormalisasi ke skala yang sebanding. Skor finansial yang lebih baik harus merepresentasikan kebutuhan bantuan yang lebih tinggi, sedangkan skor akademik mengikuti aturan seleksi yang disepakati. Rumus akhir ditempatkan pada service layer agar mudah diuji dan diubah tanpa menyebarkan logika ke controller.

### 4.4 Otorisasi Orang Tua

Akun Orang Tua diasumsikan dibuat otomatis ketika Admin membuat data siswa. Namun, relasi akun harus dirancang agar mendukung kondisi satu siswa memiliki lebih dari satu orang tua/wali dan satu orang tua/wali memiliki lebih dari satu anak.

Akun tidak sebaiknya menggunakan NISN anak sebagai satu-satunya rahasia autentikasi. Sistem perlu menyediakan kredensial awal yang aman, mekanisme penggantian kata sandi, dan validasi hubungan Orang Tua dengan siswa.

---

## Catatan Implementasi

Beberapa keputusan perlu dikonfirmasi sebelum pengembangan dimulai:

1. **Formula ranking kelas:** apakah menggunakan total nilai, rata-rata nilai, atau formula lain? Tentukan juga cara menangani nilai kosong dan nilai yang belum diserahkan.
2. **Formula beasiswa:** konfirmasi bobot, normalisasi pendapatan, kriteria akademik, dan aturan jika dua siswa memiliki skor sama.
3. **Alur revisi nilai:** tentukan apakah nilai berstatus `submitted` dapat langsung diedit oleh guru atau harus dikembalikan untuk revisi.
4. **Finalisasi rapor:** tetapkan siapa yang berwenang mengunci dan memublikasikan rapor.
5. **Data sensitif:** batasi akses pendapatan orang tua hanya kepada pihak yang membutuhkannya.
6. **Pengujian keamanan:** uji pembatasan akses pada backend, termasuk percobaan mengakses data siswa lain melalui URL atau request yang dimanipulasi.

