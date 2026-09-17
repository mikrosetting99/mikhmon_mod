<?php
/*
 *  RouterOS v6 / v7 compatibility layer for Mikhmon.
 *
 *  Masalah:
 *  RouterOS v7.10+ mengubah format tanggal dari "mmm/dd/yyyy" (v6) menjadi
 *  ISO "yyyy-mm-dd". Script on-login dan scheduler bawaan Mikhmon memotong
 *  string tanggal dengan posisi tetap (:pick $d 0 3, :pick $d 7 11) sehingga
 *  di v7 komentar expired tertulis dengan format yang salah, dan pengecekan
 *  [:pick $comment 3] = "/" pada background service tidak pernah terpenuhi.
 *  Akibatnya user hotspot tidak pernah dihapus / di-expired -> voucher jadi
 *  seolah-olah unlimited.
 *
 *  Solusi:
 *  Script yang dihasilkan mendeteksi format tanggal saat runtime, lalu SELALU
 *  menulis komentar expired dalam format kanonik v6 "mmm/dd/yyyy hh:mm:ss".
 *  Dengan begitu seluruh kode PHP Mikhmon (daftar user, status voucher, cetak
 *  voucher, laporan penjualan) tetap bekerja apa adanya di v6 maupun v7,
 *  termasuk bila router di-upgrade dari v6 ke v7 di kemudian hari.
 *
 *  Background service tetap bisa membaca komentar format ISO yang terlanjur
 *  dibuat oleh Mikhmon versi lama di router v7, sehingga user lama ikut
 *  ter-expired tanpa perlu dibuat ulang.
 */

/**
 * Fungsi RouterOS: ubah tanggal (v6 "mmm/dd/yyyy" atau ISO "yyyy-mm-dd")
 * menjadi format kanonik v6 "mmm/dd/yyyy".
 */
function ros_fn_mkdate()
{
    return ':local mkd do={' .
        ':local ma ("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec"); ' .
        ':if ([:len $d] = 10 and [:pick $d 4] = "-") do={' .
        ':local ms [:pick $d 5 7]; ' .
        ':if ([:pick $ms 0] = "0") do={:set ms [:pick $ms 1 2]}; ' .
        ':return ([:pick $ma ([:tonum $ms] - 1)] . "/" . [:pick $d 8 10] . "/" . [:pick $d 0 4])' .
        '}; ' .
        ':return $d' .
        '}';
}

/**
 * Fungsi RouterOS: tanggal (kedua format) -> integer yyyymmdd untuk dibandingkan.
 */
function ros_fn_dateint()
{
    return ':local dint do={' .
        ':local ma ("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec"); ' .
        ':if ([:len $d] >= 10 and [:pick $d 4] = "-") do={' .
        ':return [:tonum ([:pick $d 0 4] . [:pick $d 5 7] . [:pick $d 8 10])]' .
        '}; ' .
        ':local mi ([:find $ma [:pick $d 0 3]] + 1); ' .
        ':local ms "$mi"; ' .
        ':if ($mi < 10) do={:set ms "0$mi"}; ' .
        ':return [:tonum ([:pick $d 7 11] . $ms . [:pick $d 4 6])]' .
        '}';
}

/**
 * Fungsi RouterOS: jam "hh:mm:ss" -> menit sejak tengah malam.
 */
function ros_fn_timeint()
{
    return ':local tint do={' .
        ':local h [:pick $t 0 2]; ' .
        ':if ([:pick $h 0] = "0") do={:set h [:pick $h 1 2]}; ' .
        ':local m [:pick $t 3 5]; ' .
        ':if ([:pick $m 0] = "0") do={:set m [:pick $m 1 2]}; ' .
        ':return ([:tonum $h] * 60 + [:tonum $m])' .
        '}';
}

/**
 * Bangun script on-login untuk hotspot user profile.
 *
 * Header ":put (",mode,price,validity,sprice,,lock,")" dipertahankan persis
 * seperti aslinya karena dipakai Mikhmon untuk membaca harga/validity dengan
 * explode(",", $onlogin)[1..6].
 */
