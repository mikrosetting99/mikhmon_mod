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

if ($removesecr != "") {

    // Putuskan dulu koneksi aktifnya, kalau tidak sesi lama tetap jalan
    // sampai router memutus sendiri.
    $getname = $API->comm("/ppp/secret/print", array("?.id" => "$removesecr"));
    $sname   = $getname[0]['name'];
    if ($sname != "") {
        $getactive = $API->comm("/ppp/active/print", array("?name" => "$sname"));
        if ($getactive[0]['.id'] != "") {
            $API->comm("/ppp/active/remove", array(".id" => $getactive[0]['.id']));
        }
    }

    $API->comm("/ppp/secret/remove", array(
        ".id" => "$removesecr",
    ));

} elseif ($enablesecr != "") {

    $API->comm("/ppp/secret/enable", array(
        ".id" => "$enablesecr",
    ));

} elseif ($disablesecr != "") {

    $API->comm("/ppp/secret/disable", array(
        ".id" => "$disablesecr",
    ));

    // Secret yang di-disable tetap online sampai koneksinya diputus.
    $getname = $API->comm("/ppp/secret/print", array("?.id" => "$disablesecr"));
    $sname   = $getname[0]['name'];
    if ($sname != "") {
        $getactive = $API->comm("/ppp/active/print", array("?name" => "$sname"));
        if ($getactive[0]['.id'] != "") {
            $API->comm("/ppp/active/remove", array(".id" => $getactive[0]['.id']));
        }
    }
}

echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
