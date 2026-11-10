<?php

namespace RestoKita;

use PDO;

/**
 * Class Auth
 *
 * Mengelola sistem autentikasi dan otorisasi pengguna berbasis PHP Session:
 * - Login dengan verifikasi hash password (password_verify)
 * - Logout dan destruksi session yang aman
 * - Cek role hak akses (Admin vs Kasir)
 * - Middleware proteksi halaman
 *
 * @package RestoKita
 */
class Auth
{
    private PDO $db;

    /**
     * Inisialisasi Auth dengan koneksi database.
     *
     * @param PDO|null $db Objek koneksi PDO
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
        Helper::startSession();
    }

    /**
     * Memproses login pengguna dengan username dan password mentah.
     *
     * @param string $username Username pengguna
     * @param string $password Password teks biasa
     * @return bool True jika berhasil, false jika username atau password salah
     */
    public function login(string $username, string $password): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id_pengguna, nama, username, password, role FROM pengguna WHERE username = :username LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Mencegah Session Fixation Attack dengan meregenerasi session ID
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id_pengguna' => (int) $user['id_pengguna'],
                'nama'        => $user['nama'],
                'username'    => $user['username'],
                'role'        => $user['role'],
            ];

            return true;
        }

        return false;
    }

    /**
     * Mengakhiri sesi login pengguna dan menghapus data session.
     *
     * @return void
     */
    public static function logout(): void
    {
        Helper::startSession();
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Mengecek apakah ada pengguna yang sedang login.
     *
     * @return bool
     */
    public static function check(): bool
    {
        Helper::startSession();
        return isset($_SESSION['user']['id_pengguna']);
    }

    /**
     * Mengambil seluruh data user yang sedang login saat ini.
     *
     * @return array{id_pengguna: int, nama: string, username: string, role: string}|null
     */
    public static function user(): ?array
    {
        Helper::startSession();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Mengambil ID pengguna yang sedang login.
     *
     * @return int|null
     */
    public static function id(): ?int
    {
        Helper::startSession();
        return $_SESSION['user']['id_pengguna'] ?? null;
    }

    /**
     * Mengambil role pengguna yang sedang login ('admin' atau 'kasir').
     *
     * @return string|null
     */
    public static function role(): ?string
    {
        Helper::startSession();
        return $_SESSION['user']['role'] ?? null;
    }

    /**
     * Memeriksa apakah user yang sedang login memiliki hak akses 'admin'.
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * Memeriksa apakah user yang sedang login memiliki hak akses 'kasir'.
     *
     * @return bool
     */
    public static function isKasir(): bool
    {
        return self::role() === 'kasir';
    }

    /**
     * Memastikan user sudah login. Jika belum, diarahkan otomatis ke halaman login.
     *
     * @param string $redirectPath URL tujuan jika belum login
     * @return void
     */
    public static function requireLogin(string $redirectPath = '/login.php'): void
    {
        if (!self::check()) {
            Helper::setFlash('warning', 'Silakan login terlebih dahulu untuk mengakses sistem.');
            Helper::redirect($redirectPath);
        }
    }

    /**
     * Memastikan user adalah admin. Jika bukan, diarahkan ke halaman dashboard.
     *
     * @param string $redirectPath URL tujuan jika bukan admin
     * @return void
     */
    public static function requireAdmin(string $redirectPath = '/dashboard.php'): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            Helper::setFlash('danger', 'Akses ditolak. Fitur ini hanya dapat diakses oleh Administrator.');
            Helper::redirect($redirectPath);
        }
    }
}
