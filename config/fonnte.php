<?php
// ============================================================
// Konfigurasi Notifikasi WhatsApp via Fonnte
// ============================================================
// 1. Daftar di https://fonnte.com
// 2. Verifikasi nomor WA bisnis Anda
// 3. Salin token API dan tempel di bawah ini
// ============================================================

define('FONNTE_TOKEN', 'YsHddXDMKTiDCvzBR8Qz'); // Ganti dengan token dari fonnte.com

/**
 * Kirim pesan WhatsApp via Fonnte API
 * @param string $nomor   Nomor tujuan (format: 08xx atau 628xx)
 * @param string $pesan   Isi pesan teks
 * @return bool           true jika berhasil, false jika gagal
 */
function kirimNotifikasiWA(string $nomor, string $pesan): bool {
    // Jika token belum diset, skip (tidak error, cukup return false)
    if (FONNTE_TOKEN === 'TOKEN_FONNTE_ANDA_DISINI' || empty(FONNTE_TOKEN)) {
        error_log("[WA Notif] Token Fonnte belum dikonfigurasi di config/fonnte.php");
        return false;
    }

    // Normalisasi nomor: hilangkan karakter non-digit dan ubah ke format 62xxx
    $nomor = preg_replace('/\D/', '', $nomor);
    if (str_starts_with($nomor, '0')) {
        $nomor = '62' . substr($nomor, 1);
    } elseif (!str_starts_with($nomor, '62')) {
        $nomor = '62' . $nomor;
    }

    // Skip jika nomor tidak valid
    if (strlen($nomor) < 10) {
        error_log("[WA Notif] Nomor tidak valid: $nomor");
        return false;
    }

    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . FONNTE_TOKEN],
        CURLOPT_POSTFIELDS     => [
            'target'  => $nomor,
            'message' => $pesan,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return isset($result['status']) && $result['status'] === true;
    }

    error_log("[WA Notif] Gagal kirim WA ke $nomor. HTTP: $httpCode, Response: $response");
    return false;
}

/**
 * Dapatkan nomor WA terbaik dari data user (prioritas no_wa, fallback ke telepon)
 * @param array $user   Data user dari database
 * @return string|null  Nomor WA atau null jika tidak ada
 */
function getNomorWA(array $user): ?string {
    $nomor = !empty($user['no_wa']) ? $user['no_wa'] : ($user['telepon'] ?? null);
    return !empty($nomor) ? $nomor : null;
}

/**
 * Format deep link WhatsApp untuk dibuka di browser/app
 * @param string $nomor  Nomor WA
 * @param string $pesan  Pesan template
 * @return string        URL wa.me
 */
function getWADeepLink(string $nomor, string $pesan = ''): string {
    $nomor = preg_replace('/\D/', '', $nomor);
    if (str_starts_with($nomor, '0')) {
        $nomor = '62' . substr($nomor, 1);
    } elseif (!str_starts_with($nomor, '62')) {
        $nomor = '62' . $nomor;
    }
    return 'https://api.whatsapp.com/send?phone=' . $nomor . (!empty($pesan) ? '&text=' . rawurlencode($pesan) : '');
}
