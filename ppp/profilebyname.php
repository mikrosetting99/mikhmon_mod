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

  $pprofile = $_GET['pprofile'];

  $getpool = $API->comm("/ip/pool/print");

  if (isset($_POST['name'])) {
    $name       = (preg_replace('/\s+/', '-', $_POST['name']));
    $localaddr  = ($_POST['localaddr']);
    $remoteaddr = ($_POST['remoteaddr']);
    $ratelimit  = ($_POST['ratelimit']);
    $dns        = ($_POST['dns']);
    $onlyone    = ($_POST['onlyone']);
    $comment    = ($_POST['comment']);

    $API->comm("/ppp/profile/set", array(
      ".id"            => "$pprofile",
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

  $getprofile = $API->comm("/ppp/profile/print", array(
    "?.id" => "$pprofile",
  ));
  $p        = $getprofile[0];
  $pname    = $p['name'];
  $plocal   = $p['local-address'];
  $premote  = $p['remote-address'];
  $prate    = $p['rate-limit'];
  $pdns     = $p['dns-server'];
  $ponlyone = $p['only-one'];
  $pcomment = $p['comment'];

  if ($pname == "") {
    echo "<b>PPP Profile not found, redirect to profile list...</b>";
    echo "<script>window.location='./?ppp=profiles&session=" . $session . "'</script>";
  }
  // Profile bawaan RouterOS tidak boleh diganti nama.
  $lockname = ($pname == "default" || $pname == "default-encryption") ? "readonly" : "";
?>
<div class="row">
  <div class="col-8">
    <div class="card box-bordered">
      <div class="card-header">
        <h3><i class="fa fa-edit"></i> <?= $_edit ?> <?= $_ppp_profiles ?> : <?= $pname; ?>
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
              <td><input class="form-control" type="text" name="name" value="<?= $pname; ?>" <?= $lockname; ?> required="1"></td>
            </tr>
            <tr>
              <td class="align-middle">Local Address</td>
              <td><input class="form-control" type="text" name="localaddr" value="<?= $plocal; ?>" placeholder="Contoh : 10.10.10.1"></td>
            </tr>
            <tr>
              <td class="align-middle">Remote Address</td>
              <td>
                <select class="form-control" name="remoteaddr">
                  <option value="<?= $premote; ?>"><?= ($premote == "") ? "none" : $premote; ?></option>
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
              <td><input class="form-control" type="text" name="ratelimit" value="<?= $prate; ?>" placeholder="Contoh : 2M/2M"></td>
            </tr>
            <tr>
              <td class="align-middle">DNS Server</td>
              <td><input class="form-control" type="text" name="dns" value="<?= $pdns; ?>" placeholder="Contoh : 8.8.8.8,1.1.1.1"></td>
            </tr>
            <tr>
              <td class="align-middle">Only One</td>
              <td>
                <select class="form-control" name="onlyone">
                  <option value="<?= $ponlyone; ?>"><?= $ponlyone; ?></option>
                  <option value="default">default</option>
                  <option value="yes">yes</option>
                  <option value="no">no</option>
                </select>
              </td>
            </tr>
            <tr>
              <td class="align-middle"><?= $_comment ?></td>
              <td><input class="form-control" type="text" name="comment" value="<?= $pcomment; ?>"></td>
            </tr>
          </table>
        </form>
      </div>
    </div>
  </div>
</div>
<?php } ?>
