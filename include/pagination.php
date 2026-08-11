<?php
/*
 *  Paginasi sederhana untuk Mikhmon.
 *
 *  Catatan penting soal batasan:
 *  RouterOS API TIDAK punya parameter limit/offset untuk perintah print, jadi
 *  seluruh baris tetap harus ditarik dari router lebih dulu. Paginasi di sini
 *  memangkas biaya RENDER (ukuran HTML dan kerja browser), bukan lalu lintas
 *  ke router. Untuk menekan lalu lintas, pakai ".proplist" pada query supaya
 *  router hanya mengirim kolom yang benar-benar dipakai.
 */

if (!defined('MIKHMON_PER_PAGE')) {
    define('MIKHMON_PER_PAGE', 50);
}

/**
 * Hitung batas potongan untuk halaman yang sedang dibuka.
 *
 * @param int $total   jumlah seluruh baris
 * @param int $perpage baris per halaman
 * @return array  page, pages, perpage, start, end, total
 */
function mikhmon_paginate($total, $perpage = MIKHMON_PER_PAGE)
{
    $total   = (int) $total;
    $perpage = max(1, (int) $perpage);
    $pages   = max(1, (int) ceil($total / $perpage));

    $page = isset($_GET['hal']) ? (int) $_GET['hal'] : 1;
    if ($page < 1) {
        $page = 1;
    }
    if ($page > $pages) {
        $page = $pages;
    }

    $start = ($page - 1) * $perpage;

    return array(
        'page'    => $page,
        'pages'   => $pages,
        'perpage' => $perpage,
        'start'   => $start,
        'end'     => min($total, $start + $perpage),
        'total'   => $total,
    );
}

/**
 * URL halaman ke-N dengan mempertahankan seluruh parameter yang sedang aktif
 * (profile, comment, exp, session, idhr, idbl, pencarian, dan lain-lain).
 */
function mikhmon_page_url($page)
{
    $q = $_GET;
    unset($q['PHPSESSID']);
    $q['hal'] = (int) $page;
    return './?' . http_build_query($q);
}

/**
 * Cari kata kunci pada sekumpulan baris hasil API.
 *
 * Dijalankan di PHP, bukan di router — tapi karena seluruh baris memang sudah
 * ditarik, pencarian tetap mencakup SEMUA data, bukan cuma halaman yang tampil.
 * Ini yang menjaga fitur cari tetap berguna setelah tabel dipaginasi.
 *
 * @param array $rows   hasil $API->comm(...)
 * @param string $term  kata kunci
 * @param array $fields nama kolom yang ikut dicari
 */
function mikhmon_search($rows, $term, $fields)
{
    $term = trim((string) $term);
    if ($term === '' || !is_array($rows)) {
        return $rows;
    }
    $needle = function_exists('mb_strtolower') ? mb_strtolower($term) : strtolower($term);

    $hit = array();
    foreach ($rows as $row) {
        foreach ($fields as $f) {
            if (!isset($row[$f])) {
                continue;
            }
            $hay = function_exists('mb_strtolower') ? mb_strtolower($row[$f]) : strtolower($row[$f]);
            if (strpos($hay, $needle) !== false) {
                $hit[] = $row;
                break;
            }
        }
    }
    return $hit;
}

/**
 * Navigasi halaman. Memakai class bawaan Mikhmon supaya ikut tema apa pun.
 */
function mikhmon_pagination_nav($p)
{
    if ($p['pages'] <= 1) {
        return;
    }
    $page  = $p['page'];
    $pages = $p['pages'];
    $from  = $p['total'] > 0 ? $p['start'] + 1 : 0;
    $to    = $p['end'];

    echo '<div class="row mr-t-10">';
    echo '<div class="col-6 pd-t-5">';
    echo '<span class="text-grey" style="font-size:12px;">'
       . number_format($from, 0, ',', '.') . '&ndash;' . number_format($to, 0, ',', '.')
       . ' / ' . number_format($p['total'], 0, ',', '.') . '</span>';
    echo '</div>';

    echo '<div class="col-6 text-right pd-b-5">';
    if ($page > 1) {
        echo '<a class="btn" title="Halaman pertama" href="' . htmlspecialchars(mikhmon_page_url(1)) . '"><i class="fa fa-angle-double-left"></i></a>';
        echo '<a class="btn" title="Sebelumnya" href="' . htmlspecialchars(mikhmon_page_url($page - 1)) . '"><i class="fa fa-angle-left"></i></a>';
    }

    // Lompat halaman — lebih ringkas daripada mencetak puluhan nomor.
    echo '<select class="btn" style="min-width:110px;" onchange="location=this.value;" title="Lompat ke halaman">';
    for ($i = 1; $i <= $pages; $i++) {
        echo '<option value="' . htmlspecialchars(mikhmon_page_url($i)) . '"' . ($i == $page ? ' selected' : '') . '>'
           . $i . ' / ' . $pages . '</option>';
    }
    echo '</select>';

    if ($page < $pages) {
        echo '<a class="btn" title="Berikutnya" href="' . htmlspecialchars(mikhmon_page_url($page + 1)) . '"><i class="fa fa-angle-right"></i></a>';
        echo '<a class="btn" title="Halaman terakhir" href="' . htmlspecialchars(mikhmon_page_url($pages)) . '"><i class="fa fa-angle-double-right"></i></a>';
    }
    echo '</div>';
    echo '</div>';
}
