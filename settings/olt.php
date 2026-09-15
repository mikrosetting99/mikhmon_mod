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
 * Monitoring OLT — terikat ke masing-masing router session (OLT beda-beda
 * per lokasi/router). Disimpan di include/olt.json sebagai dict keyed by
 * nama session: { "<session>": [ {id, name, host, port, protocol}, ... ] }.
 * Status UP/DOWN dicek lewat TCP connect ke host:port (biasanya 80/443, web
 * admin OLT) via process/oltstatus.php.
 */

// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} elseif (empty($session)) {
  echo "<script>window.location='./admin.php?id=sessions'</script>";
} else {

  $oltFile = __DIR__ . '/../include/olt.json';

  function mikhmon_olt_load_all($file)
  {
    if (!file_exists($file)) {
      return array();
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : array();
  }

  function mikhmon_olt_save_all($file, $all)
  {
    file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT));
  }

  function mikhmon_olt_url($olt)
  {
    $defaultPort = ($olt['protocol'] == 'https') ? 443 : 80;
    $portPart = ((int) $olt['port'] === $defaultPort) ? '' : (':' . $olt['port']);
    return $olt['protocol'] . '://' . $olt['host'] . $portPart;
  }

  $color = array('1' => 'bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-yellow', 'bg-green', 'bg-teal', 'bg-cyan', 'bg-grey', 'bg-light-blue');

  $oltAll = mikhmon_olt_load_all($oltFile);
  $oltList = isset($oltAll[$session]) && is_array($oltAll[$session]) ? $oltAll[$session] : array();

  if (isset($_POST['save_olt'])) {
    $name = trim($_POST['olt_name']);
    $host = trim($_POST['olt_host']);
    $port = (int) $_POST['olt_port'];
    $protocol = ($_POST['olt_protocol'] == 'https') ? 'https' : 'http';
    $postId = $_POST['olt_id'];

    if ($port < 1 || $port > 65535) {
      $port = 80;
    }

    $found = false;
    foreach ($oltList as &$o) {
      if ($postId != '' && $o['id'] == $postId) {
        $o['name'] = $name;
        $o['host'] = $host;
        $o['port'] = $port;
        $o['protocol'] = $protocol;
        $found = true;
        break;
      }
    }
    unset($o);
    if (!$found) {
      $oltList[] = array(
        'id' => uniqid('olt_'),
        'name' => $name,
        'host' => $host,
        'port' => $port,
        'protocol' => $protocol,
      );
    }
    $oltAll[$session] = $oltList;
    mikhmon_olt_save_all($oltFile, $oltAll);
    echo "<script>window.location='./admin.php?id=olt&session=" . $session . "'</script>";
  }

  if (isset($_GET['remove_olt']) && $_GET['remove_olt'] != '') {
    $rid = $_GET['remove_olt'];
    $oltList = array_values(array_filter($oltList, function ($o) use ($rid) {
      return $o['id'] != $rid;
    }));
    $oltAll[$session] = $oltList;
    mikhmon_olt_save_all($oltFile, $oltAll);
    echo "<script>window.location='./admin.php?id=olt&session=" . $session . "'</script>";
  }

  $editOlt = array('id' => '', 'name' => '', 'host' => '', 'port' => 80, 'protocol' => 'http');
  $editId = isset($_GET['edit']) ? $_GET['edit'] : '';
  if ($editId != '') {
    foreach ($oltList as $o) {
      if ($o['id'] == $editId) {
        $editOlt = $o;
        break;
      }
    }
  }
?>
<div class="row">
  <div class="col-12">
    <h3 class="mr-b-10"><i class="fa fa-share-alt"></i> OLT Monitoring &mdash; <?= htmlspecialchars($session); ?> &nbsp; | &nbsp;&nbsp;<i onclick="location.reload();" class="fa fa-refresh pointer" title="Reload data"></i></h3>
  </div>
</div>
<div class="row">
  <div class="col-6">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="fa fa-share-alt"></i> Daftar OLT <small>(<?= count($oltList); ?>)</small></h3>
      </div>
      <div class="card-body">
        <div class="row">
