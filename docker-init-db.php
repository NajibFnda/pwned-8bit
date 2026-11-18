<?php
/**
 * Bootstrap skema database & akun default secara idempoten saat container start di Railway.
 */
$host = getenv('MYSQLHOST') ?: getenv('database_default_hostname') ?: '';
if ($host === '') {
    echo "[docker-init-db] MYSQLHOST not set, skipping DB bootstrap.\n";
    exit(0);
}

$user   = getenv('MYSQLUSER') ?: 'root';
$pass   = getenv('MYSQLPASSWORD') ?: '';
$name   = getenv('MYSQLDATABASE') ?: 'railway';
$port   = (int) (getenv('MYSQLPORT') ?: 3306);
$useSsl = filter_var(getenv('MYSQL_SSL') ?: 'false', FILTER_VALIDATE_BOOLEAN);

$db = null;
for ($attempt = 1; $attempt <= 15; $attempt++) {
    try {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn  = mysqli_init();
        $flags = 0;
        if ($useSsl && $conn) {
            $caPath = (string) (getenv('MYSQL_SSL_CA') ?: '');
            if ($caPath === '' && is_file('/etc/ssl/certs/ca-certificates.crt')) {
                $caPath = '/etc/ssl/certs/ca-certificates.crt';
            }
            $conn->ssl_set(null, null, $caPath !== '' ? $caPath : null, null, null);
            $flags = MYSQLI_CLIENT_SSL;
        }
        if ($conn && @$conn->real_connect($host, $user, $pass, $name, $port, null, $flags)) {
            $db = $conn;
            break;
        }
        $err = $conn ? $conn->connect_error : 'mysqli_init failed';
        echo "[docker-init-db] Waiting for MySQL ({$attempt}/15): {$err}\n";
    } catch (\Throwable $e) {
        echo "[docker-init-db] Waiting for MySQL ({$attempt}/15): {$e->getMessage()}\n";
    }
    sleep(2);
}

if (!$db) {
    echo "[docker-init-db] Could not connect to MySQL after 15 attempts, continuing startup.\n";
    exit(0);
}

$db->set_charset('utf8mb4');

$queries = [
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `nama` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` enum('admin','user') DEFAULT 'user',
        `subscription_plan` enum('free','plus','pro') DEFAULT 'free',
        `pending_plan` varchar(10) DEFAULT NULL,
        `expire_date` datetime DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS `transactions` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `user_id` int(10) unsigned NOT NULL,
        `paket` varchar(50) NOT NULL,
        `mode` varchar(20) DEFAULT 'bulanan',
        `harga` int(11) NOT NULL,
        `status` varchar(50) DEFAULT 'pending',
        `tanggal` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `transactions_user_id_foreign` (`user_id`),
        CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS `search_history` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `user_id` int(10) unsigned NOT NULL,
        `email_checked` varchar(255) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `search_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS `user_requests` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `user_id` int(11) unsigned NOT NULL,
        `jenis_request` enum('upgrade_plus','upgrade_pro','downgrade_free','other') NOT NULL DEFAULT 'other',
        `pesan` text DEFAULT NULL,
        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `created_at` datetime DEFAULT NULL,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS `rate_limits` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `action` varchar(40) NOT NULL,
        `ip_hash` char(32) NOT NULL,
        `actor_id` int(10) unsigned DEFAULT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_rate_lookup` (`action`,`ip_hash`,`created_at`),
        KEY `idx_rate_actor` (`action`,`actor_id`,`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS `security_logs` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `event_type` varchar(50) NOT NULL,
        `severity` enum('info','warning','critical') NOT NULL DEFAULT 'info',
        `actor_email` varchar(150) DEFAULT NULL,
        `ip_address` varchar(64) NOT NULL,
        `detail` varchar(255) NOT NULL DEFAULT '',
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_sec_event_time` (`event_type`,`created_at`),
        KEY `idx_sec_severity_time` (`severity`,`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

foreach ($queries as $sql) {
    if (!$db->query($sql)) {
        echo "[docker-init-db] Schema error: " . $db->error . "\n";
    }
}

$checkAdmin = $db->query("SELECT COUNT(*) as cnt FROM `users` WHERE `email` = 'admin@gmail.com'");
$row        = $checkAdmin ? $checkAdmin->fetch_assoc() : ['cnt' => 0];
if ((int) ($row['cnt'] ?? 0) === 0) {
    $adminHash = password_hash('123', PASSWORD_DEFAULT);
    $stmt      = $db->prepare("INSERT INTO `users` (`nama`, `email`, `password`, `role`, `subscription_plan`) VALUES ('Admin', 'admin@gmail.com', ?, 'admin', 'pro')");
    if ($stmt) {
        $stmt->bind_param('s', $adminHash);
        $stmt->execute();
        echo "[docker-init-db] Seeded default admin account (admin@gmail.com).\n";
    }
}

echo "[docker-init-db] Database initialization complete.\n";
