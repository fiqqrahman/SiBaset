# Data Sekolah dan Verifikasi Koordinat SiBaset

Dokumen ini menjelaskan aktivasi struktur data sekolah, impor JSON awal, dan alur koordinat pada branch `stag-dev`.

## Alur penerapan

1. Tarik perubahan terbaru branch `stag-dev`.
2. Pastikan konfigurasi database di `.env` benar dan buat cadangan jika database lama sudah berisi data.
3. Jalankan `php artisan migrate` untuk membuat tabel sekolah, peran pengguna, pengajuan koordinat, dan log impor.
4. Siapkan geometri batas wilayah: `php artisan sibaset:cache-city-boundary`. Perintah ini mengambil dan menyimpan geometri Kota Palangka Raya dari layanan BIG RBI edisi September 2023. Jika layanan BIG tidak dapat diakses, koordinat tidak dianggap aman; perintah akan gagal dan validasi tetap ditutup dengan aman.
5. Buat akun admin melalui `php artisan sibaset:make-admin admin@domain-anda.id`. Perintah meminta kata sandi secara interaktif dan tidak membuat kata sandi default.
6. Login sebagai admin, buka fitur Impor JSON di sisi kanan peta, pilih file, periksa pratinjau, lalu konfirmasi impor.
7. Setelah data sekolah tersedia, buat akun operator dengan `php artisan sibaset:make-operator NPSN operator@domain-anda.id`. Ganti NPSN dan email sesuai sekolah dan operator yang sah.
8. Operator masuk melalui halaman login, membuka Profil Sekolah, memilih titik di peta atau mengisi lintang/bujur, lalu mengirim pengajuan.
9. Admin membuka Verifikasi Koordinat untuk menyetujui atau menolak pengajuan dan memeriksa sekolah yang koordinatnya masih belum valid.

## Aturan impor

- Format JSON harus memiliki properti `data` berisi array objek sekolah.
- NPSN harus berupa 8 digit dan unik dalam satu file.
- Kolom yang disimpan: `sekolah_id`, `npsn`, `nama`, `bentuk_pendidikan`, `status_sekolah`, `alamat_jalan`, `nama_dusun`, `kecamatan`, `kabupaten`, `provinsi`, `nomor_telepon`, `akreditasi`, `lintang`, dan `bujur`.
- Kolom lainnya diabaikan. JSON asli tidak disimpan sebagai salinan data sekolah.
- Data dicocokkan menggunakan NPSN. Pengunggahan ulang memperbarui kolom sumber, tetapi tidak menimpa koordinat yang sudah diverifikasi.
- NPSN duplikat dalam file menghentikan konfirmasi impor agar aplikasi tidak memilih record secara diam-diam.
- Impor dibungkus transaksi database; jika penyimpanan gagal, semua perubahan transaksi dibatalkan.

## Kebijakan koordinat

- Nilai kosong dan pasangan `0,0` tidak dianggap sebagai koordinat yang tersedia.
- Koordinat harus berada dalam rentang geografis dan poligon administratif Kota Palangka Raya.
- Jika batas wilayah tidak bisa dimuat, koordinat tidak diperlakukan sebagai aman. Sekolah masih dapat diimpor tanpa koordinat resmi; verifikasi harus menunggu sampai poligon tersedia.
- Koordinat hasil impor yang masuk wilayah kota tetap berstatus menunggu verifikasi admin.
- Operator hanya dapat mengajukan koordinat untuk sekolah yang terhubung pada akunnya.
- Koordinat usulan tidak mengubah koordinat resmi sampai admin menyetujuinya.
- Peta admin hanya membuat marker sekolah yang berstatus terverifikasi dan lolos pemeriksaan poligon saat halaman dimuat.
- TK, SD, dan SMP memiliki marker khusus dan legenda tersendiri.

## Perintah pemeriksaan

Jalankan tes fitur koordinat dan impor di lingkungan proyek:

```bash
php artisan test --filter=SchoolCoordinateWorkflowsTest
php artisan route:list
php artisan migrate:status
```

Tes yang ada mencakup uji point-in-polygon, pembatasan akses, pengajuan operator, persetujuan admin, login, NPSN duplikat, dan perlindungan koordinat terverifikasi saat impor ulang. Tes perlu dijalankan di lingkungan Laravel yang benar-benar terpasang sebelum fitur dipakai di produksi.