function ros_build_onlogin($expmode, $price, $validity, $sprice, $getlock, $profilename)
{
    if ($getlock == "Enable") {
        $lock = '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]';
    } else {
        $lock = "";
    }

    $header = ':put (",' . $expmode . ',' . $price . ',' . $validity . ',' . $sprice . ',,' . $getlock . ',"); ';

    // Mode "None": tidak ada expired, hanya menyimpan harga.
    if ($expmode == "0") {
        if ($price != "") {
            return ':put (",,' . $price . ',,,noexp,' . $getlock . ',")' . $lock;
        }
        return "";
    }

    $body = '{' .
        ':local comment [/ip hotspot user get [/ip hotspot user find where name="$user"] comment]; ' .
        ':local ucode [:pick $comment 0 2]; ' .
        ':if ($ucode = "vc" or $ucode = "up" or $comment = "") do={' .
        ros_fn_mkdate() . '; ' .
        // start-date HARUS memakai format asli router, bukan format kanonik.
        ':local rawdate [/system clock get date]; ' .
        ':local date [$mkd d=$rawdate]; ' .
        ':local year [:pick $date 7 11]; ' .
        ':local month [:pick $date 0 3]; ' .
        '/system scheduler add name="$user" disabled=no start-date=$rawdate interval="' . $validity . '"; ' .
        ':delay 5s; ' .
        ':local exp [/system scheduler get [/system scheduler find where name="$user"] next-run]; ' .
        ':local ed $date; ' .
        ':local et $exp; ' .
        ':local sp [:find $exp " "]; ' .
        // next-run bisa berupa "hh:mm:ss" (hari ini), "mmm/dd hh:mm:ss" (v6,
        // tahun berjalan), "mmm/dd/yyyy hh:mm:ss" (v6) atau
        // "yyyy-mm-dd hh:mm:ss" (v7.10+). Semua dinormalkan ke format v6.
        ':if ([:typeof $sp] = "num") do={' .
        ':set et [:pick $exp ($sp + 1) ($sp + 9)]; ' .
        ':local dp [:pick $exp 0 $sp]; ' .
        ':if ([:len $dp] = 6) do={:set ed "$dp/$year"} else={:set ed [$mkd d=$dp]}' .
        '}; ' .
        '/ip hotspot user set comment="$ed $et" [find where name="$user"]; ' .
        ':delay 5s; ' .
        '/system scheduler remove [/system scheduler find where name="$user"]';

    // Record penjualan (mode remc / ntfc). $date sudah kanonik sehingga
    // source= dan owner= cocok dengan filter laporan di report/selling.php.
    $record = '';
    if ($expmode == "remc" || $expmode == "ntfc") {
        $record = '; :local mac $"mac-address"; :local time [/system clock get time]; ' .
            '/system script add name="$date-|-$time-|-$user-|-' . $price . '-|-$address-|-$mac-|-' . $validity .
            '-|-' . $profilename . '-|-$comment" owner="$month$year" source="$date" comment="mikhmon"';
    }

    return $header . $body . $record . $lock . "}}";
}

/**
 * Bangun script scheduler pemantau profile (menghapus / menandai user expired).
 *
 * @param string $mode "remove" atau "set limit-uptime=1s"
 */
function ros_build_bgservice($profilename, $mode)
{
    return ros_fn_dateint() . '; ' .
        ros_fn_timeint() . '; ' .
        ':local nd [/system clock get date]; ' .
        ':local nt [/system clock get time]; ' .
        ':local today [$dint d=$nd]; ' .
        ':local curtime [$tint t=$nt]; ' .
        ':foreach i in [/ip hotspot user find where profile="' . $profilename . '"] do={' .
        ':local cm [/ip hotspot user get $i comment]; ' .
        ':local nm [/ip hotspot user get $i name]; ' .
        ':local ok false; ' .
        // Terima format v6 "mmm/dd/yyyy hh:mm:ss" maupun ISO "yyyy-mm-dd hh:mm:ss"
        // (komentar warisan Mikhmon lama di router v7).
        ':if ([:len $cm] > 18) do={' .
        ':if ([:pick $cm 3] = "/" and [:pick $cm 6] = "/") do={:set ok true}; ' .
        ':if ([:pick $cm 4] = "-" and [:pick $cm 7] = "-") do={:set ok true}' .
        '}; ' .
        ':if ($ok) do={' .
        ':local sp [:find $cm " "]; ' .
        ':if ([:typeof $sp] = "num") do={' .
        ':local eds [:pick $cm 0 $sp]; ' .
        ':local ets [:pick $cm ($sp + 1) ($sp + 9)]; ' .
        ':local expd [$dint d=$eds]; ' .
        ':local expt [$tint t=$ets]; ' .
        ':if ($expd < $today or ($expd = $today and $expt <= $curtime)) do={' .
        '/ip hotspot user ' . $mode . ' $i; ' .
        '/ip hotspot active remove [find where user=$nm]' .
        '}}}}';
}

