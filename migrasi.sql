-- Jalankan sekali di phpMyAdmin (tab SQL) jika database lama sudah terisi data.
-- Menambah kolom metode pembayaran. Kolom deskripsi pada tabel kursus tidak lagi dipakai.
USE kursus_db;
ALTER TABLE pembayaran ADD COLUMN metode VARCHAR(30) NULL AFTER bukti;
