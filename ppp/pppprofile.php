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

  $getpprofile = $API->comm("/ppp/profile/print");
  $TotalReg = count($getpprofile);
?>
<div class="row">
  <div class="col-12">
    <div class="card box-bordered">
      <div class="card-header">
        <h3><i class="fa fa-pie-chart"></i> <?= $_ppp_profiles ?>
          <small id="loader" style="display:none;"><i><i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?></i></small>
        </h3>
      </div>
      <div class="card-body">
        <div>
          <a class="btn bg-primary" href="./?ppp=add-profile&session=<?= $session; ?>"><i class="fa fa-plus-square"></i> <?= $_add ?></a>
          <a class="btn" href="./?ppp=profiles&session=<?= $session; ?>"><i class="fa fa-refresh"></i></a>
        </div>
        <div class="overflow">
          <table class="table table-bordered table-hover text-nowrap">
            <thead>
              <tr>
                <th style="text-align:center;"><i class="fa fa-trash"></i></th>
                <th><?= $_name ?></th>
                <th>Local Address</th>
                <th>Remote Address</th>
                <th>Rate Limit</th>
                <th>Only One</th>
                <th>DNS Server</th>
                <th><?= $_comment ?></th>
              </tr>
            </thead>
            <tbody>
<?php
  for ($i = 0; $i < $TotalReg; $i++) {
    $p       = $getpprofile[$i];
    $pid     = $p['.id'];
    $pname   = $p['name'];
    $plocal  = $p['local-address'];
    $premote = $p['remote-address'];
    $prate   = $p['rate-limit'];
    $ponly   = $p['only-one'];
    $pdns    = $p['dns-server'];
    $pcomm   = $p['comment'];

    echo "<tr>";
    // Profile bawaan RouterOS (default, default-encryption) tidak boleh dihapus.
    if ($pname == "default" || $pname == "default-encryption") {
      echo "<td style='text-align:center;'><i class='fa fa-lock text-grey-dark' title='Profile bawaan RouterOS'></i></td>";
    } else {
      ?>
      <td style="text-align:center;">
        <i class="fa fa-minus-square text-danger pointer"
           onclick="if(confirm('Are you sure to delete profile (<?= $pname; ?>)?')){loadpage('./?remove-pprofile=<?= $pid; ?>&session=<?= $session; ?>');loader();}else{}"
           title="<?= $_remove ?> <?= $pname; ?>"></i>
      </td>
      <?php
    }
    echo "<td><a title='" . $_edit . " " . $pname . "' href='./?ppp=edit-profile&pprofile=" . $pid . "&session=" . $session . "'><i class='fa fa-edit'></i> " . $pname . "</a></td>";
    echo "<td>" . $plocal . "</td>";
    echo "<td>" . $premote . "</td>";
    echo "<td>" . $prate . "</td>";
    echo "<td>" . $ponly . "</td>";
    echo "<td>" . $pdns . "</td>";
    echo "<td>" . $pcomm . "</td>";
    echo "</tr>";
  }
?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>
