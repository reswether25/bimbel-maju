<?php require 'config.php'; session_unset(); session_destroy(); session_start(); flash('Anda sudah keluar.'); redirect('login.php');
