<?php require 'config.php'; require_login(['admin','siswa']); $u=user();
$label=['belum_bayar'=>'Belum Bayar','menunggu_verifikasi'=>'Menunggu Verifikasi','lunas'=>'Lunas','ditolak'=>'Ditolak'];
$s=$pdo->prepare('SELECT t.*,p.siswa_id,u.nama siswa,u.email,k.nama kursus,k.tingkat,(SELECT metode FROM pembayaran WHERE id=(SELECT MAX(id) FROM pembayaran WHERE tagihan_id=t.id)) metode,(SELECT tanggal FROM pembayaran WHERE id=(SELECT MAX(id) FROM pembayaran WHERE tagihan_id=t.id)) tgl_bayar FROM tagihan t JOIN pendaftaran p ON p.id=t.pendaftaran_id JOIN users u ON u.id=p.siswa_id JOIN kursus k ON k.id=p.kursus_id WHERE t.id=?');
$s->execute([(int)($_GET['id']??0)]); $t=$s->fetch();
if (!$t || ($u['role']!=='admin' && $t['siswa_id']!=$u['id'])) { http_response_code(404); die('Invoice tidak ditemukan.'); }
$pilih = ($t['metode'] && isset($METODE_BAYAR[$t['metode']])) ? [$t['metode']=>$METODE_BAYAR[$t['metode']]] : $METODE_BAYAR; ?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice <?= e(invoice_no($t)) ?> - Bimbel Maju</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@700;800&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{margin:0;background:#f3f6f4;font-family:"Plus Jakarta Sans","Segoe UI",system-ui,sans-serif;color:#14231f;line-height:1.55}
.inv{max-width:760px;margin:1.5rem auto;background:#fff;border:2px solid #14231f;box-shadow:6px 6px 0 #14231f;padding:2rem}
.top{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;border-bottom:3px solid #f4c542;padding-bottom:1rem;margin-bottom:1.2rem}
h1,h2{font-family:Fraunces,Georgia,serif;margin:0}h1{font-size:1.8rem}h2{font-size:1.05rem;margin:1.4rem 0 .5rem}
.r{text-align:right}.muted{color:#60726b}
table{width:100%;border-collapse:collapse}th,td{padding:.6rem .7rem;border-bottom:1px solid #dfe7e3;text-align:left}th{background:#1b3a33;color:#fff;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em}
td.n,th.n{text-align:right}.tot td{font-weight:800;font-size:1.1rem;border-top:2px solid #14231f}
.badge{display:inline-block;padding:.05rem .7rem;border:2px solid #14231f;border-radius:20px;font-weight:700;font-size:.8rem;background:#fff4c9}.badge.lunas{background:#8fd6b4}.badge.ditolak{background:#f6c3b8}
.metode{border:2px solid #14231f;border-radius:6px;padding:.5rem .8rem;margin:.4rem 0}
.act{max-width:760px;margin:1rem auto;display:flex;gap:.6rem}
button,a.b{background:#1b3a33;color:#fff;border:2px solid #14231f;padding:.5rem 1rem;border-radius:4px;font:inherit;font-weight:700;cursor:pointer;text-decoration:none;box-shadow:3px 3px 0 #14231f}a.b{background:#fff;color:#1b3a33}
@media print{body{background:#fff}.act{display:none}.inv{border:0;box-shadow:none;margin:0;max-width:none}}
</style></head><body>
<div class="act"><button onclick="window.print()">Cetak / simpan PDF</button><a class="b" href="pembayaran.php">Kembali</a></div>
<div class="inv">
  <div class="top"><div><h1>Bimbel Maju</h1><span class="muted">Manajemen kursus</span></div>
    <div class="r"><h1>INVOICE</h1><b><?= e(invoice_no($t)) ?></b><br><span class="muted">Terbit: <?= e(date('d/m/Y', strtotime($t['created_at']))) ?></span></div></div>
  <h2>Ditagihkan kepada</h2><b><?= e($t['siswa']) ?></b><br><span class="muted"><?= e($t['email']) ?></span>
  <h2>Rincian</h2>
  <table><tr><th>Kursus</th><th>Tingkat</th><th class="n">Jumlah</th></tr>
  <tr><td><?= e($t['kursus']) ?></td><td><?= e($t['tingkat'] ?: '-') ?></td><td class="n"><?= rupiah($t['jumlah']) ?></td></tr>
  <tr class="tot"><td colspan="2">Total tagihan</td><td class="n"><?= rupiah($t['jumlah']) ?></td></tr></table>
  <h2>Status</h2><span class="badge <?= e($t['status']) ?>"><?= $label[$t['status']] ?></span>
  <?php if ($t['status']==='lunas' && $t['tgl_bayar']): ?> <span class="muted">Dibayar pada <?= e(date('d/m/Y', strtotime($t['tgl_bayar']))) ?></span><?php endif; ?>
  <h2><?= $t['status']==='lunas' ? 'Dibayar melalui' : ($t['metode'] ? 'Metode dipilih' : 'Cara pembayaran') ?></h2>
  <?php foreach ($pilih as $m): ?><div class="metode"><b><?= e($m['nama']) ?></b><br><?= e($m['info']) ?>: <b><?= e($m['tujuan']) ?></b> a.n. <?= e($m['atas_nama']) ?></div><?php endforeach; ?>
  <?php if (in_array($t['status'],['belum_bayar','ditolak'])): ?><p class="muted">Transfer sesuai jumlah tagihan, lalu unggah bukti pembayaran di menu Pembayaran.</p><?php endif; ?>
</div></body></html>
