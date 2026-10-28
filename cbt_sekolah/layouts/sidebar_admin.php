<aside class="w-64 bg-gray-900 text-white h-screen fixed left-0 top-0 overflow-y-auto transition-transform transform md:translate-x-0 -translate-x-full md:block z-50 shadow-2xl" id="sidebar">
    <div class="p-6 border-b border-gray-800 flex items-center justify-center">
        <h2 class="text-2xl font-bold text-blue-500 tracking-tighter">ADMIN<span class="text-white">SKAPAN</span></h2>
    </div>

    <nav class="mt-6 px-4 pb-10">
        <div class="space-y-1">
            <a href="dashboard.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-th-large w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="font-medium">Dashboard</span>
            </a>

            <div class="pt-6 pb-2 px-3 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Data Master</div>

            <a href="data_kelas.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-school w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="text-sm">Data Kelas</span>
            </a>

            <a href="data_mapel.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-book w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="text-sm">Mata Pelajaran</span>
            </a>

            <a href="data_siswa.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-user-graduate w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="text-sm">Data Siswa</span>
            </a>

            <a href="data_guru.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-user-tie w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="text-sm">Data Guru</span>
            </a>

            <div class="pt-6 pb-2 px-3 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Akademik</div>

            <a href="data_soal.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-folder-open w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="text-sm font-medium">Bank Soal</span>
            </a>

            <a href="rencana_ujian.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-calendar-check w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="text-sm font-medium">Rencana Ujian</span>
            </a>

            <a href="monitoring_ujian.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-desktop w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="text-sm font-medium">Monitoring Ujian</span>
            </a>

            <a href="nilai_ujian.php" class="flex items-center p-3 text-gray-300 hover:bg-blue-600 hover:text-white rounded-xl transition-all group">
                <i class="fas fa-file-invoice-dollar w-6 text-center mr-3 text-lg opacity-70 group-hover:opacity-100"></i>
                <span class="text-sm font-medium">Nilai Ujian</span>
            </a>

            <div class="pt-10">
                <a href="#" onclick="confirmLogout()" class="flex items-center p-3 text-red-400 hover:bg-red-600 hover:text-white rounded-xl transition-all group">
                    <i class="fas fa-sign-out-alt w-6 text-center mr-3 text-lg group-hover:translate-x-1 transition-transform"></i>
                    <span class="font-bold">Logout</span>
                </a>
            </div>
        </div>
    </nav>
</aside>
<?php if (isset($_SESSION['notif'])): ?>
    <script>
        Swal.fire({
            icon: '<?= $_SESSION['notif']['type'] ?>',
            title: '<?= $_SESSION['notif']['title'] ?>',
            text: '<?= $_SESSION['notif']['text'] ?>',
            confirmButtonColor: '#3498DB',
            showConfirmButton: true,
            timer: 3000 // Otomatis hilang dalam 3 detik
        });
    </script>
    <?php unset($_SESSION['notif']); ?>
<?php endif; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmLogout() {
        Swal.fire({
            title: 'Yakin ingin keluar?',
            text: "Sesi Anda di sistem CBT SKAPAN akan diakhiri.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Keluar!',
            cancelButtonText: 'Batal',
            border: 'none',
            borderRadius: '20px'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../../logout.php";
            }
        })
    }
</script>