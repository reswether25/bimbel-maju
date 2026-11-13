CARA MENJALANKAN (XAMPP)
1. Salin folder ini ke C:\xampp\htdocs\kursus-app
2. Jalankan Apache dan MySQL di XAMPP Control Panel
3. Buka http://localhost/phpmyadmin, tab Import, pilih database.sql, klik Import
4. Buka http://localhost/kursus-app

AKUN CONTOH
Admin    : admin@kursus.test / admin123
Pengajar : pengajar@kursus.test / pengajar123
Siswa    : daftar sendiri lewat halaman Daftar akun

Jika MySQL XAMPP memakai password, ubah DB_PASS di config.php.

CATATAN VERSI BARU
- Metode bayar dan tujuan bayar (nomor rekening/e-wallet) diatur di config.php, variabel $METODE_BAYAR. Ganti dengan data asli.
- Jika database lama sudah berisi data, jangan import ulang database.sql. Cukup jalankan migrasi.sql di phpMyAdmin (tab SQL).
- Jika memulai dari nol, import database.sql seperti biasa.
- Menu Presensi: pengajar mengisi kehadiran per jadwal (Hadir/Izin/Sakit/Alpa), siswa melihat riwayatnya, admin melihat rekap. Tidak perlu mengubah database.
