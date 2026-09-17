<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
// hide all error
error_reporting(0);
require_once __DIR__ . '/../include/roscompat.php';

// Satu klik "Aktifkan": kembalikan profile ke profile asal (sebelum
// diisolir) dan perpanjang Tanggal Jatuh Tempo 30 hari dari hari ini.
if ($activatesecr != "") {
    $getsecret = $API->comm("/ppp/secret/print", array("?.id" => "$activatesecr"));
    $s = $getsecret[0];

    if (!empty($s)) {
        $tag         = ros_ppp_parse_isolir($s['comment']);
        $origProfile = ($tag !== null && $tag['orig_profile'] != '') ? $tag['orig_profile'] : $s['profile'];
        $rest        = ($tag !== null) ? $tag['rest'] : $s['comment'];
        $newDue      = date('Y-m-d', strtotime('+30 days'));
        $newComment  = ros_ppp_compose_isolir($newDue, $origProfile, $rest);

        $API->comm("/ppp/secret/set", array(
            ".id"     => "$activatesecr",
            "profile" => "$origProfile",
            "comment" => "$newComment",
        ));

        $isolirFile    = __DIR__ . '/../include/pppisolir.json';
        $isolirAll     = ros_ppp_isolir_settings_load($isolirFile);
        $isolirProfile = isset($isolirAll[$session]['profile']) ? $isolirAll[$session]['profile'] : '';
        if ($isolirProfile != '') {
            ros_ensure_ppp_isolir_scheduler($API, $isolirProfile);
        }
    }
}

echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
