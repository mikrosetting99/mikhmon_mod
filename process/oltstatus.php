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
 * Cek status satu OLT lewat TCP connect ke host:port (80/443 web admin).
 * Bukan HTTP GET penuh — cukup buktikan port itu terbuka/menerima koneksi,
 * cepat dan tidak butuh library tambahan. Dipanggil per-OLT dari
 * settings/olt.php lewat fetch(), jadi beberapa OLT dicek paralel, bukan
 * berurutan.
 */

session_start();
error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION["mikhmon"])) {
  http_response_code(401);
  echo json_encode(array("ok" => false, "error" => "not_logged_in"));
  exit;
}

$host = isset($_GET['host']) ? $_GET['host'] : '';
$port = isset($_GET['port']) ? (int) $_GET['port'] : 80;

if ($host == '' || $port < 1 || $port > 65535) {
  echo json_encode(array("ok" => false, "error" => "invalid_target"));
  exit;
}

$start = microtime(true);
$conn = @fsockopen($host, $port, $errno, $errstr, 3);
$ms = (int) round((microtime(true) - $start) * 1000);

if ($conn) {
  fclose($conn);
  echo json_encode(array("ok" => true, "ms" => $ms));
} else {
  echo json_encode(array("ok" => false, "ms" => $ms, "error" => $errstr));
}
