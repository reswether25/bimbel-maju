<?php require 'config.php'; require_login(); $u=user(); $title='Jadwal';
$elig="SELECT k.id kid,u.id sid,u.nama sn,k.nama kn FROM pendaftaran p JOIN tagihan t ON t.pendaftaran_id=p.id AND t.status='lunas' JOIN users u ON u.id=p.siswa_id JOIN kelas k ON k.kursus_id=p.kursus_id WHERE NOT EXISTS (SELECT 1 FROM kelas_siswa ks WHERE ks.kelas_id=k.id AND ks.siswa_id=u.id)";
if ($_SERVER['REQUEST_METHOD']==='POST' && $u['role']==='admin') { csrf_check(); $a=$_POST['aksi']??'';
  if ($a==='kelas') { $n=trim($_POST['nama']??'');
    if ($n===''||empty($_POST['kursus_id'])||empty($_POST['pengajar_id'])) flash('Lengkapi data kelas.');
    else { $pdo->prepare('INSERT INTO kelas (kursus_id,pengajar_id,nama) VALUES (?,?,?)')->execute([(int)$_POST['kursus_id'],(int)$_POST['pengajar_id'],$n]); flash('Kelas dibuat.'); } }
  elseif ($a==='siswa') { $jid=(int)($_POST['jadwal_id']??0); $sid=(int)($_POST['siswa_id']??0);
    $g=$pdo->prepare('SELECT kelas_id FROM jadwal WHERE id=?'); $g->execute([$jid]); $kid=(int)$g->fetchColumn();
    if (!$kid || !$sid) flash('Pilih jadwal dan siswa terlebih dahulu.');
    else { $c=$pdo->prepare($elig.' AND k.id=? AND u.id=?'); $c->execute([$kid,$sid]);
      if (!$c->fetch()) flash('Siswa tidak dapat ditempatkan: pembayaran harus Lunas, kursusnya harus sama dengan kelas, dan siswa belum ada di kelas ini.');
      else { $pdo->prepare('INSERT INTO kelas_siswa (kelas_id,siswa_id) VALUES (?,?)')->execute([$kid,$sid]); flash('Siswa berhasil ditempatkan ke jadwal.'); } } }
  elseif ($a==='jadwal') { $kid=(int)($_POST['kelas_id']??0); $tg=$_POST['tanggal']??''; $m=$_POST['jam_mulai']??''; $e2=$_POST['jam_selesai']??''; $r=trim($_POST['ruang']??'');
    if (!$kid||!$tg||!$m||!$e2||$r==='') flash('Lengkapi data jadwal.');
    elseif ($e2<=$m) flash('Jam selesai harus setelah jam mulai.');
    else { $p=$pdo->prepare('SELECT pengajar_id FROM kelas WHERE id=?'); $p->execute([$kid]); $pg=$p->fetchColumn();
      $x=$pdo->prepare('SELECT COUNT(*) FROM jadwal j JOIN kelas k ON k.id=j.kelas_id WHERE j.tanggal=? AND j.jam_mulai<? AND j.jam_selesai>? AND (k.pengajar_id=? OR j.ruang=?)'); $x->execute([$tg,$e2,$m,$pg,$r]);
      if ($x->fetchColumn()) flash('Jadwal ditolak: bertabrakan dengan pengajar atau ruang yang sama pada waktu tersebut.');
      else { $pdo->prepare('INSERT INTO jadwal (kelas_id,tanggal,jam_mulai,jam_selesai,ruang) VALUES (?,?,?,?,?)')->execute([$kid,$tg,$m,$e2,$r]); flash('Jadwal ditambahkan.'); } } }
  redirect('jadwal.php'); }
