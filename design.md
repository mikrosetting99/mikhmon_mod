# Design — Mikhmon (Cobalt)

Sistem desain terkunci untuk tema **Cobalt**. Setiap perubahan tampilan berikutnya
membaca file ini lebih dulu. Jangan bikin sistem baru per halaman — perluas atau
amandemen file ini kalau sistemnya perlu tumbuh.

Tema ini **aditif**: lima tema bawaan (dark, light, blue, green, pink) tidak diubah
sama sekali. Ganti tema lewat **Settings → Theme**.

## Genre

`modern-minimal` — register dev-tool/instrumen. Mikhmon adalah alat operasional
yang dipelototi berjam-jam, bukan halaman pemasaran. Fungsi yang membawa halaman;
tidak ada enrichment, ilustrasi, atau hiasan.

## Macrostructure

`Workbench` — side-rail navigasi tetap, konten padat, hierarki dibawa oleh garis
dan berat huruf, bukan oleh bayangan atau jarak besar.

- Halaman aplikasi: Workbench. Variasi hanya pada susunan kartu.
- Halaman auth (login): kartu tunggal terpusat, lebar 380px.
- Halaman publik (cek status voucher): kartu tunggal, tanpa side-rail.

## Theme

Sumbu diversifikasi: **paper = dark (L 20.5%) · display = grotesk-sans (Inter, roman) · accent = cool (235°)**

| Token | Nilai | Peran |
|---|---|---|
| `--color-paper` | `oklch(20.5% 0.012 250)` | latar aplikasi |
| `--color-paper-2` | `oklch(24.5% 0.013 250)` | kartu, sidenav, navbar |
| `--color-paper-3` | `oklch(28.5% 0.014 250)` | header kartu, hover |
| `--color-paper-4` | `oklch(33% 0.015 250)` | aktif, ditekan |
| `--color-well` | `oklch(17% 0.012 250)` | input, sumur |
| `--color-ink` | `oklch(96% 0.004 250)` | teks utama |
| `--color-ink-2` | `oklch(76% 0.011 250)` | teks sekunder |
| `--color-ink-3` | `oklch(60% 0.012 250)` | teks redup, placeholder |
| `--color-rule` | `oklch(32% 0.014 250)` | garis hairline |
| `--color-accent` | `oklch(70% 0.132 235)` | aksen — mempertahankan `#20a8d8` |
| `--color-focus` | `oklch(82% 0.120 235)` | ring fokus |

Aksen dipakai **maksimal ~5% per layar**: tombol primer, tautan, spine 3px pada
menu aktif, ring fokus, garis pace. Tidak pernah sebagai latar seluruh panel.

Nilai lengkap ada di [`css/tokens.css`](css/tokens.css).

## Typography

- **Body & display:** Inter (variable, 100–900), roman. Tracking `-0.011em`.
- **Mono:** JetBrains Mono (variable) — kode voucher, MAC, IP, editor template.
- **Angka:** `font-variant-numeric: tabular-nums` di seluruh dokumen supaya kolom
  angka pada tabel rata.
- Keduanya **di-host lokal** di `css/fonts/` (79 KB total, lisensi OFL). Tidak ada
  permintaan ke CDN — Mikhmon sering dipakai offline di samping router.
- Header tabel: 11px, uppercase, tracking `0.06em`.
- Tidak ada header miring. Penekanan dibawa oleh berat dan warna.

## Spacing

Skala 4pt bernama (`--space-3xs` … `--space-2xl`). Halaman memakai token, bukan
nilai mentah.

**Pengecualian yang disengaja:** utilitas `.mr-*`, `.pd-*`, `.bmh-*` tetap memakai
nilai piksel literal yang sama persis dengan framework lama (`.mr-5` = `margin:5px`).
Utilitas itu tertanam di markup 101 halaman; mengubah nilainya akan menggeser tata
letak di mana-mana.

