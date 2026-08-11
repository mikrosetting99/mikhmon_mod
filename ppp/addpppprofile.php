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

  $getpool = $API->comm("/ip/pool/print");

  if (isset($_POST['name'])) {
    $name       = (preg_replace('/\s+/', '-', $_POST['name']));
    $localaddr  = ($_POST['localaddr']);
    $remoteaddr = ($_POST['remoteaddr']);
    $ratelimit  = ($_POST['ratelimit']);
    $dns        = ($_POST['dns']);
    $onlyone    = ($_POST['onlyone']);
    $comment    = ($_POST['comment']);

    $API->comm("/ppp/profile/add", array(
      "name"           => "$name",
      "local-address"  => "$localaddr",
      "remote-address" => "$remoteaddr",
      "rate-limit"     => "$ratelimit",
      "dns-server"     => "$dns",
      "only-one"       => "$onlyone",
      "comment"        => "$comment",
    ));

    echo "<script>window.location='./?ppp=profiles&session=" . $session . "'</script>";
  }
?>
<div class="row">
  <div class="col-8">
    <div class="card box-bordered">
      <div class="card-header">
        <h3><i class="fa fa-plus"></i> <?= $_add ?> <?= $_ppp_profiles ?>
          <small id="loader" style="display:none;"><i><i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?></i></small>
        </h3>
      </div>
      <div class="card-body">
        <form autocomplete="off" method="post" action="">
          <div>
            <a class="btn bg-warning" href="./?ppp=profiles&session=<?= $session; ?>"><i class="fa fa-close"></i> <?= $_close ?></a>
            <button type="submit" onclick="loader()" class="btn bg-primary" name="save"><i class="fa fa-save"></i> <?= $_save ?></button>
          </div>
          <table class="table">
            <tr>
              <td class="align-middle"><?= $_name ?></td>
              <td><input class="form-control" type="text" name="name" value="" required="1" autofocus></td>
            </tr>
            <tr>
              <td class="align-middle">Local Address</td>
              <td><input class="form-control" type="text" name="localaddr" value="" placeholder="Contoh : 10.10.10.1"></td>
            </tr>
            <tr>
              <td class="align-middle">Remote Address</td>
              <td>
                <select class="form-control" name="remoteaddr">
                  <option value="">none</option>
                  <?php $TotalReg = count($getpool);
                  for ($i = 0; $i < $TotalReg; $i++) {
                    echo "<option>" . $getpool[$i]['name'] . "</option>";
                  } ?>
                </select>
              </td>
            </tr>
            <tr>
              <td class="align-middle">Rate Limit [rx/tx]</td>
              <td><input class="form-control" type="text" name="ratelimit" value="" placeholder="Contoh : 2M/2M"></td>
            </tr>
            <tr>
              <td class="align-middle">DNS Server</td>
              <td><input class="form-control" type="text" name="dns" value="" placeholder="Contoh : 8.8.8.8,1.1.1.1"></td>
            </tr>
            <tr>
              <td class="align-middle">Only One</td>
              <td>
                <select class="form-control" name="onlyone">
                  <option value="default">default</option>
                  <option value="yes">yes</option>
                  <option value="no">no</option>
                </select>
              </td>
            </tr>
            <tr>
              <td class="align-middle"><?= $_comment ?></td>
              <td><input class="form-control" type="text" name="comment" value=""></td>
            </tr>
          </table>
        </form>
      </div>
    </div>
  </div>
  <div class="col-4">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-book"></i> <?= $_readme ?></h3>
      </div>
      <div class="card-body">
        <p style="padding:0px 5px;">
          <b>Local Address</b> adalah IP gateway di sisi router untuk klien PPPoE.
          <b>Remote Address</b> memakai IP Pool; buat pool lebih dulu di RouterOS
          bila daftarnya masih kosong.
        </p>
        <p style="padding:0px 5px;">
          <b>Rate Limit</b> memakai format <code>rx/tx</code> dilihat dari sisi klien,
          misal <code>2M/2M</code>. <b>Only One</b> = <code>yes</code> mencegah satu
          user dipakai login di dua tempat sekaligus.
        </p>
      </div>
    </div>
  </div>
</div>
<?php } ?>
