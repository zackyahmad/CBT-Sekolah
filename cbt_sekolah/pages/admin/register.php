<?php
session_start();
// Cek sesi admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Proses Update Status User (Aktif/Nonaktif)
if (isset($_POST['update_status'])) {
    $id_user = $_POST['id_user'];
    $status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE tb_users SET status = ? WHERE id_user = ?");
        $stmt->execute([$status, $id_user]);
        $_SESSION['success'] = "Status user berhasil diupdate!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal mengupdate status: " . $e->getMessage();
    }
    header("Location: register.php");
    exit;
}

// Proses Tambah User Manual
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role = $_POST['role'];
    $jenis_kelamin = !empty($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : null;
    $tempat_lahir = !empty($_POST['tempat_lahir']) ? trim($_POST['tempat_lahir']) : null;
    $tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
    $agama = !empty($_POST['agama']) ? trim($_POST['agama']) : null;
    $alamat = !empty($_POST['alamat']) ? trim($_POST['alamat']) : null;
    $ruangan = !empty($_POST['ruangan']) ? trim($_POST['ruangan']) : null;
    $status = 'aktif';
    
    try {
        $check = $pdo->prepare("SELECT COUNT(*) FROM tb_users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Username '{$username}' sudah terdaftar!";
        } else {
            $sql = "INSERT INTO tb_users (username, password, nama_lengkap, role, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, alamat, ruangan, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $password, $nama_lengkap, $role, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $agama, $alamat, $ruangan, $status]);
            $_SESSION['success'] = "User '{$nama_lengkap}' berhasil ditambahkan!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menambahkan user: " . $e->getMessage();
    }
    header("Location: register.php");
    exit;
}

// Proses Hapus User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        if ($id == $_SESSION['id_user']) {
            $_SESSION['error'] = "Tidak dapat menghapus akun sendiri!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM tb_users WHERE id_user = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "User berhasil dihapus!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
    }
    header("Location: register.php");
    exit;
}

// Proses Edit User
if (isset($_POST['edit_user'])) {
    $id_user = $_POST['id_user'];
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role = $_POST['role'];
    $jenis_kelamin = !empty($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : null;
    $tempat_lahir = !empty($_POST['tempat_lahir']) ? trim($_POST['tempat_lahir']) : null;
    $tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
    $agama = !empty($_POST['agama']) ? trim($_POST['agama']) : null;
    $alamat = !empty($_POST['alamat']) ? trim($_POST['alamat']) : null;
    $ruangan = !empty($_POST['ruangan']) ? trim($_POST['ruangan']) : null;
    
    try {
        $sql = "UPDATE tb_users SET nama_lengkap = ?, role = ?, jenis_kelamin = ?, tempat_lahir = ?, tanggal_lahir = ?, agama = ?, alamat = ?, ruangan = ? WHERE id_user = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama_lengkap, $role, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $agama, $alamat, $ruangan, $id_user]);
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE tb_users SET password = ? WHERE id_user = ?");
            $stmt->execute([$password, $id_user]);
        }
        
        $_SESSION['success'] = "User '{$nama_lengkap}' berhasil diupdate!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal mengupdate user: " . $e->getMessage();
    }
    header("Location: register.php");
    exit;
}

// Reset Password
if (isset($_GET['reset_password'])) {
    $id = $_GET['reset_password'];
    $default_password = password_hash('123456', PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("UPDATE tb_users SET password = ? WHERE id_user = ?");
        $stmt->execute([$default_password, $id]);
        $_SESSION['success'] = "Password berhasil direset menjadi: 123456";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal mereset password: " . $e->getMessage();
    }
    header("Location: register.php");
    exit;
}

