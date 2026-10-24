<?php
/**
 * SKRIP SETUP SUPER ADMIN (VERSI SEDERHANA)
 * -----------------------------------------
 * Digunakan untuk mendaftarkan akun Super Admin pertama.
 * Cukup jalankan satu kali di browser.
 */

require_once 'config/database.php';

$message = "";

if (isset($_POST['buat_admin'])) {
    $nama = "Super Admin";
    $email = "admin@alfaone.com";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $role = "super_admin";

    // Cek apakah email sudah ada
    $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $cek->execute([$email]);

    if ($cek->fetch()) {
        $message = "<div style='color:red;'>Error: Akun $email sudah ada di database!</div>";
    } else {
        // Simpan ke database
        $sql = "INSERT INTO users (nama, email, password, role, status_verifikasi) 
                VALUES (?, ?, ?, ?, 'verified')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nama, $email, $password, $role])) {
            $message = "<div style='color:green;'>Sukses! Akun Super Admin berhasil dibuat.</div>";
        } else {
            $message = "<div style='color:red;'>Gagal membuat akun.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Super Admin</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f4; }
        .box { background: #c8e4f5; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Pendaftaran Awal Super Admin</h2>
        <p>Email: admin@alfaone.com | Pass: admin123</p>
        <?= $message ?>
        <br>
        <form method="POST">
            <button type="submit" name="buat_admin">Buat Akun Sekarang</button>
        </form>
    </div>
</body>
</html>
