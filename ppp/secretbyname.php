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
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  $getpprofile = $API->comm("/ppp/profile/print");

  $isolirFile = __DIR__ . '/../include/pppisolir.json';
  $isolirAll = ros_ppp_isolir_settings_load($isolirFile);
  $isolirProfile = isset($isolirAll[$session]['profile']) ? $isolirAll[$session]['profile'] : '';

  // $secretbyname boleh berupa .id (*1) atau nama secret.
  if (substr($secretbyname, 0, 1) != "*") {
    $findsecret   = $API->comm("/ppp/secret/print", array("?name" => "$secretbyname"));
    $secretbyname = $findsecret[0]['.id'];
  }

  if (isset($_POST['name'])) {
    $name       = (preg_replace('/\s+/', '', $_POST['name']));
    $password   = ($_POST['pass']);
    $service    = ($_POST['service']);
    $profile    = ($_POST['profile']);
    $localaddr  = ($_POST['localaddr']);
    $remoteaddr = ($_POST['remoteaddr']);
    $comment    = ($_POST['comment']);
    $disabled   = ($_POST['disabled']);
    $dueDate    = ($_POST['due_date']);

    // Profile yang disimpan di tag isolir adalah "profile asal" (tujuan
    // pemulihan saat tanggal jatuh tempo diperpanjang) — kalau staff
    // menyimpan while secret ini SEDANG di profile isolir, jangan catat
    // profile isolir itu sendiri sebagai profile asal, pertahankan yang lama.
    $origProfileForTag = $profile;
    if ($isolirProfile != '' && $profile === $isolirProfile) {
      $oldTag = ros_ppp_parse_isolir($_POST['old_comment_raw']);
      if ($oldTag !== null && $oldTag['orig_profile'] != '') {
        $origProfileForTag = $oldTag['orig_profile'];
      }
    }

    $finalComment = ros_ppp_compose_isolir($dueDate, $origProfileForTag, $comment);

    // Sama seperti di addsecret.php: kirim local-address/remote-address
    // hanya kalau diisi. RouterOS menolak string kosong sebagai nilai
    // address ("invalid value for argument address"). Konsekuensinya field
    // yang sudah pernah diisi tidak bisa dikosongkan lagi lewat form ini
    // (tetap dipertahankan nilainya di router) — itu masih lebih baik
    // daripada seluruh proses Simpan gagal total seperti sebelumnya.
    $setArgs = array(
      ".id"      => "$secretbyname",
      "name"     => "$name",
      "password" => "$password",
      "service"  => "$service",
      "profile"  => "$profile",
      "comment"  => "$finalComment",
      "disabled" => "$disabled",
    );
    if ($localaddr != '') {
      $setArgs["local-address"] = "$localaddr";
    }
    if ($remoteaddr != '') {
      $setArgs["remote-address"] = "$remoteaddr";
    }

    $setResult = $API->comm("/ppp/secret/set", $setArgs);
    $setError = ros_trap_message($setResult);

    if ($setError === null) {
      if ($dueDate != '') {
        ros_ensure_ppp_isolir_scheduler($API, $isolirProfile);
      }
      echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
    }
  }

  $getsecret = $API->comm("/ppp/secret/print", array("?.id" => "$secretbyname"));
  $s        = $getsecret[0];
  $sname    = $s['name'];
  $spass    = $s['password'];
  $ssrv     = $s['service'];
  $sprof    = $s['profile'];
  $slocal   = $s['local-address'];
  $sremote  = $s['remote-address'];
  $scommentRaw = $s['comment'];
  $sdis     = $s['disabled'];

  $isolirTag = ros_ppp_parse_isolir($scommentRaw);
  $sdueDate  = $isolirTag !== null ? $isolirTag['due'] : '';
  $scomment  = $isolirTag !== null ? $isolirTag['rest'] : $scommentRaw;
  $sIsIsolated = ($isolirProfile != '' && $sprof === $isolirProfile);

  if ($sname == "") {
    echo "<b>PPP Secret not found, redirect to secret list...</b>";
    echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
  }

  // Status koneksi saat ini + tombol putus.
  $getactive = $API->comm("/ppp/active/print", array("?name" => "$sname"));
  $aid   = $getactive[0]['.id'];
  $aaddr = $getactive[0]['address'];
  $auptm = $getactive[0]['uptime'];
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
        <h3><i class="fa fa-edit"></i> <?= $_edit ?> <?= $_ppp_secrets ?> : <?= $sname; ?>
          <small id="loader" style="display:none;"><i><i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?></i></small>
        </h3>
      </div>
      <div class="card-body">
