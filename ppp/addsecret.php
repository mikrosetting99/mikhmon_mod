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

  if (isset($_POST['name'])) {
    $name       = (preg_replace('/\s+/', '', $_POST['name']));
    $password   = ($_POST['pass']);
    $service    = ($_POST['service']);
    $profile    = ($_POST['profile']);
    $localaddr  = ($_POST['localaddr']);
    $remoteaddr = ($_POST['remoteaddr']);
    $comment    = ($_POST['comment']);

    $API->comm("/ppp/secret/add", array(
      "name"           => "$name",
      "password"       => "$password",
      "service"        => "$service",
      "profile"        => "$profile",
      "local-address"  => "$localaddr",
      "remote-address" => "$remoteaddr",
      "comment"        => "$comment",
      "disabled"       => "no",
    ));

    echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
  }
?>
<script>
  function PassSecret() {
    var x = document.getElementById('passSecret');
    x.type = (x.type === 'password') ? 'text' : 'password';
  }
</script>
<div class="row">
  <div class="col-8">
    <div class="card box-bordered">
      <div class="card-header">
        <h3><i class="fa fa-user-plus"></i> <?= $_add ?> <?= $_ppp_secrets ?>
          <small id="loader" style="display:none;"><i><i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?></i></small>
        </h3>
      </div>
      <div class="card-body">
        <form autocomplete="off" method="post" action="">
          <div>
            <a class="btn bg-warning" href="./?ppp=secrets&session=<?= $session; ?>"><i class="fa fa-close"></i> <?= $_close ?></a>
            <button type="submit" onclick="loader()" class="btn bg-primary" name="save"><i class="fa fa-save"></i> <?= $_save ?></button>
          </div>
          <table class="table">
            <tr>
              <td class="align-middle"><?= $_name ?></td>
              <td><input class="form-control" type="text" name="name" value="" required="1" autofocus></td>
            </tr>
            <tr>
              <td class="align-middle"><?= $_password ?></td>
              <td>
                <div class="input-group">
                  <div class="input-group-11 col-box-10">
                    <input class="group-item group-item-l" id="passSecret" type="password" name="pass" autocomplete="new-password" value="" required="1">
                  </div>
                  <div class="input-group-1 col-box-2">
                    <div class="group-item group-item-r pd-2p5 text-center">
                      <input title="Show/Hide Password" type="checkbox" onclick="PassSecret()">
                    </div>
                  </div>
                </div>
              </td>
            </tr>
            <tr>
              <td class="align-middle">Service</td>
              <td>
                <select class="form-control" name="service">
                  <option value="pppoe">pppoe</option>
                  <option value="any">any</option>
                  <option value="pptp">pptp</option>
                  <option value="l2tp">l2tp</option>
                  <option value="sstp">sstp</option>
                  <option value="ovpn">ovpn</option>
                </select>
              </td>
            </tr>
            <tr>
              <td class="align-middle"><?= $_profile ?></td>
              <td>
                <select class="form-control" name="profile" required="1">
                  <?php $TotalReg = count($getpprofile);
                  for ($i = 0; $i < $TotalReg; $i++) {
                    echo "<option>" . $getpprofile[$i]['name'] . "</option>";
                  } ?>
                </select>
              </td>
            </tr>
            <tr>
              <td class="align-middle">Local Address</td>
              <td><input class="form-control" type="text" name="localaddr" value="" placeholder="Kosongkan untuk ikut profile"></td>
            </tr>
            <tr>
              <td class="align-middle">Remote Address</td>
              <td><input class="form-control" type="text" name="remoteaddr" value="" placeholder="Kosongkan untuk ambil dari pool"></td>
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
          <b>Service</b> pilih <code>pppoe</code> untuk pelanggan PPPoE biasa.
          <b>Profile</b> menentukan kecepatan dan IP; buat profile lebih dulu di
          menu Profil PPP.
        </p>
        <p style="padding:0px 5px;">
          <b>Local</b> dan <b>Remote Address</b> boleh dikosongkan — nilainya akan
          mengikuti profile. Isi <b>Remote Address</b> hanya bila pelanggan ini
          butuh IP tetap.
        </p>
      </div>
    </div>
  </div>
</div>
<?php } ?>