include 'includes/header.php';
$q='SELECT j.*,k.nama kelas,c.nama kursus,p.nama pengajar FROM jadwal j JOIN kelas k ON k.id=j.kelas_id JOIN kursus c ON c.id=k.kursus_id JOIN users p ON p.id=k.pengajar_id';
$par=[]; if ($u['role']==='pengajar') { $q.=' WHERE k.pengajar_id=?'; $par=[$u['id']]; } elseif ($u['role']==='siswa') { $q.=' WHERE k.id IN (SELECT kelas_id FROM kelas_siswa WHERE siswa_id=?)'; $par=[$u['id']]; }
$s=$pdo->prepare($q.' ORDER BY j.tanggal,j.jam_mulai'); $s->execute($par); $jd=$s->fetchAll(); ?>
<h1><?= $u['role']==='admin' ? 'Kelas dan jadwal' : 'Jadwal saya' ?></h1>
<?php if ($u['role']==='admin'):
  $kur=$pdo->query("SELECT id,nama FROM kursus WHERE status='aktif' ORDER BY nama")->fetchAll();
  $pen=$pdo->query("SELECT id,nama FROM users WHERE role='pengajar' AND aktif=1 ORDER BY nama")->fetchAll();
  $kel=$pdo->query('SELECT k.id,k.nama,c.nama kursus FROM kelas k JOIN kursus c ON c.id=k.kursus_id ORDER BY c.nama,k.nama')->fetchAll();
  $el=$pdo->query($elig.' ORDER BY u.nama')->fetchAll();
  $jdl=$pdo->query('SELECT j.id,j.kelas_id,j.tanggal,j.jam_mulai,j.jam_selesai,j.ruang,k.nama kelas,c.nama kursus FROM jadwal j JOIN kelas k ON k.id=j.kelas_id JOIN kursus c ON c.id=k.kursus_id ORDER BY j.tanggal,j.jam_mulai')->fetchAll();
  $tanpa=$pdo->query("SELECT DISTINCT u.nama sn,c.nama kn FROM pendaftaran p JOIN tagihan t ON t.pendaftaran_id=p.id AND t.status='lunas' JOIN users u ON u.id=p.siswa_id JOIN kursus c ON c.id=p.kursus_id WHERE NOT EXISTS (SELECT 1 FROM kelas k WHERE k.kursus_id=p.kursus_id)")->fetchAll(); ?>