<?php if (!empty($setError)) { ?>
        <div class="bg-danger" style="padding:8px 10px; border-radius:5px; margin-bottom:10px;">
          <i class="fa fa-ban"></i> Gagal menyimpan — router menolak: <b><?= htmlspecialchars($setError); ?></b>
        </div>
<?php } ?>
        <form autocomplete="off" method="post" action="">
          <input type="hidden" name="old_comment_raw" value="<?= htmlspecialchars($scommentRaw); ?>">
          <div>
            <a class="btn bg-warning" href="./?ppp=secrets&session=<?= $session; ?>"><i class="fa fa-close"></i> <?= $_close ?></a>
            <button type="submit" onclick="loader()" class="btn bg-primary" name="save"><i class="fa fa-save"></i> <?= $_save ?></button>
<?php if ($aid != "") { ?>
            <a class="btn bg-danger" href="javascript:void(0)"
               onclick="if(confirm('Disconnect <?= $sname; ?> now?')){loadpage('./?remove-pactive=<?= $aid; ?>&session=<?= $session; ?>');loader();}else{}"><i class="fa fa-plug"></i> Disconnect</a>
<?php } ?>
          </div>
          <table class="table">
            <tr>
              <td class="align-middle"><?= $_name ?></td>
              <td><input class="form-control" type="text" name="name" value="<?= $sname; ?>" required="1"></td>
            </tr>
            <tr>
              <td class="align-middle"><?= $_password ?></td>
              <td>
                <div class="input-group">
                  <div class="input-group-11 col-box-10">
                    <input class="group-item group-item-l" id="passSecret" type="password" name="pass" autocomplete="new-password" value="<?= $spass; ?>" required="1">
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
                  <option value="<?= $ssrv; ?>"><?= $ssrv; ?></option>
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
                  <option value="<?= $sprof; ?>"><?= $sprof; ?></option>
                  <?php $TotalReg = count($getpprofile);
                  for ($i = 0; $i < $TotalReg; $i++) {
                    echo "<option>" . $getpprofile[$i]['name'] . "</option>";
                  } ?>
                </select>
              </td>
            </tr>
            <tr>
              <td class="align-middle">Local Address</td>
              <td><input class="form-control" type="text" name="localaddr" value="<?= $slocal; ?>"></td>
            </tr>
            <tr>
              <td class="align-middle">Remote Address</td>
              <td><input class="form-control" type="text" name="remoteaddr" value="<?= $sremote; ?>"></td>
            </tr>
            <tr>
              <td class="align-middle">Disabled</td>
              <td>
                <select class="form-control" name="disabled">
                  <option value="<?= $sdis; ?>"><?= $sdis; ?></option>
                  <option value="no">no</option>
                  <option value="yes">yes</option>
                </select>
              </td>
            </tr>
            <tr>
              <td class="align-middle"><?= $_comment ?></td>
              <td><input class="form-control" type="text" name="comment" value="<?= $scomment; ?>"></td>
            </tr>
            <tr>
              <td class="align-middle">Tanggal Jatuh Tempo</td>
              <td>
                <input class="form-control" type="date" name="due_date" value="<?= $sdueDate; ?>">
                <?php if ($sIsIsolated) { ?>
                  <small class="text-danger"><i class="fa fa-ban"></i> Sedang diisolir (profile: <?= $isolirProfile; ?>). Ubah Profile di atas &amp; Tanggal Jatuh Tempo ke tanggal baru, lalu Simpan untuk memulihkan.</small>
                <?php } ?>
              </td>
            </tr>
          </table>
        </form>
      </div>
    </div>
  </div>
  <div class="col-4">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-wifi"></i> Status</h3>
      </div>
      <div class="card-body">
        <table class="table">
          <tr>
            <td class="align-middle">Koneksi</td>
            <td>
<?php if ($aid != "") {
        echo "<span class='text-success'><i class='fa fa-circle'></i> online</span>";
      } else {
        echo "<span class='text-grey'><i class='fa fa-circle-o'></i> offline</span>";
      } ?>
            </td>
          </tr>
          <tr><td class="align-middle">IP</td><td><?= ($aaddr == "") ? "-" : $aaddr; ?></td></tr>
          <tr><td class="align-middle"><?= $_uptime ?></td><td><?= ($auptm == "") ? "-" : $auptm; ?></td></tr>
          <tr>
            <td class="align-middle">Isolir</td>
            <td>
<?php if ($sdueDate == '') {
        echo "<span class='text-grey'>tidak dipakai</span>";
      } elseif ($sIsIsolated) {
        echo "<span class='text-danger'><i class='fa fa-ban'></i> diisolir (jatuh tempo " . $sdueDate . ")</span>";
      } else {
        echo "<span class='text-success'><i class='fa fa-check-circle'></i> aktif s/d " . $sdueDate . "</span>";
      } ?>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
<?php } ?>
