# Changelog

Semua perubahan penting pada fork ini dicatat di sini. Format mengikuti
[Keep a Changelog](https://keepachangelog.com/), versi mengikuti
[Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH` —
MAJOR untuk perubahan besar/berpotensi mengubah tampilan-perilaku secara
signifikan, MINOR untuk fitur baru yang aman ditambahkan, PATCH untuk
perbaikan bug.

Versi di sini **khusus fork ini** (mikrosetting99/mikhmon_mod), terpisah dari
nomor versi upstream Mikhmon (`v3.20`, masih dirujuk di halaman About).

## [Unreleased]

## [2.0.1] - 2026-09-13
### Fixed
- Laporan penjualan bulanan tidak lagi menemukan data di RouterOS 7.24.2+.
  Sejak versi RouterOS tersebut, router memaksa `owner=` pada
  `/system script add` jadi `*sys` dan mengabaikan nilai yang dikirim
  Mikhmon, padahal laporan bulanan (dan widget Income di dashboard)
  memfilter lewat `?owner=<bulan><tahun>`. Data penjualan tetap tersimpan,
  cuma tidak ketemu lagi oleh filter itu.
  Perbaikan: berhenti mengandalkan `owner=` sebagai filter router — ambil
  semua entri `comment=mikhmon`, cocokkan bulan/tahun lewat `source=`
  (tanggal apa adanya, terbukti tetap akurat) di sisi PHP
  (`ros_month_matches()` baru di `include/roscompat.php`).

## [2.0.0] - 2026-09-10
### Added
- Tema **Material** (light & dark) menggantikan seluruh tema lama
  (dark/light/blue/green/pink/Cobalt) sebagai satu-satunya pilihan tema.
  Warna diambil dari swatch resmi Material Design (Blue 700 primer, Green
  800 sukses, Red 700 bahaya, dst), bukan hex kira-kira.
- Progress bar generate voucher yang nyata (persentase asli, bukan sekadar
  spinner) — proses dipecah jadi AJAX per-batch lewat endpoint baru
  `process/genbatch.php`.
### Changed
- Kotak statistik dashboard (Hotspot/PPPoE) diisi warna solid ala Mikhmon
  klasik, baris Hotspot & PPPoE dipadatkan jadi masing-masing satu baris.
  Brand aplikasi disingkat jadi "MIKHMON" (dari "MIKHMON ROS7").
  Halaman Settings & Session Settings dirapikan (proporsi input-group yang
  tidak sejajar, kartu pembungkus yang menyisakan ruang kosong).
### Removed
- Mekanisme anti-tamper tersembunyi (hex-encoded di `js/mikhmon.js` dan
  `settings/settings.php`) yang mengganti seluruh isi halaman jadi pesan
  sabotase kalau teks brand navbar diubah dari "MIKHMON ROS7".

## [1.0.0] - 2026-08-11 s/d 2026-08-13
Baseline fork stabil pertama, di atas upstream Mikhmon v3.20
(laksa19/mikhmonv3).
### Added
- Dukungan RouterOS v7: perbaikan voucher hotspot yang sebelumnya tidak
  pernah expired di ROS7 (`include/roscompat.php`, lapisan kompatibilitas
  format tanggal v6/v7).
- Tema **Cobalt**: UI modern tanpa mengubah markup halaman manapun.
- Menu PPPoE lengkap: profile, secret, cek koneksi aktif, kartu ringkasan
  di dashboard.
- Paginasi 50 baris untuk daftar user, laporan, dan secret PPPoE.
### Fixed
- Kompatibilitas PHP 8 (tema, bahasa, deteksi router mati).
- Tata letak tema Cobalt yang sebelumnya berantakan.
### Performance
- Halaman laporan dan pengambilan log dipercepat.
- `.proplist` dicabut dari query daftar voucher & secret PPPoE supaya
  router hanya mengirim data yang dipakai.

[Unreleased]: https://github.com/mikrosetting99/mikhmon_mod/compare/main...HEAD
[2.0.1]: https://github.com/mikrosetting99/mikhmon_mod/compare/a258a59...94f40db
[2.0.0]: https://github.com/mikrosetting99/mikhmon_mod/compare/a0fcee7...a258a59
[1.0.0]: https://github.com/mikrosetting99/mikhmon_mod/compare/947655a...a0fcee7