/**
 * Mode yang dipakai background service untuk sebuah expired mode.
 */
function ros_expmode_action($expmode)
{
    if ($expmode == "ntf" || $expmode == "ntfc") {
        return "set limit-uptime=1s";
    }
    return "remove";
}

/**
 * Apakah komentar user berisi tanggal expired?
 * Menerima format v6 "mmm/dd/yyyy hh:mm:ss" dan ISO "yyyy-mm-dd hh:mm:ss".
 */
function ros_is_exp_comment($s)
{
    if ($s === null || $s === "") {
        return false;
    }
    if (substr($s, 3, 1) === "/" && substr($s, 6, 1) === "/") {
        return true;
    }
    if (substr($s, 4, 1) === "-" && substr($s, 7, 1) === "-") {
        return true;
    }
    return false;
}

/**
 * Panjang bagian tanggal+jam pada komentar expired (20 utk v6, 19 utk ISO).
 */
function ros_exp_comment_len($s)
{
    if (substr($s, 4, 1) === "-" && substr($s, 7, 1) === "-") {
        return 19;
    }
    return 20;
}

/**
 * Sejak sebuah versi RouterOS 7.x (dikonfirmasi terjadi di 7.24.2), router
 * tidak lagi menghormati owner= kustom pada /system script add — nilainya
 * selalu ditimpa jadi "*sys" oleh router, apa pun yang dikirim Mikhmon.
 * Laporan penjualan bulanan sebelumnya memfilter /system/script/print lewat
 * ?owner=<bulan><tahun> (mis. "sep2026"), jadi filter itu berhenti berfungsi
 * begitu router di-upgrade — datanya tetap tersimpan, cuma tidak ketemu lagi.
 *
 * source= (tanggal apa adanya, format kanonik v6 "mmm/dd/yyyy" dari
 * ros_build_onlogin) terbukti tetap akurat, jadi pencocokan bulan+tahun
 * dipindah ke PHP: ambil semua entri comment=mikhmon, lalu saring di sini.
 */
function ros_month_matches($sourceDate, $idbl)
{
    if (strlen($sourceDate) < 7 || $idbl == "") {
        return false;
    }
    return strtolower(substr($sourceDate, 0, 3)) . substr($sourceDate, -4) === strtolower($idbl);
}

/* ==================================================================
 * Auto isolir PPPoE — tanggal jatuh tempo per secret disimpan sebagai tag
 * di depan comment PPP secret: "ISOLIR|<due:Y-m-d>|<orig-profile>|<sisa
 * comment bebas>". Tag ini yang dibaca baik oleh PHP (untuk tampilan &
 * form) maupun oleh script scheduler di router (untuk isolir otomatis).
 * ================================================================== */

/**
 * Pecah comment PPP secret jadi due date, profile asal, dan sisa teks
 * bebas. Null kalau secret ini tidak punya tanggal jatuh tempo.
 */
function ros_ppp_parse_isolir($comment)
{
    if (substr($comment, 0, 7) !== 'ISOLIR|') {
        return null;
    }
    $parts = explode('|', substr($comment, 7), 3);
    return array(
        'due' => isset($parts[0]) ? $parts[0] : '',
        'orig_profile' => isset($parts[1]) ? $parts[1] : '',
        'rest' => isset($parts[2]) ? $parts[2] : '',
    );
}

/**
 * Susun kembali comment PPP secret dengan tag isolir di depan. Tanpa
 * due date, comment ditulis apa adanya (fitur ini opt-in per secret).
 */
function ros_ppp_compose_isolir($due, $origProfile, $rest)
{
    if ($due == '') {
        return $rest;
    }
    return 'ISOLIR|' . $due . '|' . $origProfile . '|' . $rest;
}

/**
 * Nama tetap scheduler pengecek isolir di router — satu per router,
 * bukan per secret, supaya gampang dicari/ditimpa ulang saat profile
 * isolir diganti dari Mikhmon.
 */
