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

  $getactive  = $API->comm("/ppp/active/print");
  $TotalReg   = count($getactive);

  // Ringkasan: berapa secret terdaftar vs berapa yang online.
  $getsecret  = $API->comm("/ppp/secret/print");
  $totalsecret = count($getsecret);
  $totaldis = 0;
  for ($d = 0; $d < $totalsecret; $d++) {
    if ($getsecret[$d]['disabled'] == "true") { $totaldis++; }
  }
  $totaloffline = $totalsecret - $totaldis - $TotalReg;
  if ($totaloffline < 0) { $totaloffline = 0; }
?>
<div class="row">
  <div class="col-3">
    <div class="box bmh-75 box-bordered">
      <div class="box-group">
        <div class="box-group-icon bg-success"><i class="fa fa-plug"></i></div>
        <div class="box-group-area">
          <span style="font-size:22px;font-weight:600;"><?= $TotalReg; ?></span><br>
          <span class="text-grey">Online</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-3">
    <div class="box bmh-75 box-bordered">
      <div class="box-group">
        <div class="box-group-icon bg-grey"><i class="fa fa-power-off"></i></div>
        <div class="box-group-area">
          <span style="font-size:22px;font-weight:600;"><?= $totaloffline; ?></span><br>
          <span class="text-grey">Offline</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-3">
    <div class="box bmh-75 box-bordered">
      <div class="box-group">
        <div class="box-group-icon bg-warning"><i class="fa fa-ban"></i></div>
        <div class="box-group-area">
          <span style="font-size:22px;font-weight:600;"><?= $totaldis; ?></span><br>
          <span class="text-grey">Disabled</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-3">
    <div class="box bmh-75 box-bordered">
      <div class="box-group">
        <div class="box-group-icon bg-primary"><i class="fa fa-users"></i></div>
        <div class="box-group-area">
          <span style="font-size:22px;font-weight:600;"><?= $totalsecret; ?></span><br>
          <span class="text-grey">Total <?= $_ppp_secrets ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card box-bordered">
      <div class="card-header">
        <h3><i class="fa fa-wifi"></i> <?= $_ppp_active ?> <small>(<?= $TotalReg; ?>)</small>
          <small id="loader" style="display:none;"><i><i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?></i></small>
        </h3>
      </div>
      <div class="card-body">
        <div>
          <a class="btn" href="./?ppp=active&session=<?= $session; ?>"><i class="fa fa-refresh"></i> Refresh</a>
          <a class="btn" href="./?ppp=secrets&session=<?= $session; ?>"><i class="fa fa-users"></i> <?= $_ppp_secrets ?></a>
        </div>
        <div class="overflow">
          <table class="table table-bordered table-hover text-nowrap">
            <thead>
              <tr>
                <th style="text-align:center;"><i class="fa fa-plug"></i></th>
                <th><?= $_name ?></th>
                <th>Service</th>
                <th>IP Address</th>
                <th>Caller ID</th>
                <th><?= $_uptime ?></th>
                <th>Encoding</th>
              </tr>
            </thead>
            <tbody>
<?php
  if ($TotalReg == 0) {
    echo "<tr><td colspan='7' class='text-center text-grey'>Belum ada koneksi PPPoE aktif.</td></tr>";
  }
  for ($i = 0; $i < $TotalReg; $i++) {
    $a       = $getactive[$i];
    $aid     = $a['.id'];
    $aname   = $a['name'];
    $asrv    = $a['service'];
    $aaddr   = $a['address'];
    $acaller = $a['caller-id'];
    $auptm   = $a['uptime'];
    $aenc    = $a['encoding'];
?>
              <tr>
                <td style="text-align:center;">
                  <i class="fa fa-minus-square text-danger pointer"
                     onclick="if(confirm('Disconnect (<?= $aname; ?>)?')){loadpage('./?remove-pactive=<?= $aid; ?>&session=<?= $session; ?>');loader();}else{}"
                     title="Disconnect <?= $aname; ?>"></i>
                </td>
                <td><a title="<?= $_edit ?> <?= $aname; ?>" href="./?secret=<?= $aname; ?>&session=<?= $session; ?>"><i class="fa fa-edit"></i> <?= $aname; ?></a></td>
                <td><?= $asrv; ?></td>
                <td><?= $aaddr; ?></td>
                <td><?= $acaller; ?></td>
                <td style="text-align:right;"><?= $auptm; ?></td>
                <td><?= $aenc; ?></td>
              </tr>
<?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>
