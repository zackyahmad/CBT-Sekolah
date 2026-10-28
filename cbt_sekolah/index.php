<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') header("Location: pages/admin/dashboard.php");
    else if ($_SESSION['role'] == 'guru') header("Location: pages/guru/dashboard.php");
    else if ($_SESSION['role'] == 'pengawas') header("Location: pages/pengawas/dashboard.php");
    else if ($_SESSION['role'] == 'siswa') header("Location: pages/siswa/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login CBT - SMK Negeri 8 Bandar Lampung</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #2B3A67 0%, #496A81 50%, #66999B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Background decorative elements */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 70%;
            height: 70%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        /* Main Card */
        .login-container {
            width: 100%;
            max-width: 440px;
            z-index: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 44px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.4);
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            position: relative;
        }

        .logo-school {
            width: 85px;
            height: 85px;
            object-fit: contain;
            border-radius: 22px;
            background: linear-gradient(135deg, #F8F4E9, #E8E1D3);
            padding: 14px;
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.15);
        }

        .logo-placeholder {
            width: 85px;
            height: 85px;
            background: linear-gradient(135deg, #FFC482, #DDB7AB);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
            color: #2B3A67;
        }

        .logo-section h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #2B3A67, #496A81);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .logo-section p {
            font-size: 13px;
            color: #66999B;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2E3B4E;
            margin-bottom: 8px;
        }

        .form-group label i {
            margin-right: 8px;
            color: #FFC482;
            width: 18px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #B3AF8F;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 15px 18px 15px 48px;
            border: 1.5px solid #E8E1D3;
            border-radius: 18px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: #FFFFFF;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFC482;
            box-shadow: 0 0 0 4px rgba(255, 196, 130, 0.15);
        }

        .form-control::placeholder {
            color: #C4C0B0;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2B3A67, #496A81);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 12px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1F2D52, #3A5A6E);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(43, 58, 103, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #E8E1D3;
        }

        .login-footer p {
            font-size: 11px;
            color: #B3AF8F;
            letter-spacing: 0.5px;
        }

        .login-footer p i {
            margin: 0 6px;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
            .logo-school {
                width: 70px;
                height: 70px;
            }
            .logo-section h1 {
                font-size: 24px;
            }
            .form-control {
                padding: 13px 16px 13px 42px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="logo-section">
            <div class="logo-wrapper">
                <img src="assets/img/logo-smk8.png" alt="Logo SMK Negeri 8 Bandar Lampung" class="logo-school" onerror="this.outerHTML='<div class=\'logo-placeholder\'><i class=\'fas fa-school\'></i></div>'">
            </div>
            <h1>CBT Ujian Sekolah</h1>
            <p>SMK Negeri 8 Bandar Lampung</p>
        </div>

        <form action="proses/login_proses.php" method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="text" name="username" required class="form-control" placeholder="Masukkan username Anda...">
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-key"></i>
                    <input type="password" name="password" required class="form-control" placeholder="Masukkan password...">
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right-to-bracket"></i> MASUK SEKARANG
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; CIDIKNA    <i class="fas fa-graduation-cap"></i> SMK Negeri 8 Bandar Lampung</p>
        </div>
    </div>
</div>

<script>
    <?php if (isset($_SESSION['notif'])): ?>
        Swal.fire({
            icon: '<?= $_SESSION['notif']['type']; ?>',
            title: '<?= $_SESSION['notif']['title']; ?>',
            text: '<?= $_SESSION['notif']['text']; ?>',
            confirmButtonColor: '#2B3A67',
            confirmButtonText: 'OK',
            background: '#FFFFFF',
            backdrop: true
        });
        <?php unset($_SESSION['notif']); ?>
    <?php endif; ?>
    
    // Cek error di URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'akses') {
        Swal.fire({
            icon: 'error',
            title: 'Akses Ditolak!',
            text: 'Anda tidak memiliki akses ke halaman tersebut!',
            confirmButtonColor: '#E58C7C',
            confirmButtonText: 'OK'
        });
    }
</script>

</body>
</html>