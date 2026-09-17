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

## [2.3.1] - 2026-09-17
### Fixed
- Secret yang Tanggal Jatuh Tempo-nya **persis hari ini** belum diisolir
  oleh scheduler — script yang dibuat memakai perbandingan "lebih kecil
  dari" (`$dueInt < $todayInt`) sehingga baru dianggap lewat mulai besok.
  Diganti jadi "lebih kecil atau sama dengan" (`<=`) supaya isolir jalan
  tepat di hari H, bukan H+1 (`ros_build_ppp_isolir_checker()` di
  `include/roscompat.php`).
  **Perlu tindakan:** perbaikan ini cuma berlaku untuk scheduler yang baru
  dibuat/diperbarui. Router yang sudah lebih dulu punya scheduler
  `mikhmon-ppp-isolir` (dibuat sebelum versi ini) masih menjalankan script
  lama sampai di-refresh — cukup buka PPP Secrets, klik Simpan lagi di
  kartu Auto Isolir (boleh tanpa mengubah pilihan profile), atau edit
  salah satu secret yang punya Tanggal Jatuh Tempo dan Simpan.

## [2.3.0] - 2026-09-17
### Added
- Tombol **"Aktifkan"** untuk memulihkan PPPoE secret yang sedang diisolir
  (atau memperpanjang yang belum jatuh tempo), muncul di daftar PPP
  Secrets dan di halaman Edit Secret kalau secret punya Tanggal Jatuh
  Tempo. Satu klik: profile dikembalikan ke profile asal (sebelum
  diisolir) dan Tanggal Jatuh Tempo otomatis diperpanjang 30 hari dari
  hari ini — tidak perlu lagi ubah Profile dan Tanggal Jatuh Tempo manual
  satu-satu di form Edit. Endpoint baru `process/pppactivate.php`.

## [2.2.1] - 2026-09-17
### Fixed
- Tambah/Edit PPP Secret gagal total (secret tidak tersimpan sama sekali)
  begitu field **Local Address** atau **Remote Address** dikosongkan —
  router menolak dengan `invalid value for argument address` karena
  Mikhmon mengirim field itu sebagai string kosong ke RouterOS API
  (`=local-address=` tanpa nilai), padahal seharusnya field itu tidak
  dikirim sama sekali kalau memang mau dikosongkan/ikut default profile.
  Kegagalan ini sebelumnya **senyap** — halaman tetap redirect ke daftar
  secret seolah berhasil, jadi tidak ada tanda apa pun bahwa proses
  tambah/edit gagal.
- Semua respons `!trap` (penolakan) dari RouterOS API sekarang ditangkap
  dan ditampilkan sebagai pesan error di halaman Tambah/Edit PPP Secret
  (`ros_trap_message()` baru di `include/roscompat.php`) — sebelumnya
  tidak ada satu pun halaman di aplikasi ini yang memeriksa `!trap`, jadi
  kegagalan apa pun dari router (nama duplikat, dsb.) selalu senyap.

## [2.2.0] - 2026-09-16
### Added
- Auto isolir PPPoE: secret yang diberi **Tanggal Jatuh Tempo** (form
  Tambah/Edit PPP Secret) otomatis dipindah ke profile isolir pilihan begitu
  tanggalnya lewat — dicek router tiap jam lewat scheduler
  `mikhmon-ppp-isolir` yang dibuat/diperbarui otomatis. Profile tujuan
  (mis. "Isolir") diatur sekali per router session di halaman PPP Secrets
  (kartu baru "Auto Isolir"); kosongkan pilihannya untuk mematikan fitur ini.
  Tanggal jatuh tempo diisi manual per pelanggan, bukan dihitung otomatis
  dari tanggal aktivasi/pembayaran.
  Data tanggal disimpan sebagai tag di depan Comment secret
  (`ISOLIR|<tanggal>|<profile-asal>|<comment asli>`) supaya tidak perlu
  field baru di RouterOS; profile asal ikut dicatat supaya begitu
  diperpanjang, staff cukup ganti tanggal dan (kalau perlu) profile —
  fitur ini tidak menghapus/menonaktifkan secret, hanya memindah profile.
  Pengaturan profile isolir per session disimpan di `include/pppisolir.json`
  (pola sama dengan `include/olt.json`).

## [2.1.1] - 2026-09-15
### Changed
- Monitoring OLT (2.1.0) sekarang **terikat per router session**, bukan satu
  daftar global. Tiap router bisa punya OLT sendiri-sendiri, dan menu "OLT
  Monitoring" cuma muncul setelah masuk ke session router tertentu
  (`admin.php?id=olt&session=<nama>`) — dihapus dari sidenav sebelum pilih
  router karena OLT memang milik masing-masing lokasi/router, bukan konsep
  global. `include/olt.json` berubah struktur jadi dict per-session
  (`{ "<session>": [ ...daftar OLT... ] }`).

## [2.1.0] - 2026-09-15
### Added
- Monitoring OLT: menu baru "OLT Monitoring" (halaman `settings/olt.php`),
  daftar terpisah dari session router Mikrotik — tidak terbatas satu OLT per
  session. Tiap OLT (nama, host, port, protokol) dicek status UP/DOWN lewat
  TCP connect ke port web admin-nya (biasanya 80/443, endpoint baru
  `process/oltstatus.php`), dicek paralel per-OLT lewat AJAX supaya OLT yang
  down tidak memperlambat yang lain. Ada tombol "Buka" untuk langsung buka
  web admin OLT di tab baru. Data disimpan di `include/olt.json` (bukan di
  config.php, karena tidak terikat ke satu router).

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
[2.3.1]: https://github.com/mikrosetting99/mikhmon_mod/compare/ab6f1d0...main
[2.3.0]: https://github.com/mikrosetting99/mikhmon_mod/compare/8cbeda8...ab6f1d0
[2.2.1]: https://github.com/mikrosetting99/mikhmon_mod/compare/c3cf148...8cbeda8
[2.2.0]: https://github.com/mikrosetting99/mikhmon_mod/compare/43c5aa3...c3cf148
[2.1.1]: https://github.com/mikrosetting99/mikhmon_mod/compare/24863a3...43c5aa3
[2.1.0]: https://github.com/mikrosetting99/mikhmon_mod/compare/94f40db...24863a3
[2.0.1]: https://github.com/mikrosetting99/mikhmon_mod/compare/a258a59...94f40db
[2.0.0]: https://github.com/mikrosetting99/mikhmon_mod/compare/a0fcee7...a258a59
[1.0.0]: https://github.com/mikrosetting99/mikhmon_mod/compare/947655a...a0fcee7