<?php
  if (count($oltList) == 0) {
?>
          <div class="col-12">
            <p class="text-center text-grey">Belum ada OLT terdaftar untuk router <b><?= htmlspecialchars($session); ?></b>. Tambahkan lewat form di sebelah kanan.</p>
          </div>
<?php
  }
  foreach ($oltList as $i => $olt) {
    $url = mikhmon_olt_url($olt);
?>
          <div class="col-12">
            <div class="box bmh-75 box-bordered box-tile-muted <?= $color[($i % 11) + 1]; ?>">
              <div class="box-group">
                <div class="box-group-icon">
                  <i class="fa fa-share-alt"></i>
                </div>
                <div class="box-group-area">
                  <span>
                    <b><?= htmlspecialchars($olt['name']); ?></b><br>
                    <?= htmlspecialchars($olt['host']); ?>:<?= (int) $olt['port']; ?> (<?= htmlspecialchars($olt['protocol']); ?>)<br>
                    <span id="olt-status-<?= $olt['id']; ?>" class="text-grey"><i class="fa fa-circle-o-notch fa-spin"></i> Mengecek...</span>
                    &nbsp;
                    <a href="<?= htmlspecialchars($url); ?>" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> Buka</a>&nbsp;
                    <a href="./admin.php?id=olt&session=<?= $session; ?>&edit=<?= $olt['id']; ?>"><i class="fa fa-edit"></i> Edit</a>&nbsp;
                    <a href="javascript:void(0)" onclick="if(confirm('Hapus OLT <?= htmlspecialchars($olt['name']); ?>?')){loadpage('./admin.php?id=olt&session=<?= $session; ?>&remove_olt=<?= $olt['id']; ?>')}else{}"><i class="fa fa-remove"></i> Hapus</a>
                  </span>
                </div>
              </div>
            </div>
          </div>
<?php
  }
?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6">
    <form autocomplete="off" method="post" action="">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fa fa-plus-square"></i> <?= $editOlt['id'] != '' ? 'Edit OLT' : 'Tambah OLT'; ?></h3>
        </div>
        <div class="card-body">
          <input type="hidden" name="olt_id" value="<?= htmlspecialchars($editOlt['id']); ?>">
          <table class="table table-sm">
            <tr>
              <td class="align-middle">Nama</td>
              <td><input class="form-control" type="text" name="olt_name" title="Nama OLT" placeholder="mis. OLT Gudang" value="<?= htmlspecialchars($editOlt['name']); ?>" required="1"/></td>
            </tr>
            <tr>
              <td class="align-middle">Host / IP</td>
              <td><input class="form-control" type="text" name="olt_host" title="IP atau domain web admin OLT" placeholder="mis. 192.168.1.2" value="<?= htmlspecialchars($editOlt['host']); ?>" required="1"/></td>
            </tr>
            <tr>
              <td class="align-middle">Port</td>
              <td><input class="form-control" type="number" min="1" max="65535" name="olt_port" title="Port web admin (biasanya 80 atau 443)" value="<?= (int) $editOlt['port']; ?>" required="1"/></td>
            </tr>
            <tr>
              <td class="align-middle">Protokol</td>
              <td>
                <select class="form-control" name="olt_protocol">
                  <option value="http" <?= $editOlt['protocol'] == 'http' ? 'selected' : ''; ?>>http</option>
                  <option value="https" <?= $editOlt['protocol'] == 'https' ? 'selected' : ''; ?>>https</option>
                </select>
              </td>
            </tr>
            <tr>
              <td></td>
              <td class="text-right">
                <?php if ($editOlt['id'] != '') { ?>
                <a class="btn" href="./admin.php?id=olt&session=<?= $session; ?>"><i class="fa fa-close"></i> Batal</a>
                <?php } ?>
                <button type="submit" name="save_olt" class="btn bg-primary"><i class="fa fa-save"></i> Simpan</button>
              </td>
            </tr>
          </table>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  var targets = <?= json_encode(array_map(function ($o) {
    return array('id' => $o['id'], 'host' => $o['host'], 'port' => (int) $o['port']);
  }, $oltList)); ?>;
  targets.forEach(function (t) {
    var el = document.getElementById('olt-status-' + t.id);
    if (!el) { return; }
    fetch('./process/oltstatus.php?host=' + encodeURIComponent(t.host) + '&port=' + encodeURIComponent(t.port) + '&session=<?= $session; ?>')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          el.innerHTML = '<i class="fa fa-circle text-success"></i> Online (' + data.ms + ' ms)';
          el.className = 'text-success';
        } else {
          el.innerHTML = '<i class="fa fa-circle-o text-grey"></i> Offline / tidak terjangkau';
          el.className = 'text-grey';
        }
      })
      .catch(function () {
        el.innerHTML = '<i class="fa fa-ban text-red"></i> Gagal cek status';
        el.className = 'text-red';
      });
  });
})();
</script>
<?php
}
?>
