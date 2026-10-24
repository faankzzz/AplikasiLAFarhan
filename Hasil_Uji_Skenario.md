# Laporan Hasil Uji Skenario Aplikasi Alfa One

Dokumen ini berisi hasil pengujian skenario sistem pada **Aplikasi Alfa One** (Aplikasi Pemesanan Air Mineral) yang membandingkan kondisi operasional sebelum dan sesudah menggunakan aplikasi, serta detail teknis hasil uji dari masing-masing skenario.

---

## Indikator yang dapat diukur

Supaya pengujian lebih ilmiah, gunakan beberapa indikator berikut.

| No | Skenario Pengujian | Kondisi Sebelum Menggunakan Aplikasi | Kondisi Setelah Menggunakan Aplikasi | Indikator Efektivitas | Aspek yang Diukur |
| :--- | :--- | :--- | :--- | :--- | :--- |
| 1. | Registrasi & Login | Konsumen baru tidak terdata secara digital; tidak ada pembatasan hak akses (role) yang terintegrasi (semua admin berbagi akun manual). | Registrasi mandiri via web; login dibatasi otomatis berdasarkan role (Konsumen, Admin Penjualan, Admin Stok, Sopir) dalam < 2 detik. | Login berhasil & hak akses terisolasi secara aman. | Keamanan & Efisiensi Waktu |
| 2. | Pemesanan Produk | Pemesanan dilayani via WhatsApp/Telepon secara manual. Admin mencatat pesanan satu per satu, membutuhkan waktu 5-10 menit per transaksi. | Pemesanan mandiri oleh konsumen via katalog online. Data pesanan masuk otomatis dalam 1 detik setelah checkout. | Waktu pemesanan berkurang drastis, bebas antrean chat. | Efisiensi Waktu & Kemudahan Penggunaan |
| 3. | Perhitungan Stok | Pencatatan stok ditulis manual di buku besar atau tabel Excel yang diperbarui sore hari kerja (sering terjadi selisih stok fisik). | Stok terhitung otomatis secara real-time saat transaksi sukses. Log mutasi terbit otomatis tanpa rekap manual. | Selisih stok menjadi 0% dan pencatatan mutasi instan. | Akurasi & Kecepatan Akses Data |
| 4. | Upload Bukti Bayar | Bukti transfer dikirim via WhatsApp. File menumpuk di galeri handphone admin dan rawan terhapus atau terselip. | Bukti transfer langsung diunggah di web dan terikat otomatis dengan ID Pesanan terkait secara permanen. | Semua bukti bayar terdokumentasi terpusat & tidak ada berkas hilang. | Akurasi & Kecepatan Akses Data |
| 5. | Verifikasi Pembayaran | Admin harus mencocokkan mutasi rekening bank dengan tumpukan chat WhatsApp konsumen (memakan waktu 5-15 menit per verifikasi). | Admin cukup membuka halaman verifikasi pembayaran di web, meninjau gambar bukti, lalu memverifikasi dalam 1 klik (< 10 detik). | Waktu verifikasi berkurang hingga 95% dengan tingkat keakuratan tinggi. | Efisiensi Waktu & Kemudahan Penggunaan |
| 6. | Verifikasi Akun Toko | Verifikasi kelayakan reseller/toko dilakukan secara lisan/chat manual tanpa syarat dokumentasi yang terpusat. | Pendaftar toko wajib mengunggah dokumen di form registrasi. Admin meninjau berkas di dasbor sebelum membuka akses belanja toko. | Akun toko tidak dapat melihat harga reseller sebelum terverifikasi sah. | Akurasi & Keamanan |
| 7. | Penanganan Komplain | Komplain masuk lewat pesan personal WhatsApp. Sering terabaikan, tenggelam di chat lain, atau tanpa bukti foto yang jelas. | Komplain dilaporkan melalui sistem dilengkapi detail masalah & foto. Admin merespons terstruktur dan memantau statusnya. | Penyelesaian keluhan terdokumentasi rapi dengan kepuasan rating konsumen. | Kepuasan Pengguna & Akurasi |
| 8. | Pengantaran (Sopir) | Admin menulis rute kirim di kertas surat jalan manual. Sopir tidak memiliki riwayat kiriman terpusat dan rawan salah rute. | Sistem otomatis membagi daftar rute & tugas kiriman langsung ke dashboard masing-masing sopir beserta status pengantaran. | Sopir dapat memperbarui status kiriman secara langsung; riwayat rute tercatat 100%. | Kemudahan Penggunaan & Efisiensi Waktu |
| 9. | Rekap & Laporan | Admin mencocokkan penjualan, mutasi stok, dan pengantaran secara manual setiap akhir bulan (memakan waktu 1-2 hari kerja). | Laporan dan rekapitulasi omset dihitung otomatis oleh sistem dan dapat langsung diunduh ke Excel dalam hitungan detik. | Pembuatan laporan menjadi instan dan bebas kesalahan rekap manual. | Efisiensi Waktu & Akurasi |

---

## 🔍 Detail Teknis Hasil Uji Skenario

### 1. Registrasi dan Login
* **Tujuan Pengujian**: Memastikan pengguna dapat mendaftar dan masuk ke dashboard masing-masing secara cepat dan aman.
* **Prosedur Pengujian**:
  1. Membuka halaman `register.php` dan `login.php`.
  2. Mengisi data pendaftaran lalu masuk menggunakan akun tersebut.
* **Hasil yang Diharapkan**: Validasi form berjalan baik dan user dialihkan ke dashboard sesuai role masing-masing.
* **Hasil Aktual**: Akun berhasil didaftarkan dan sistem login membedakan role dengan aman menggunakan session PHP.
* **Kesimpulan**: **LULUS**