// Ambil data user dengan filter
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$query = "SELECT * FROM tb_users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY id_user DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Data statistik
$total_siswa = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role = 'siswa'")->fetchColumn();
$total_guru = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role = 'guru'")->fetchColumn();
$total_admin = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role = 'admin'")->fetchColumn();
$total_pengawas = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role = 'pengawas'")->fetchColumn();
$total_active = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE status = 'aktif'")->fetchColumn();
$total_inactive = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE status = 'nonaktif'")->fetchColumn();
$total_all = $total_siswa + $total_guru + $total_admin + $total_pengawas;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User - Admin | SMK N 8 Bandar Lampung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4A6FA5;
            --primary-light: #6B8DB5;
            --primary-dark: #3D5A80;
            --secondary: #98C1D9;
            --bg-main: #F5F0E8;
            --bg-card: #FFFFFF;
            --text-primary: #1E293B;
            --text-secondary: #64748B;
            --border: #E2E8F0;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #F5F0E8 0%, #EDE5D8 100%);
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        .app-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(74, 111, 165, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 111, 165, 0.4);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 20px;
            background: var(--bg-card);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        .data-table th {
            background: #F8FAFC;
            color: var(--text-primary);
            padding: 14px 10px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }
        .data-table td {
            padding: 12px 10px;
            font-size: 0.8rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: var(--text-primary);
        }
        .data-table tr:hover {
            background: #F8FAFC;
        }

        .badge-role {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .badge-siswa { background: #EFF6FF; color: #1E40AF; }
        .badge-guru { background: #ECFDF5; color: #065F46; }
        .badge-admin { background: #FEF3C7; color: #92400E; }
        .badge-pengawas { background: #F5F3FF; color: #5B21B6; }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }
        .form-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 5px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-input {
            padding: 10px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            font-size: 0.85rem;
            background: white;
        }
        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: scale(1.05);
            background: #F1F5F9;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #CBD5E0;
            transition: 0.3s;
            border-radius: 22px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        input:checked + .slider {
            background-color: var(--success);
        }
        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .dashboard-link {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        .dashboard-link:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }
        
        .truncate-text {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="app-header">
    <div class="px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">Register User</h1>
                    <p class="text-xs text-white/80">Kelola akun pengguna sistem</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right text-white">
                <p class="text-sm font-semibold"><?= $_SESSION['nama'] ?? 'Admin' ?></p>
                <p class="text-xs text-white/70">Administrator</p>
            </div>
            <a href="dashboard.php" class="dashboard-link flex items-center gap-2 text-white px-5 py-2 rounded-xl text-sm font-medium">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<main class="p-6 max-w-[1600px] mx-auto">
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-xl mb-6 flex items-center justify-between">
            <div><i class="fas fa-check-circle mr-2"></i> <?= nl2br(htmlspecialchars($_SESSION['success'])) ?></div>
            <button onclick="this.parentElement.remove()" class="text-green-700"><i class="fas fa-times"></i></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-xl mb-6 flex items-center justify-between">
            <div><i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($_SESSION['error']) ?></div>
            <button onclick="this.parentElement.remove()" class="text-red-700"><i class="fas fa-times"></i></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- STATISTIK CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total User</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $total_all ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Siswa</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $total_siswa ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-user-graduate text-blue-600 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Guru</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $total_guru ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <i class="fas fa-chalkboard-user text-green-600 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pengawas</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $total_pengawas ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-user-shield text-purple-600 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Aktif</p>
                    <p class="text-2xl font-bold text-green-600 mt-1"><?= $total_active ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-lg"></i>
                </div>
            </div>
        </div>
        <div class="stat-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Nonaktif</p>
                    <p class="text-2xl font-bold text-red-600 mt-1"><?= $total_inactive ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <i class="fas fa-ban text-red-600 text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- TOMBOL & FILTER -->
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <button onclick="openAddModal()" class="btn-primary text-white px-5 py-2.5 rounded-xl font-medium flex items-center gap-2 text-sm shadow-lg">
            <i class="fas fa-plus"></i> Tambah User Baru
        </button>
        
        <form method="GET" class="flex gap-3 flex-wrap items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="search" placeholder="Cari nama, username..." value="<?= htmlspecialchars($search) ?>" class="filter-input pl-9 w-64">
            </div>
            <select name="role_filter" class="filter-input" onchange="this.form.submit()">
                <option value="">Semua Role</option>
                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="guru" <?= $role_filter == 'guru' ? 'selected' : '' ?>>Guru</option>
                <option value="siswa" <?= $role_filter == 'siswa' ? 'selected' : '' ?>>Siswa</option>
                <option value="pengawas" <?= $role_filter == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
            </select>
            <select name="status_filter" class="filter-input" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $status_filter == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-xl transition text-sm shadow-md flex items-center gap-2">
                <i class="fas fa-search"></i> Cari
            </button>
            <?php if ($search || $role_filter || $status_filter): ?>
                <a href="register.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl transition text-sm shadow-md flex items-center gap-2">
                    <i class="fas fa-times"></i> Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABEL DATA USER (SESUAI FORM REGISTER) -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="40">NO</th>
                    <th>USERNAME</th>
                    <th>NAMA LENGKAP</th>
                    <th>ROLE</th>
                    <th>JENIS KELAMIN</th>
                    <th>TEMPAT LAHIR</th>
                    <th>TANGGAL LAHIR</th>
                    <th>AGAMA</th>
                    <th>RUANGAN</th>
                    <th>ALAMAT</th>
                    <th>STATUS</th>
                    <th width="100">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php $no = 1; foreach ($users as $user): ?>
                        <tr>
                            <td class="text-center font-semibold text-gray-500"><?= $no++ ?></td>
                            <td class="font-medium"><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                            <td>
                                <span class="badge-role 
                                    <?= $user['role'] == 'siswa' ? 'badge-siswa' : '' ?>
                                    <?= $user['role'] == 'guru' ? 'badge-guru' : '' ?>
                                    <?= $user['role'] == 'admin' ? 'badge-admin' : '' ?>
                                    <?= $user['role'] == 'pengawas' ? 'badge-pengawas' : '' ?>">
                                    <i class="fas fa-<?= $user['role'] == 'siswa' ? 'user-graduate' : ($user['role'] == 'guru' ? 'chalkboard-user' : ($user['role'] == 'admin' ? 'user-cog' : 'user-shield')) ?> text-xs"></i>
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $jk = $user['jenis_kelamin'] ?? '';
                                if ($jk == 'L') echo '<span class="text-blue-600"><i class="fas fa-mars mr-1"></i> Laki-laki</span>';
                                elseif ($jk == 'P') echo '<span class="text-pink-600"><i class="fas fa-venus mr-1"></i> Perempuan</span>';
                                else echo '<span class="text-gray-400">-</span>';
                                ?>
                            </td>
                            <td><?= htmlspecialchars($user['tempat_lahir'] ?? '-') ?></td>
                            <td>
                                <?php 
                                if (!empty($user['tanggal_lahir'])) {
                                    echo date('d/m/Y', strtotime($user['tanggal_lahir']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($user['agama'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($user['ruangan'] ?? '-') ?></td>
                            <td class="truncate-text" title="<?= htmlspecialchars($user['alamat'] ?? '') ?>"><?= htmlspecialchars($user['alamat'] ?? '-') ?></td>
                            <td>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="id_user" value="<?= $user['id_user'] ?>">
                                    <input type="hidden" name="status" value="<?= $user['status'] == 'aktif' ? 'nonaktif' : 'aktif' ?>">
                                    <button type="submit" name="update_status" class="switch">
                                        <input type="checkbox" <?= $user['status'] == 'aktif' ? 'checked' : '' ?> <?= $user['id_user'] == $_SESSION['id_user'] ? 'disabled' : '' ?>>
                                        <span class="slider"></span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" class="action-btn text-blue-600 hover:bg-blue-50" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id_user'] != $_SESSION['id_user']): ?>
                                        <button onclick="confirmDelete(<?= $user['id_user'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>')" class="action-btn text-red-600 hover:bg-red-50" title="Hapus User">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="confirmResetPassword(<?= $user['id_user'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>')" class="action-btn text-yellow-600 hover:bg-yellow-50" title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center py-12 text-gray-400">
                            <i class="fas fa-users fa-3x mb-3 block opacity-50"></i>
                            <p>Belum ada data user</p>
                            <p class="text-sm mt-1">Klik "Tambah User Baru" untuk menambahkan</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-4 text-center text-xs text-gray-400">
        <i class="fas fa-info-circle"></i> Menampilkan <?= count($users) ?> dari <?= $total_all ?> total user
    </div>
</main>

<!-- MODAL TAMBAH USER -->
<div id="addModal" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gradient-to-r from-[#4A6FA5] to-[#3D5A80] px-6 py-4 flex justify-between items-center rounded-t-2xl">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-user-plus mr-2"></i> Tambah User Baru
            </h2>
            <button onclick="closeAddModal()" class="text-white/80 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="col-span-2">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-input" required>
                        <option value="">Pilih Role</option>
                        <option value="admin">Admin</option>
                        <option value="guru">Guru</option>
                        <option value="siswa">Siswa</option>
                        <option value="pengawas">Pengawas</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-input">
                        <option value="">Pilih</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" class="form-input">
                </div>
                <div>
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="form-input">
                </div>
                <div>
                    <label class="form-label">Agama</label>
                    <select name="agama" class="form-input">
                        <option value="">Pilih</option>
                        <option value="Islam">Islam</option>
                        <option value="Kristen">Kristen</option>
                        <option value="Katolik">Katolik</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                        <option value="Konghucu">Konghucu</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Ruangan</label>
                    <input type="text" name="ruangan" class="form-input" placeholder="Contoh: Ruang 101">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-input" rows="2" placeholder="Alamat lengkap"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 border rounded-xl hover:bg-gray-50">Batal</button>
                <button type="submit" name="add_user" class="btn-primary text-white px-6 py-2 rounded-xl">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT USER -->
<div id="editModal" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gradient-to-r from-[#4A6FA5] to-[#3D5A80] px-6 py-4 flex justify-between items-center rounded-t-2xl">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-user-edit mr-2"></i> Edit User
            </h2>
            <button onclick="closeEditModal()" class="text-white/80 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="id_user" id="edit_id_user">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-input" placeholder="Kosongkan jika tidak diubah">
                </div>
                <div>
                    <label class="form-label">Role *</label>
                    <select name="role" id="edit_role" class="form-input" required>
                        <option value="admin">Admin</option>
                        <option value="guru">Guru</option>
                        <option value="siswa">Siswa</option>
                        <option value="pengawas">Pengawas</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-input">
                        <option value="">Pilih</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" id="edit_tempat_lahir" class="form-input">
                </div>
                <div>
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-input">
                </div>
                <div>
                    <label class="form-label">Agama</label>
                    <select name="agama" id="edit_agama" class="form-input">
                        <option value="">Pilih</option>
                        <option value="Islam">Islam</option>
                        <option value="Kristen">Kristen</option>
                        <option value="Katolik">Katolik</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                        <option value="Konghucu">Konghucu</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Ruangan</label>
                    <input type="text" name="ruangan" id="edit_ruangan" class="form-input" placeholder="Contoh: Ruang 101">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" id="edit_alamat" class="form-input" rows="2"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 border rounded-xl hover:bg-gray-50">Batal</button>
                <button type="submit" name="edit_user" class="btn-primary text-white px-6 py-2 rounded-xl">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
        document.getElementById('addModal').classList.add('flex');
    }
    
    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
        document.getElementById('addModal').classList.remove('flex');
    }
    
    function openEditModal(user) {
        document.getElementById('edit_id_user').value = user.id_user;
        document.getElementById('edit_nama_lengkap').value = user.nama_lengkap || '';
        document.getElementById('edit_role').value = user.role || 'admin';
        document.getElementById('edit_jenis_kelamin').value = user.jenis_kelamin || '';
        document.getElementById('edit_tempat_lahir').value = user.tempat_lahir || '';
        document.getElementById('edit_tanggal_lahir').value = user.tanggal_lahir || '';
        document.getElementById('edit_agama').value = user.agama || '';
        document.getElementById('edit_ruangan').value = user.ruangan || '';
        document.getElementById('edit_alamat').value = user.alamat || '';
        
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    }
    
    function confirmDelete(id, nama) {
        Swal.fire({
            title: 'Hapus User?',
            text: `Apakah Anda yakin ingin menghapus user "${nama}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `?delete=${id}`;
            }
        });
    }
    
    function confirmResetPassword(id, nama) {
        Swal.fire({
            title: 'Reset Password?',
            text: `Reset password untuk user "${nama}" menjadi 123456?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `?reset_password=${id}`;
            }
        });
    }
</script>
</body>
</html>