function ros_ppp_isolir_scheduler_name()
{
    return 'mikhmon-ppp-isolir';
}

/**
 * Bangun script scheduler: jalan berkala, cek semua PPP secret yang
 * bertag ISOLIR|, pindahkan ke profile isolir begitu tanggal jatuh tempo
 * tiba (dueDate <= hari ini, bukan menunggu satu hari lagi lewat).
 * Memakai ros_fn_dateint() yang sama dipakai roscompat lain supaya
 * tetap benar baik router masih format tanggal v6 ("mmm/dd/yyyy") maupun
 * ISO v7.10+ ("yyyy-mm-dd") — due date yang ditulis Mikhmon sendiri selalu
 * ISO, dan ros_fn_dateint() sudah mengenali format itu juga.
 */
function ros_build_ppp_isolir_checker($isolirProfile)
{
    $isolirProfile = str_replace('"', '', $isolirProfile);
    return ros_fn_dateint() . '; ' .
        ':local todayD [/system clock get date]; ' .
        ':local todayInt [$dint d=$todayD]; ' .
        ':local isolirProfile "' . $isolirProfile . '"; ' .
        ':foreach i in [/ppp secret find] do={' .
        ':local cm [/ppp secret get $i comment]; ' .
        ':if ([:len $cm] > 7 && [:pick $cm 0 7] = "ISOLIR|") do={' .
        ':local rest [:pick $cm 7 [:len $cm]]; ' .
        ':local p1 [:find $rest "|"]; ' .
        ':if ([:typeof $p1] = "num") do={' .
        ':local dueDate [:pick $rest 0 $p1]; ' .
        ':local dueInt [$dint d=$dueDate]; ' .
        ':local curProfile [/ppp secret get $i profile]; ' .
        ':if ($dueInt <= $todayInt && $curProfile != $isolirProfile) do={' .
        '/ppp secret set $i profile=$isolirProfile' .
        '}' .
        '}' .
        '}' .
        '}';
}

/**
 * Pastikan scheduler pengecek isolir ada di router dan memakai profile
 * isolir yang sedang berlaku. Dipanggil tiap kali pengaturan isolir
 * disimpan, atau tiap kali secret dengan due date disimpan (jaga-jaga
 * kalau admin belum sempat buka halaman pengaturan).
 */
function ros_ensure_ppp_isolir_scheduler($API, $isolirProfile)
{
    if ($isolirProfile == '') {
        return;
    }
    $name = ros_ppp_isolir_scheduler_name();
    $script = ros_build_ppp_isolir_checker($isolirProfile);
    $existing = $API->comm('/system/scheduler/print', array('?name' => $name));
    if (!empty($existing) && isset($existing[0]['.id'])) {
        $API->comm('/system/scheduler/set', array(
            '.id' => $existing[0]['.id'],
            'on-event' => $script,
            'interval' => '1h',
            'disabled' => 'no',
        ));
    } else {
        $API->comm('/system/scheduler/add', array(
            'name' => $name,
            'start-time' => 'startup',
            'interval' => '1h',
            'on-event' => $script,
            'disabled' => 'no',
            'comment' => 'Mikhmon: auto isolir PPPoE jatuh tempo',
        ));
    }
}

/**
 * Muat/simpan pengaturan isolir per session (nama profile tujuan isolir).
 * Tidak masuk include/config.php karena formatnya posisi-tetap dan rawan
 * rusak kalau ditambah field baru — dict JSON terpisah, sama pola dengan
 * include/olt.json.
 */
function ros_ppp_isolir_settings_load($file)
{
    if (!file_exists($file)) {
        return array();
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : array();
}

function ros_ppp_isolir_settings_save($file, $all)
{
    file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT));
}

/**
 * routeros_api.class.php->comm() mengembalikan array ber-key "!trap" kalau
 * router menolak command (permission, validasi field, dll) — tidak pernah
 * dicek di mana pun sebelumnya, jadi kegagalan seperti itu selama ini
 * senyap (redirect tetap jalan seolah berhasil). Kembalikan pesan errornya
 * kalau ada, null kalau command sukses.
 */
function ros_trap_message($result)
{
    if (!is_array($result) || !isset($result['!trap'][0]['message'])) {
        return null;
    }
    return $result['!trap'][0]['message'];
}
