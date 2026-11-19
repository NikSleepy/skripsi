Kamu adalah Project Manager dan System Analyst berpengalaman. Buatkan project plan lengkap untuk proyek berikut.

KONTEKS PROYEK
- Nama proyek: Dashboard Siswa Sekolah Dasar
- Tujuan: mengelola data siswa dan nilai, menampilkan ringkasan di dashboard,
  serta menghasilkan perangkingan otomatis (ranking kelas dan ranking
  penerima bantuan beasiswa)
- Role pengguna: Admin sekolah, Wali kelas, Kepala sekolah, (opsional) Orang tua
- Fitur utama:
  1. Login dan manajemen user per role
  2. Master data: siswa, kelas, tahun ajaran, semester, mata pelajaran, guru
  3. Input nilai (tugas, UTS, UAS) dan rekap nilai rapor
  4. Absensi / kehadiran
  5. Ranking kelas per semester berdasarkan rata-rata nilai
  6. Ranking calon penerima beasiswa dengan kriteria berbobot
     (nilai, kehadiran, penghasilan orang tua, jumlah tanggungan, dll)
  7. Dashboard ringkasan dan grafik per role
  8. Export laporan (PDF/Excel)
- Tech stack: Laravel 11, PHP 8.2, SQLite (dev), Blade, Breeze
- Tim: 1 developer
- Durasi: [isi, misal 8 minggu]
- Batasan: data siswa adalah data anak, jadi perlu kontrol akses ketat
  dan log perubahan nilai

OUTPUT YANG DIMINTA
(isi 13 poin dari template sebelumnya)

KHUSUS SISTEM RANKING
- Jelaskan metode perhitungan ranking kelas, termasuk aturan jika nilai sama (tie-break)
- Usulkan metode ranking beasiswa (misal SAW atau TOPSIS), lengkap dengan
  kriteria, bobot, contoh perhitungan, dan cara admin mengubah bobot
- Rancang tabel penyimpanan hasil ranking per periode agar bisa ditelusuri riwayatnya

ATURAN
- Bahasa Indonesia, tulis asumsi bila info kurang, maksimal 5 pertanyaan sebelum mulai.