## Radius

`--radius-card: 6px` · `--radius-input: 5px` · `--radius-sm: 4px`. Ketat, sesuai
register teknis. Bukan pill, kecuali progress bar dan scrollbar.

## Motion

- Easing: `--ease-out: cubic-bezier(0.16, 1, 0.3, 1)`, `--ease-in`, `--ease-in-out`.
  Tidak pernah `ease` bawaan, tidak pernah bounce/overshoot.
- Durasi: `--dur-instant 90ms` · `--dur-short 160ms` · `--dur-medium 240ms`.
- Hanya `transform`, `opacity`, `background-color`, `border-color` yang dianimasikan.
- Tidak ada reveal saat scroll. Halaman tersusun, bukan tampil bertahap.
- `prefers-reduced-motion: reduce` memangkas semua transisi; spinner tetap berputar
  karena ia menandakan status, bukan dekorasi.

## Microinteractions

- Sukses itu senyap — tidak ada toast perayaan.
- Ring fokus **tidak pernah dianimasikan**; muncul seketika pada `:focus-visible`.
- Hover pada baris tabel `90ms` linear, cukup untuk terbaca tanpa terasa lamban.

## CTA voice

- **Primer:** isi aksen (`.btn.bg-primary`), tinta gelap, radius 5px, ikon di kiri.
- **Sekunder:** `.btn` netral — permukaan `paper-3`, border `rule`.
- **Destruktif:** `.btn.bg-danger`.
- Semua tombol menekan `translateY(1px)` saat `:active`.

## Delapan state

Setiap elemen interaktif (`.btn`, `.btn-login`, `.form-control`, `.group-item`,
`.sidenav a`, `.dropdown-btn`, `.modal-close`) mengimplementasikan: default ·
hover · `:focus-visible` · `:active` · disabled · loading · error · success.

## Yang WAJIB sama di semua halaman

- Aksen dan penempatannya (≤5% per layar).
- Inter + JetBrains Mono.
- Bentuk tombol (radius 5px, ritme padding `--space-xs` / `--space-md`).
- Hairline 1px `--color-rule` sebagai pembawa hierarki.

## Yang BOLEH berbeda

- Susunan kartu per halaman.
- Kepadatan tabel (`.table-bordered` untuk data, polos untuk tata letak form).

## Batasan yang mengikat implementasi

1. **`.table` dipakai sebagai tata letak form** di sebagian besar halaman
   (sel label + sel input), bukan cuma tabel data. Karena itu `.table td` **tidak
   boleh** punya border bawaan — tabel data ikut serta lewat `.table-bordered`.
2. **`js/mikhmon-ui.<theme>.min.js` menulis warna border sidenav lewat inline
   style**, yang menimpa CSS. Tiap tema butuh varian JS-nya sendiri.
3. **Nama file wajib `mikhmon-ui.<theme>.min.css`** — `include/headhtml.php`
   menuliskan sufiks `.min.css` secara hardcoded. File Cobalt sengaja dibiarkan
   tidak terminifikasi agar bisa dirawat.
4. `.form-control` dan `.group-item` wajib `min-width: 0`, kalau tidak lebar
   intrinsik input menembus kartu di layar sempit.

## Berkas

| Berkas | Isi |
|---|---|
| [`css/tokens.css`](css/tokens.css) | seluruh token, portabel |
| [`css/mikhmon-ui.cobalt.min.css`](css/mikhmon-ui.cobalt.min.css) | tema, 296 selector |
| [`css/pace.cobalt.css`](css/pace.cobalt.css) | garis progres |
| [`js/mikhmon-ui.cobalt.min.js`](js/mikhmon-ui.cobalt.min.js) | varian JS sidenav |
| [`css/fonts/`](css/fonts/) | Inter + JetBrains Mono (OFL) |
| [`css/preview.html`](css/preview.html) | referensi komponen, buka tanpa login |
