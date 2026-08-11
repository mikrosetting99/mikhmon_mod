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
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  require_once __DIR__ . '/../include/pagination.php';

  $fprofile = $_GET['pprofile'];

  // Hanya kolom ini yang dirender; tanpa .proplist router ikut mengirim seluruh
  // properti secret (caller-id, routes, ipv6, limit-bytes, dan lain-lain).
  $sprops = ".id,name,password,service,profile,remote-address,comment,disabled";

  if ($fprofile != "" && $fprofile != "all") {
    $getsecret = $API->comm("/ppp/secret/print", array("?profile" => "$fprofile", ".proplist" => "$sprops"));
  } else {
    $getsecret = $API->comm("/ppp/secret/print", array(".proplist" => "$sprops"));
  }
  if (!is_array($getsecret)) {
    $getsecret = array();
  }

  // Pencarian di sisi PHP tapi mencakup seluruh baris, bukan hanya yang tampil.
  $cari = isset($_GET['cari']) ? $_GET['cari'] : "";
  if ($cari != "") {
    $getsecret = mikhmon_search($getsecret, $cari, array("name", "comment", "profile", "remote-address"));
  }

  $TotalReg = count($getsecret);
  $pg = mikhmon_paginate($TotalReg);

  // Daftar user yang sedang online dipakai untuk menandai baris.
  $getactive = $API->comm("/ppp/active/print");
  $online = array();
  for ($a = 0; $a < count($getactive); $a++) {
    $online[$getactive[$a]['name']] = $getactive[$a]['address'];
  }

  $getpprofile = $API->comm("/ppp/profile/print");
?>
<div class="row">
  <div class="col-12">
    <div class="card box-bordered">
      <div class="card-header">
        <h3><i class="fa fa-users"></i> <?= $_ppp_secrets ?> <small>(<?= $TotalReg; ?>)</small>
          <small id="loader" style="display:none;"><i><i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?></i></small>
        </h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-6">
            <a class="btn bg-primary" href="./?ppp=addsecret&session=<?= $session; ?>"><i class="fa fa-user-plus"></i> <?= $_add ?></a>
            <a class="btn" href="./?ppp=secrets&session=<?= $session; ?>"><i class="fa fa-refresh"></i></a>
          </div>
          <div class="col-6">
            <form method="get" action="./" style="margin:0 0 5px 0;">
              <input type="hidden" name="ppp" value="secrets">
              <input type="hidden" name="session" value="<?= $session; ?>">
              <?php if ($fprofile != "") { ?><input type="hidden" name="pprofile" value="<?= htmlspecialchars($fprofile); ?>"><?php } ?>
              <input class="form-control" name="cari" type="text" value="<?= htmlspecialchars($cari); ?>"
                     placeholder="<?= $_search ?> — tekan Enter">
            </form>
            <div class="input-group">
              <div class="input-group-9">
                <select class="group-item group-item-l" id="fprofile">
                  <option value="all">-- <?= $_profile ?> : <?= ($fprofile == "" || $fprofile == "all") ? "all" : $fprofile; ?> --</option>
                  <option value="all">all</option>
                  <?php for ($i = 0; $i < count($getpprofile); $i++) {
                    echo "<option>" . $getpprofile[$i]['name'] . "</option>";
                  } ?>
                </select>
              </div>
              <div class="input-group-3">
                <div class="group-item group-item-r text-center pointer" onclick="filterSecret();loader();"><i class="fa fa-search"></i> Filter</div>
              </div>
            </div>
          </div>
        </div>
        <div class="overflow">
          <table class="table table-bordered table-hover text-nowrap">
            <thead>
              <tr>
                <th style="text-align:center;"><i class="fa fa-trash"></i> / <i class="fa fa-lock"></i></th>
                <th><?= $_name ?></th>
                <th><?= $_password ?></th>
                <th>Service</th>
                <th><?= $_profile ?></th>
                <th>Remote Address</th>
                <th>Status</th>
                <th><?= $_comment ?></th>
              </tr>
            </thead>
            <tbody>
<?php
  if ($TotalReg == 0) {
    echo "<tr><td colspan='8' class='text-center text-grey'>"
       . ($cari != "" ? "Tidak ada hasil untuk \"" . htmlspecialchars($cari) . "\"." : "Belum ada user PPPoE.")
       . "</td></tr>";
  }
  for ($i = $pg['start']; $i < $pg['end']; $i++) {
    $s        = $getsecret[$i];
    $sid      = $s['.id'];
    $sname    = $s['name'];
    $spass    = $s['password'];
    $ssrv     = $s['service'];
    $sprof    = $s['profile'];
    $sremote  = $s['remote-address'];
    $scomment = $s['comment'];
    $sdis     = $s['disabled'];
?>
              <tr>
                <td style="text-align:center;">
                  <i class="fa fa-minus-square text-danger pointer"
                     onclick="if(confirm('Are you sure to delete secret (<?= $sname; ?>)?')){loadpage('./?remove-pppsecret=<?= $sid; ?>&session=<?= $session; ?>');loader();}else{}"
                     title="<?= $_remove ?> <?= $sname; ?>"></i>&nbsp;&nbsp;
<?php if ($sdis == "true") { ?>
                  <span class="text-warning pointer" title="Enable <?= $sname; ?>" onclick="loadpage('./?enable-pppsecret=<?= $sid; ?>&session=<?= $session; ?>');loader();"><i class="fa fa-lock"></i></span>
<?php } else { ?>
                  <span class="pointer" title="Disable <?= $sname; ?>" onclick="loadpage('./?disable-pppsecret=<?= $sid; ?>&session=<?= $session; ?>');loader();"><i class="fa fa-unlock"></i></span>
<?php } ?>
                </td>
                <td><a title="<?= $_edit ?> <?= $sname; ?>" href="./?secret=<?= $sid; ?>&session=<?= $session; ?>"><i class="fa fa-edit"></i> <?= $sname; ?></a></td>
                <td><?= $spass; ?></td>
                <td><?= $ssrv; ?></td>
                <td><a href="./?ppp=secrets&pprofile=<?= $sprof; ?>&session=<?= $session; ?>" title="Filter <?= $sprof; ?>"><?= $sprof; ?></a></td>
                <td><?= $sremote; ?></td>
                <td>
<?php
    if ($sdis == "true") {
      echo "<span class='text-grey-dark'><i class='fa fa-ban'></i> disabled</span>";
    } elseif (isset($online[$sname])) {
      echo "<span class='text-success'><i class='fa fa-circle'></i> online " . $online[$sname] . "</span>";
    } else {
      echo "<span class='text-grey'><i class='fa fa-circle-o'></i> offline</span>";
    }
?>
                </td>
                <td><?= $scomment; ?></td>
              </tr>
<?php } ?>
            </tbody>
          </table>
        </div>
        <?php mikhmon_pagination_nav($pg); ?>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  function filterSecret() {
    var p = document.getElementById('fprofile').value;
    loadpage('./?ppp=secrets&pprofile=' + p + '&session=<?= $session; ?>');
  }
</script>
<?php } ?>