### 2. Pemesanan Produk
* **Tujuan Pengujian**: Memastikan konsumen dapat memesan air mineral secara mandiri melalui katalog online.
* **Prosedur Pengujian**:
  1. Masuk sebagai konsumen, buka halaman belanja `konsumen/pesan.php`.
  2. Memilih barang dan kuantitas produk lalu melakukan checkout.
* **Hasil yang Diharapkan**: Pesanan masuk ke database secara real-time dan stok otomatis terpotong.
* **Hasil Aktual**: Data berhasil terinput ke tabel pesanan dan transaksi belanja.
* **Kesimpulan**: **LULUS**

### 3. Perhitungan Stok
* **Tujuan Pengujian**: Memastikan sistem dapat mengelola mutasi stok masuk dan keluar secara otomatis.
* **Prosedur Pengujian**:
  1. Melakukan checkout barang atau penambahan stok oleh admin stok.
  2. Memeriksa sisa stok di database dan log pada tabel `mutasi_stok`.
* **Hasil yang Diharapkan**: Nilai stok berubah akurat secara otomatis di katalog maupun mutasi.
* **Hasil Aktual**: Stok produk berkurang/bertambah tepat dan mutasi stok tercatat rapi.
* **Kesimpulan**: **LULUS**

### 4. Upload Bukti Pembayaran
* **Tujuan Pengujian**: Menjamin seluruh transaksi transfer bank memiliki dokumentasi bukti bayar yang valid.
* **Prosedur Pengujian**:
  1. Memilih metode pembayaran "Transfer Bank" saat checkout.
  2. Mengunggah gambar bukti pembayaran pada form pesanan.
* **Hasil yang Diharapkan**: Gambar berhasil tersimpan di folder uploads dan tautan tersimpan di database.
* **Hasil Aktual**: File gambar berhasil diunggah dengan aman dan tercatat di tabel pesanan.
* **Kesimpulan**: **LULUS**

### 5. Verifikasi Pembayaran
* **Tujuan Pengujian**: Mempercepat pengecekan keabsahan pembayaran transaksi masuk.
* **Prosedur Pengujian**:
  1. Membuka halaman `admin_penjualan/pesanan.php`.
  2. Memeriksa detail pesanan transfer dan mengeklik tombol "Lihat Bukti Transfer".
* **Hasil yang Diharapkan**: Admin dapat melihat foto bukti transfer langsung dan mengubah status pesanan.
* **Hasil Aktual**: Foto bukti transfer tampil dengan jelas dan status pesanan dapat diperbarui dalam satu klik.
* **Kesimpulan**: **LULUS**

### 6. Verifikasi Akun Toko
* **Tujuan Pengujian**: Memvalidasi kelayakan pendaftar dengan jenis akun Toko/Reseller.
* **Prosedur Pengujian**:
  1. Pendaftar memilih opsi akun Toko di `register.php` dan mengunggah berkas wajib.
  2. Admin Penjualan membuka halaman `admin_penjualan/verifikasi.php` untuk memproses verifikasi.
* **Hasil yang Diharapkan**: Akun Toko tidak bisa belanja sebelum berstatus `verified` dari pihak admin.
* **Hasil Aktual**: Akun berstatus `pending` dan terkunci dari fitur belanja toko hingga disetujui admin.
* **Kesimpulan**: **LULUS**

### 7. Penanganan Komplain Konsumen
* **Tujuan Pengujian**: Memastikan keluhan konsumen tercatat dan ditindaklanjuti secara tertib.
* **Prosedur Pengujian**:
  1. Konsumen mengisi form komplain atas pesanan bermasalah di `konsumen/komplain.php`.
  2. Admin memantau di `admin_penjualan/komplain.php` untuk memberi tanggapan atau menjadwalkan penggantian.
* **Hasil yang Diharapkan**: Keluhan tercatat lengkap dengan foto bukti komplain dan memiliki riwayat status penanganan.
* **Hasil Aktual**: Pengaduan berhasil terdaftar di database, admin dapat membalas keluhan, serta konsumen dapat memberikan rating setelah selesai.
* **Kesimpulan**: **LULUS**

### 8. Pengantaran Pesanan (Sopir)
* **Tujuan Pengujian**: Mengelola penugasan pengiriman barang ke sopir secara real-time.
* **Prosedur Pengujian**:
  1. Admin Penjualan menugaskan sopir pada rute pengiriman tertentu.
  2. Sopir masuk ke `sopir/dashboard.php` untuk melihat tugas pengantaran yang aktif.
* **Hasil yang Diharapkan**: Sopir menerima detail alamat kirim dan dapat memperbarui status pengiriman (dikirim/selesai).
* **Hasil Aktual**: Tugas terdistribusi dengan benar ke dashboard sopir masing-masing dan riwayat pengiriman tercatat rapi.
* **Kesimpulan**: **LULUS**

### 9. Rekap & Laporan Penjualan
* **Tujuan Pengujian**: Menyajikan laporan performa penjualan harian, mingguan, dan bulanan.
* **Prosedur Pengujian**:
  1. Admin membuka menu Rekap Penjualan di `admin_penjualan/rekap.php`.
  2. Mengekspor rekap laporan tersebut menggunakan file `export_excel.php`.
* **Hasil yang Diharapkan**: Sistem menampilkan total omset dan jumlah terjual dengan tepat serta sukses mengunduh file format Excel.
* **Hasil Aktual**: Rekapitulasi omset terhitung otomatis dan file spreadsheet sukses terunduh tanpa ada data yang rusak.
* **Kesimpulan**: **LULUS**
