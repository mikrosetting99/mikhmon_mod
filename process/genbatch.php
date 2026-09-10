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

/*
 * Endpoint AJAX untuk generate voucher per-batch.
 *
 * hotspot/generateuser.php sebelumnya menambahkan seluruh qty (bisa sampai 500)
 * ke router dalam satu request PHP yang sinkron, jadi UI cuma bisa menampilkan
 * spinner tanpa progres nyata. Endpoint ini memecah proses jadi dua langkah:
 *
 *  - step=init  : generate daftar username/password (cepat, tanpa panggilan API
 *                 router), simpan antrian ke session, tulis voucher/temp.php
 *                 seperti alur lama, lalu kembalikan total qty.
 *  - step=chunk : proses beberapa user dari antrian (panggil /ip/hotspot/user/add
 *                 ke router), lalu kembalikan progres done/total supaya JS bisa
 *                 menghitung persentase dan mengisi progress bar.
 */

session_start();
error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION["mikhmon"])) {
  http_response_code(401);
  echo json_encode(array("error" => "not_logged_in"));
  exit;
}

$session = $_POST['session'];
$step = $_POST['step'];

if ($session == "" || !isset($_SESSION[$session])) {
  echo json_encode(array("error" => "invalid_session"));
  exit;
}

include('../include/config.php');
include('../include/readcfg.php');
include_once('../lib/routeros_api.class.php');
include_once('../lib/formatbytesbites.php');

$API = new RouterosAPI();
$API->debug = false;
$API->connect($iphost, $userhost, decrypt($passwdhost));

$qkey = $session . 'genqueue';
$mkey = $session . 'genmeta';
$ikey = $session . 'genindex';

