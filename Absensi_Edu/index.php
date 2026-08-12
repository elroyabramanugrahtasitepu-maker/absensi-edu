<?php
// 1. Kunci Timezone PHP ke WIB (Jakarta) secara absolut
date_default_timezone_set('Asia/Jakarta');

// 2. Koneksi ke database
$host = 'localhost';
$user = 'root'; // Sesuaikan jika ada password di XAMPP
$pass = '';
$db   = 'db_absensi';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $divisi = mysqli_real_escape_string($conn, $_POST['divisi']);
    $tipe_absen = mysqli_real_escape_string($conn, $_POST['tipe_absen']);
    $latitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);
    
    // Pastikan lokasi terdeteksi
    if (empty($latitude) || empty($longitude)) {
        $pesan = "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6 shadow-sm flex items-start gap-3'>
                    <i class='fas fa-exclamation-triangle mt-1 text-lg'></i>
                    <div>
                        <strong class='block font-semibold'>Akses Lokasi Ditolak!</strong>
                        <span class='text-sm'>Sistem belum mendeteksi lokasi (GPS) Anda. Pastikan Anda mengizinkan akses lokasi di browser.</span>
                    </div>
                  </div>";
    } else {
        // Ambil tanggal hari ini dan waktu sekarang (WIB)
        $tanggal_hari_ini = date('Y-m-d');
        $waktu_sekarang = date('Y-m-d H:i:s');
        
        // --- LOGIKA CEK ABSEN GANDA ---
        // Cek apakah sudah ada data absen dengan nama, tipe (Mulai/Akhir), dan di hari yang sama
        $cek_query = "SELECT id FROM tabel_absensi 
                      WHERE nama = '$nama' 
                      AND tipe_absen = '$tipe_absen' 
                      AND DATE(waktu_absen) = '$tanggal_hari_ini'";
        $cek_result = mysqli_query($conn, $cek_query);
        
        // Jika data ditemukan (jumlah baris > 0), berarti sudah absen
        if (mysqli_num_rows($cek_result) > 0) {
            $pesan = "<div class='bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-xl mb-6 shadow-sm flex items-start gap-3'>
                        <i class='fas fa-hand-paper mt-1 text-lg text-amber-500'></i>
                        <div>
                            <strong class='block font-semibold'>Anda Sudah Absen!</strong>
                            <span class='text-sm'>Anda sudah melakukan presensi <b>$tipe_absen</b> hari ini. Tidak perlu mengirim ulang.</span>
                        </div>
                      </div>";
        } else {
            // Jika belum absen, jalankan proses Insert
            $query = "INSERT INTO tabel_absensi (nama, divisi, tipe_absen, latitude, longitude, waktu_absen) 
                      VALUES ('$nama', '$divisi', '$tipe_absen', '$latitude', '$longitude', '$waktu_sekarang')";
                      
            if (mysqli_query($conn, $query)) {
                $pesan = "<div class='bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl mb-6 shadow-sm flex items-start gap-3'>
                            <i class='fas fa-check-circle mt-1 text-lg'></i>
                            <div>
                                <strong class='block font-semibold'>Absen Berhasil!</strong>
                                <span class='text-sm'>Absen<b></b> Anda telah tercatat pada sistem ($waktu_sekarang WIB).</span>
                            </div>
                          </div>";  
            } else {    
                $pesan = "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6 shadow-sm'>
                            <i class='fas fa-times-circle mr-2'></i> Terjadi kesalahan database: " . mysqli_error($conn) . "
                          </div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Real-time</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 via-white to-indigo-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 md:p-10 rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 w-full max-w-lg relative overflow-hidden">
        
        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-bl-full -z-10 opacity-60"></div>
        
        <div class="mb-8 flex items-start justify-between">
            <div>
                <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-600 text-white rounded-xl mb-4 shadow-lg shadow-indigo-200">
                    <i class="fas fa-fingerprint text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Presensi Harian</h2>
                <p class="text-slate-500 text-sm mt-1">Pastikan GPS aktif sebelum melakukan absensi.</p>
            </div>
            <a href="admin.php" class="flex items-center justify-center w-10 h-10 bg-slate-50 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition-all duration-200" title="Masuk Panel Admin">
                <i class="fas fa-user-cog"></i>
            </a>
        </div>

        <?= $pesan; ?>

        <form action="" method="POST" class="space-y-6" onsubmit="return validateForm()">
            
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="far fa-user"></i>
                    </div>
                    <select name="nama" class="w-full bg-slate-50 border border-slate-200 text-slate-700 rounded-xl pl-10 p-3 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none appearance-none cursor-pointer" required>
                        <option value="">-- Pilih Nama Anda --</option>
                        <option value="(Frau Etna)">Etnadea</option>
                        <option value="(Frau Dhea)">Adhea Diva Ardila</option>
                        <option value="(Herr Rama)">Mohamad Rama Dani</option>
                        <option value="(Frau Alvidya)">Alvidya Utami</option>
                        <option value="(Alya Maulidiya)">Alya Maulidiya</option>
                        <option value="(Tomi Pratama)">Tomi Pratama</option>
                        <option value="(EL roy Abram Anugrahta Sitepu)">Elroy Abram Anugrahta Sitepu</option>
                        <option value="(hahhahahahahahah)">ahhahahahahh</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Divisi / Peran</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="far fa-id-badge"></i>
                    </div>
                    <select name="divisi" class="w-full bg-slate-50 border border-slate-200 text-slate-700 rounded-xl pl-10 p-3 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none appearance-none cursor-pointer" required>
                        <option value="">Pilih Divisi...</option>
                        <option value="IT">IT</option>
                        <option value="Finance">Finance</option>
                        <option value="Staff">Staff</option>
                        <option value="Pengajar">Pengajar</option>
                        <option value="Marketing">Marketing</option>
                        
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tipe Kehadiran</label>
                <div class="flex gap-4">
                    <label class="flex-1 cursor-pointer group">
                        <input type="radio" name="tipe_absen" value="Mulai" class="peer sr-only" required>
                        <div class="text-center p-3 border-2 border-slate-100 bg-white rounded-xl peer-checked:bg-indigo-50 peer-checked:border-indigo-500 peer-checked:text-indigo-700 hover:border-indigo-200 transition-all duration-200">
                            <div class="w-8 h-8 mx-auto bg-slate-50 peer-checked:bg-indigo-100 rounded-full flex items-center justify-center mb-2 text-slate-400 peer-checked:text-indigo-600 transition-colors">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-600 peer-checked:text-indigo-700">Check-in</span>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer group">
                        <input type="radio" name="tipe_absen" value="Akhir" class="peer sr-only">
                        <div class="text-center p-3 border-2 border-slate-100 bg-white rounded-xl peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-700 hover:border-rose-200 transition-all duration-200">
                            <div class="w-8 h-8 mx-auto bg-slate-50 peer-checked:bg-rose-100 rounded-full flex items-center justify-center mb-2 text-slate-400 peer-checked:text-rose-600 transition-colors">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-600 peer-checked:text-rose-700">Check-out</span>
                        </div>
                    </label>
                </div>
            </div>

            <div id="location-box" class="bg-amber-50/80 border border-amber-200 p-3.5 rounded-xl flex items-start gap-3 transition-colors duration-300">
                <div id="location-icon" class="mt-0.5 text-amber-500 animate-pulse">
                    <i class="fas fa-location-crosshairs text-lg"></i>
                </div>
                <div class="flex-1">
                    <span class="font-semibold text-slate-800 text-sm block mb-0.5">Status Lokasi GPS</span>
                    <span id="lokasi-status" class="text-amber-700 text-xs">Sedang mencari titik koordinat Anda...</span>
                </div>
            </div>

            <button type="submit" id="btnSubmit" class="w-full bg-indigo-600 text-white font-semibold py-3.5 rounded-xl hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all duration-200 disabled:opacity-50 disabled:hover:shadow-none disabled:cursor-not-allowed flex items-center justify-center gap-2" disabled>
                <i class="fas fa-paper-plane text-sm"></i> Kirim Presensi
            </button>
        </form>
    </div>

    <script>
        // DOM Elements
        const latInput = document.getElementById('latitude');
        const longInput = document.getElementById('longitude');
        const statusText = document.getElementById('lokasi-status');
        const locationBox = document.getElementById('location-box');
        const locationIcon = document.getElementById('location-icon');
        const btnSubmit = document.getElementById('btnSubmit');

        function getLocation() {
            if (navigator.geolocation) {
                const options = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                };
                navigator.geolocation.getCurrentPosition(showPosition, showError, options);
            } else {
                updateUIError("Browser Anda tidak mendukung Geolocation.");
            }
        }

        function showPosition(position) {
            latInput.value = position.coords.latitude;
            longInput.value = position.coords.longitude;
            
            // Ubah UI menjadi status sukses (Hijau)
            locationBox.className = "bg-emerald-50 border border-emerald-200 p-3.5 rounded-xl flex items-start gap-3 transition-colors duration-300";
            locationIcon.className = "mt-0.5 text-emerald-500";
            locationIcon.innerHTML = "<i class='fas fa-map-marker-alt text-lg'></i>";
            statusText.className = "text-emerald-700 text-xs mt-0.5 block";
            statusText.innerHTML = "Lokasi ditemukan! Siap melakukan absen.";
            
            btnSubmit.removeAttribute("disabled"); 
        }

        function showError(error) {
            btnSubmit.setAttribute("disabled", "true");
            let msg = "";
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    msg = "Akses lokasi ditolak. Izinkan GPS di browser Anda.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = "Informasi lokasi tidak tersedia.";
                    break;
                case error.TIMEOUT:
                    msg = "Waktu permintaan akses lokasi habis.";
                    break;
                case error.UNKNOWN_ERROR:
                    msg = "Terjadi kesalahan yang tidak diketahui.";
                    break;
            }
            updateUIError(msg);
        }

        function updateUIError(pesanError) {
            // Ubah UI menjadi status error (Merah)
            locationBox.className = "bg-rose-50 border border-rose-200 p-3.5 rounded-xl flex items-start gap-3 transition-colors duration-300";
            locationIcon.className = "mt-0.5 text-rose-500";
            locationIcon.innerHTML = "<i class='fas fa-exclamation-circle text-lg'></i>";
            statusText.className = "text-rose-700 text-xs mt-0.5 block";
            statusText.innerHTML = pesanError;
        }

        function validateForm() {
            if (latInput.value === "" || longInput.value === "") {
                alert("Sistem belum mendeteksi lokasi Anda. Pastikan Anda mengizinkan akses lokasi (GPS) di browser!");
                return false;
            }
            return true;
        }

        // Panggil fungsi pencarian lokasi saat halaman pertama kali dimuat
        window.onload = getLocation;
    </script>
</body>
</html>