<div class="grid">
<form method="post" class="card" autocomplete="off" data-reset><?= csrf_field() ?><input type="hidden" name="aksi" value="kelas"><h3>Buat kelas</h3>
<label>Kursus</label><select name="kursus_id" required><?php foreach ($kur as $x): ?><option value="<?= $x['id'] ?>"><?= e($x['nama']) ?></option><?php endforeach; ?></select>
<label>Pengajar</label><select name="pengajar_id" required><?php foreach ($pen as $x): ?><option value="<?= $x['id'] ?>"><?= e($x['nama']) ?></option><?php endforeach; ?></select>
<label>Nama kelas</label><input name="nama" required placeholder="Contoh: Kelas A Sore"><p><button>Buat kelas</button></p></form>
<form method="post" class="card" autocomplete="off" data-reset id="formSiswa"><?= csrf_field() ?><input type="hidden" name="aksi" value="siswa"><h3>Tempatkan siswa ke jadwal</h3>
<p class="muted">Hanya siswa dengan pembayaran Lunas yang bisa dipilih. Siswa akan masuk ke kelas dari jadwal yang dipilih.</p>
<?php if (!$jdl): ?><p class="flash">Belum ada jadwal. Buat kelas dan tambahkan jadwal dulu.</p>
<?php else: ?>
<label>Jadwal</label><select name="jadwal_id" id="selJadwal" required><option value="">- Pilih jadwal -</option>
<?php foreach ($jdl as $x): ?><option value="<?= $x['id'] ?>" data-kelas="<?= $x['kelas_id'] ?>"><?= e($x['tanggal']) ?> <?= e(substr($x['jam_mulai'],0,5)) ?>-<?= e(substr($x['jam_selesai'],0,5)) ?> | <?= e($x['kursus']) ?> - <?= e($x['kelas']) ?> (<?= e($x['ruang']) ?>)</option><?php endforeach; ?></select>
<label>Siswa</label><select name="siswa_id" id="selSiswa" required><option value="">- Pilih jadwal dulu -</option>
<?php foreach ($el as $x): ?><option value="<?= $x['sid'] ?>" data-kelas="<?= $x['kid'] ?>" hidden disabled><?= e($x['sn']) ?></option><?php endforeach; ?></select>
<p id="infoSiswa" class="muted"></p>
<p><button>Tempatkan</button></p>
<script>(function(){var j=document.getElementById('selJadwal'),s=document.getElementById('selSiswa'),i=document.getElementById('infoSiswa');
function f(){var k=j.options[j.selectedIndex].getAttribute('data-kelas'),n=0,first=null;
for(var x=1;x<s.options.length;x++){var o=s.options[x],ok=k&&o.getAttribute('data-kelas')===k;o.hidden=!ok;o.disabled=!ok;if(ok){n++}}
s.options[0].textContent=!k?'- Pilih jadwal dulu -':(n?'- Pilih siswa -':'- Tidak ada siswa yang memenuhi syarat -');s.selectedIndex=0;
i.textContent=k&&!n?'Belum ada siswa Lunas untuk kursus ini yang belum masuk kelas tersebut.':''}
j.addEventListener('change',f);f();})();</script>
<?php endif; ?></form>
<form method="post" class="card" autocomplete="off" data-reset><?= csrf_field() ?><input type="hidden" name="aksi" value="jadwal"><h3>Tambah jadwal</h3>
<label>Kelas</label><select name="kelas_id" required><?php foreach ($kel as $x): ?><option value="<?= $x['id'] ?>"><?= e($x['kursus']) ?> - <?= e($x['nama']) ?></option><?php endforeach; ?></select>
<label>Tanggal</label><input type="date" name="tanggal" required><label>Jam mulai</label><input type="time" name="jam_mulai" required>
<label>Jam selesai</label><input type="time" name="jam_selesai" required><label>Ruang</label><input name="ruang" required><p><button>Simpan jadwal</button></p></form></div>
<?php if ($tanpa): ?><div class="card"><b>Siswa Lunas yang kursusnya belum punya kelas:</b><ul><?php foreach ($tanpa as $x): ?><li><?= e($x['sn']) ?> - <?= e($x['kn']) ?></li><?php endforeach; ?></ul><span class="muted">Buat kelas untuk kursus tersebut, tambahkan jadwal, lalu tempatkan siswanya.</span></div><?php endif; ?>
<?php endif; ?>
<div class="tablewrap"><table><tr><th>Tanggal</th><th>Jam</th><th>Kursus / Kelas</th><th>Pengajar</th><th>Ruang</th></tr>
<?php foreach ($jd as $j): ?><tr><td><?= e($j['tanggal']) ?></td><td><?= e(substr($j['jam_mulai'],0,5)) ?> - <?= e(substr($j['jam_selesai'],0,5)) ?></td><td><?= e($j['kursus']) ?> / <?= e($j['kelas']) ?></td><td><?= e($j['pengajar']) ?></td><td><?= e($j['ruang']) ?></td></tr><?php endforeach; ?></table></div>
<?php if (!$jd): ?><div class="card"><p><?= $u['role']==='siswa' ? 'Belum ada jadwal. Selesaikan pembayaran, lalu tunggu admin menempatkan Anda ke kelas.' : 'Belum ada jadwal.' ?></p></div><?php endif;
if ($u['role']!=='siswa'):
  $k2=$pdo->prepare('SELECT k.nama,c.nama kursus,GROUP_CONCAT(u.nama SEPARATOR ", ") siswa FROM kelas k JOIN kursus c ON c.id=k.kursus_id LEFT JOIN kelas_siswa ks ON ks.kelas_id=k.id LEFT JOIN users u ON u.id=ks.siswa_id '.($u['role']==='pengajar'?'WHERE k.pengajar_id=? ':'').'GROUP BY k.id'); $k2->execute($u['role']==='pengajar'?[$u['id']]:[]); ?>
<h2>Daftar siswa per kelas</h2><?php foreach ($k2 as $k): ?><div class="card"><b><?= e($k['kursus']) ?> / <?= e($k['nama']) ?></b><br><?= e($k['siswa'] ?: 'Belum ada siswa') ?></div><?php endforeach; endif;
include 'includes/footer.php'; ?>