if ($step == 'init') {
  $qty = (int) $_POST['qty'];
  if ($qty < 1) { $qty = 1; }
  if ($qty > 500) { $qty = 500; }
  $server = $_POST['server'];
  $user = $_POST['user'];
  $userl = (int) $_POST['userl'];
  $prefix = $_POST['prefix'];
  $char = $_POST['char'];
  $profile = $_POST['profile'];
  $timelimit = $_POST['timelimit'];
  $datalimit = $_POST['datalimit'];
  $adcomment = $_POST['adcomment'];
  $mbgb = $_POST['mbgb'];

  $timelimit = ($timelimit == "") ? "0" : $timelimit;
  $datalimit = ($datalimit == "") ? "0" : ($datalimit * $mbgb);

  $getprofile = $API->comm("/ip/hotspot/user/profile/print", array("?name" => "$profile"));
  $ponlogin = $getprofile[0]['on-login'];
  $getvalid = explode(",", $ponlogin)[3];
  $getprice = explode(",", $ponlogin)[2];
  $getsprice = explode(",", $ponlogin)[4];
  $getlock = explode(",", $ponlogin)[6];
  $_SESSION['ubp'] = $profile;

  $commt = $user . "-" . rand(100, 999) . "-" . date("m.d.y") . "-" . $adcomment;
  $gentemp = $commt . "|~" . $profile . "~" . $getvalid . "~" . $getprice . "!" . $getsprice . "~" . $timelimit . "~" . $datalimit . "~" . $getlock;
  $gen = '<?php $genu="' . encrypt($gentemp) . '";?>';
  $temp = '../voucher/temp.php';
  $handle = fopen($temp, 'w') or die(json_encode(array("error" => "temp_write_failed")));
  fwrite($handle, $gen);
  fclose($handle);

  $a = array("1" => "", "", 1, 2, 2, 3, 3, 4);
  $queue = array();

  if ($user == "up") {
    for ($i = 1; $i <= $qty; $i++) {
      if ($char == "lower") {
        $uu = randLC($userl);
      } elseif ($char == "upper") {
        $uu = randUC($userl);
      } elseif ($char == "upplow") {
        $uu = randULC($userl);
      } elseif ($char == "mix") {
        $uu = randNLC($userl);
      } elseif ($char == "mix1") {
        $uu = randNUC($userl);
      } elseif ($char == "mix2") {
        $uu = randNULC($userl);
      } else {
        $uu = "";
      }

      if ($userl == 3) {
        $pp = randN(3);
      } elseif ($userl == 4) {
        $pp = randN(4);
      } elseif ($userl == 5) {
        $pp = randN(5);
      } elseif ($userl == 6) {
        $pp = randN(6);
      } elseif ($userl == 7) {
        $pp = randN(7);
      } elseif ($userl == 8) {
        $pp = randN(8);
      } else {
        $pp = "";
      }

      $queue[] = array("name" => "$prefix$uu", "pass" => $pp);
    }
  } elseif ($user == "vc") {
    $shuf = ($userl - $a[$userl]);
    for ($i = 1; $i <= $qty; $i++) {
      $uname = "";

      if ($char == "lower" || $char == "upper" || $char == "upplow") {
        if ($char == "lower") {
          $uu = randLC($shuf);
        } elseif ($char == "upper") {
          $uu = randUC($shuf);
        } else {
          $uu = randULC($shuf);
        }

        if ($userl == 3) {
          $pp = randN(1);
        } elseif ($userl == 4 || $userl == 5) {
          $pp = randN(2);
        } elseif ($userl == 6 || $userl == 7) {
          $pp = randN(3);
        } elseif ($userl == 8) {
          $pp = randN(4);
        } else {
          $pp = "";
        }

        $uname = "$prefix$uu$pp";
      } elseif ($char == "num") {
        if ($userl == 3) {
          $pp = randN(3);
        } elseif ($userl == 4) {
          $pp = randN(4);
        } elseif ($userl == 5) {
          $pp = randN(5);
        } elseif ($userl == 6) {
          $pp = randN(6);
        } elseif ($userl == 7) {
          $pp = randN(7);
        } elseif ($userl == 8) {
          $pp = randN(8);
        } else {
          $pp = "";
        }
        $uname = "$prefix$pp";
      } elseif ($char == "mix") {
        $uname = "$prefix" . randNLC($userl);
      } elseif ($char == "mix1") {
        $uname = "$prefix" . randNUC($userl);
      } elseif ($char == "mix2") {
        $uname = "$prefix" . randNULC($userl);
      }

      $queue[] = array("name" => $uname, "pass" => $uname);
    }
  }

  $_SESSION[$qkey] = $queue;
  $_SESSION[$mkey] = array(
    "server" => $server,
    "profile" => $profile,
    "timelimit" => $timelimit,
    "datalimit" => $datalimit,
    "commt" => $commt,
    "qty" => count($queue),
  );
  $_SESSION[$ikey] = 0;

  echo json_encode(array("total" => count($queue)));
  exit;
}

if ($step == 'chunk') {
  $queue = isset($_SESSION[$qkey]) ? $_SESSION[$qkey] : array();
  $meta = isset($_SESSION[$mkey]) ? $_SESSION[$mkey] : null;
  $index = isset($_SESSION[$ikey]) ? $_SESSION[$ikey] : 0;

  if ($meta === null || count($queue) == 0) {
    echo json_encode(array("error" => "no_batch"));
    exit;
  }

  $total = $meta['qty'];
  $batchSize = 10;
  $end = min($index + $batchSize, $total);

  for ($i = $index; $i < $end; $i++) {
    $u = $queue[$i];
    $API->comm("/ip/hotspot/user/add", array(
      "server" => $meta['server'],
      "name" => $u['name'],
      "password" => $u['pass'],
      "profile" => $meta['profile'],
      "limit-uptime" => $meta['timelimit'],
      "limit-bytes-total" => $meta['datalimit'],
      "comment" => $meta['commt'],
    ));
  }

  $_SESSION[$ikey] = $end;
  $finished = ($end >= $total);

  $result = array("done" => $end, "total" => $total, "finished" => $finished);

  if ($finished) {
    if ($total < 2) {
      $result["redirect"] = "./?hotspot-user=" . $queue[0]['name'] . "&session=" . $session;
    } else {
      $result["redirect"] = "./?hotspot-user=generate&session=" . $session;
    }
    unset($_SESSION[$qkey]);
    unset($_SESSION[$mkey]);
    unset($_SESSION[$ikey]);
  }

  echo json_encode($result);
  exit;
}

echo json_encode(array("error" => "invalid_step"));
