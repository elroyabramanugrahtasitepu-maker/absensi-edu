<?php
// Set Timezone
date_default_timezone_set('Asia/Jakarta');

// Koneksi ke database
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_absensi';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$pesan = "";

// Cek apakah ada permintaan penghapusan data
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $hapus_query = "DELETE FROM tabel_absensi WHERE id = $id_hapus";
    
    if (mysqli_query($conn, $hapus_query)) {
        $pesan = "<div class='bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-400 p-4 rounded-xl mb-6 flex justify-between items-center shadow-sm'>
                    <span class='flex items-center font-medium'><i class='fas fa-check-circle text-xl mr-3'></i> Data absensi berhasil dihapus!</span>
                    <a href='admin.php' class='text-emerald-500 hover:text-emerald-800 dark:hover:text-emerald-300 transition-colors bg-white dark:bg-emerald-900/50 hover:bg-emerald-100 dark:hover:bg-emerald-800 p-1.5 rounded-lg'><i class='fas fa-times'></i></a>
                  </div>";  
    } else {
        $pesan = "<div class='bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800/50 text-red-700 dark:text-red-400 p-4 rounded-xl mb-6 flex items-center shadow-sm'>
                    <i class='fas fa-exclamation-circle text-xl mr-3'></i> Gagal menghapus data.
                  </div>";
    }
}

// Ambil semua data absensi
$query = "SELECT * FROM tabel_absensi ORDER BY waktu_absen DESC";
$result = mysqli_query($conn, $query);

// 1. Siapkan keranjang utama untuk Tab
$data_tab = [
    'Etna Dea' => [],
    'Adhea Diva Ardila' => [],
    'Mohamad Rama Dani' => [],
    'Alvidya Utami' => [],
    'IT' => [],
    'Finance' => [],
    'Staff' => [],
    'Marketing' =>[]
];

// Siapkan keranjang khusus untuk Laporan Jam Kerja
$laporan_kerja = [];

// 2. Kelompokkan data dari database ke keranjangnya masing-masing
while ($row = mysqli_fetch_assoc($result)) {
    $nama = $row['nama'];
    $divisi = $row['divisi'];
    $kategori_tab = '';
    
    // Tentukan kategori tab berdasarkan divisi & nama
    if ($divisi == 'Pengajar') {
        if (stripos($nama, 'Etna') !== false) {
            $kategori_tab = 'Etna Dea';
        } elseif (stripos($nama, 'Dhea') !== false) {
            $kategori_tab = 'Adhea Diva Ardila';
        } elseif (stripos($nama, 'Rama') !== false) {
            $kategori_tab = 'Mohamad Rama Dani';
        } elseif (stripos($nama, 'Alvidya') !== false) {
            $kategori_tab = 'Alvidya Utami';
        } elseif (stripos($nama, 'Alya') !== false) {
            $kategori_tab = 'Alya Maulidiya';
        } elseif (stripos($nama, 'Tomi') !== false) {
            $kategori_tab = 'Tomi Pratama';
        } elseif (stripos($nama, 'roy') !== false || stripos($nama, 'Elroy') !== false) {
            $kategori_tab = 'Elroy Abram';    
        } else {
            $kategori_tab = 'Pengajar Lainnya';
        }
    } else {
        $kategori_tab = ($divisi == 'Staff') ? 'Staff' : $divisi;
    }

    // Masukkan data utuh ke keranjang tab
    if (!isset($data_tab[$kategori_tab])) $data_tab[$kategori_tab] = [];
    $data_tab[$kategori_tab][] = $row;

    // --- LOGIKA REKAP JAM KERJA HARIAN ---
    $tanggal = date('Y-m-d', strtotime($row['waktu_absen']));
    
    if (!isset($laporan_kerja[$kategori_tab])) {
        $laporan_kerja[$kategori_tab] = [];
    }
    if (!isset($laporan_kerja[$kategori_tab][$nama])) {
        $laporan_kerja[$kategori_tab][$nama] = [];
    }
    if (!isset($laporan_kerja[$kategori_tab][$nama][$tanggal])) {
        $laporan_kerja[$kategori_tab][$nama][$tanggal] = ['masuk' => null, 'keluar' => null];
    }

    if ($row['tipe_absen'] == 'Mulai') {
        if (is_null($laporan_kerja[$kategori_tab][$nama][$tanggal]['masuk']) || $row['waktu_absen'] < $laporan_kerja[$kategori_tab][$nama][$tanggal]['masuk']) {
            $laporan_kerja[$kategori_tab][$nama][$tanggal]['masuk'] = $row['waktu_absen'];
        }
    } elseif ($row['tipe_absen'] == 'Akhir') {
        if (is_null($laporan_kerja[$kategori_tab][$nama][$tanggal]['keluar']) || $row['waktu_absen'] > $laporan_kerja[$kategori_tab][$nama][$tanggal]['keluar']) {
            $laporan_kerja[$kategori_tab][$nama][$tanggal]['keluar'] = $row['waktu_absen'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Dasbor Absensi</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', // Enable dark mode via class
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Kustomisasi scrollbar yang adaptif mode gelap/terang */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 transition-colors duration-300 min-h-screen p-4 md:p-8">

    <div class="max-w-7xl mx-auto">
        <div class="bg-white dark:bg-slate-800 p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 transition-colors">
            <div class="flex items-center gap-4">
                <div class="bg-indigo-100 dark:bg-indigo-900/50 p-3.5 rounded-xl text-indigo-600 dark:text-indigo-400">
                    <i class="fas fa-chart-pie text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Dasbor Kehadiran</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Kelola dan pantau aktivitas presensi secara real-time.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 w-full md:w-auto">
                <button id="theme-toggle" type="button" class="flex-shrink-0 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl p-2.5 transition-colors focus:outline-none">
                    <i id="theme-toggle-dark-icon" class="hidden fas fa-moon text-lg w-5 h-5 flex items-center justify-center"></i>
                    <i id="theme-toggle-light-icon" class="hidden fas fa-sun text-lg w-5 h-5 flex items-center justify-center"></i>
                </button>
                
                <a href="index.php" class="flex-1 md:flex-none inline-flex justify-center items-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-slate-700 dark:text-slate-200 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-sm">
                    <i class="fas fa-external-link-alt mr-2.5 text-indigo-500 dark:text-indigo-400"></i>Ke Absensi
                </a>
            </div>
        </div>

        <?= $pesan; ?>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden transition-colors">
            
            <div class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700/50 p-4 md:p-6 overflow-x-auto">
                <div class="flex gap-2 whitespace-nowrap pb-2 md:pb-0">
                    <?php 
                    $is_first = true;
                    foreach ($data_tab as $nama_kategori => $data): 
                        $tab_id = str_replace(' ', '_', $nama_kategori);
                        
                        // Logika kelas CSS untuk Tab berdasarkan status (Aktif/Non-aktif) dan Mode
                        $btn_class = $is_first ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200 dark:shadow-none' : 'bg-transparent text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700';
                        $badge_class = $is_first ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-300';
                    ?>
                        <button onclick="openTab(event, 'tab-<?= $tab_id ?>')" class="tab-btn <?= $btn_class ?> px-5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 flex items-center gap-2 focus:outline-none">
                            <?= htmlspecialchars($nama_kategori); ?>
                            <span class="tab-badge <?= $badge_class ?> text-xs py-0.5 px-2.5 rounded-full font-semibold transition-colors">
                                <?= count($data); ?>
                            </span>
                        </button>
                    <?php 
                        $is_first = false;
                    endforeach; 
                    ?>
                </div>
            </div>

            <div class="p-4 md:p-6">
                <?php 
                $is_first_content = true;
                foreach ($data_tab as $nama_kategori => $data): 
                    $tab_id = str_replace(' ', '_', $nama_kategori);
                    $display_class = $is_first_content ? 'block' : 'hidden';
                ?>
                    <div id="tab-<?= $tab_id ?>" class="tab-content <?= $display_class ?>">
                        
                        <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden mb-8 transition-colors">
                            <div class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 p-4">
                                <h3 class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                    <i class="fas fa-list-ul text-indigo-500 dark:text-indigo-400"></i> Histori Semua Aktivitas
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse min-w-[700px]">
                                    <thead>
                                        <tr class="bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider font-semibold border-b border-slate-100 dark:border-slate-700">
                                            <th class="py-4 px-5 w-16 text-center">No</th>
                                            <th class="py-4 px-5">Karyawan / Pengajar</th>
                                            <th class="py-4 px-5">Aktivitas</th>
                                            <th class="py-4 px-5">Waktu (WIB)</th>
                                            <th class="py-4 px-5">Lokasi</th>
                                            <th class="py-4 px-5 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-slate-600 dark:text-slate-300 text-sm divide-y divide-slate-100 dark:divide-slate-700/50 bg-white dark:bg-slate-800">
                                        <?php 
                                        if (count($data) > 0) {
                                            $no = 1;
                                            foreach ($data as $row) {
                                                $is_mulai = ($row['tipe_absen'] == 'Mulai');
                                                $badge_bg = $is_mulai ? 'bg-indigo-50 border-indigo-200 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-400' : 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-900/30 dark:border-rose-800 dark:text-rose-400';
                                                $icon_absen = $is_mulai ? 'fa-sign-in-alt' : 'fa-sign-out-alt';
                                                $waktu = date('d M Y, H:i:s', strtotime($row['waktu_absen']));
                                        ?>
                                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition-colors group">
                                                    <td class="py-4 px-5 text-center font-medium text-slate-400 dark:text-slate-500"><?= $no++; ?></td>
                                                    <td class="py-4 px-5">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-300 font-bold text-xs uppercase flex-shrink-0">
                                                                <?= substr(preg_replace('/[^a-zA-Z]/', '', $row['nama']), 0, 1); ?>
                                                            </div>
                                                            <span class="font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap"><?= htmlspecialchars($row['nama']); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-5 whitespace-nowrap">
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border text-xs font-semibold <?= $badge_bg; ?>">
                                                            <i class="fas <?= $icon_absen; ?> text-[10px]"></i> <?= $row['tipe_absen']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-5 text-slate-500 dark:text-slate-400 font-medium whitespace-nowrap">
                                                        <i class="far fa-clock mr-1.5 opacity-70"></i><?= $waktu; ?>
                                                    </td>
                                                    <td class="py-4 px-5 whitespace-nowrap">
                                                        <a href="https://www.google.com/maps?q=<?= $row['latitude']; ?>,<?= $row['longitude']; ?>" target="_blank" class="inline-flex items-center gap-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-500 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors duration-200">
                                                            <i class="fas fa-map-marker-alt"></i> Buka Maps
                                                        </a>
                                                    </td>
                                                    <td class="py-4 px-5 text-center">
                                                        <a href="admin.php?hapus=<?= $row['id']; ?>" onclick="return konfirmasiHapus();" class="inline-flex items-center justify-center w-8 h-8 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-rose-500 dark:text-rose-400 rounded-lg hover:bg-rose-500 hover:text-white dark:hover:bg-rose-500 dark:hover:text-white dark:hover:border-rose-500 transition-all duration-200 shadow-sm" title="Hapus Data">
                                                            <i class="fas fa-trash-alt text-xs"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                        <?php 
                                            }
                                        } else {
                                        ?>
                                            <tr>
                                                <td colspan="6" class="py-12 text-center">
                                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700/50 mb-4">
                                                        <i class="fas fa-inbox text-2xl text-slate-300 dark:text-slate-500"></i>
                                                    </div>
                                                    <h3 class="text-slate-700 dark:text-slate-300 font-semibold mb-1">Belum Ada Catatan</h3>
                                                    <p class="text-slate-500 dark:text-slate-400 text-sm max-w-sm mx-auto">Data kehadiran untuk <span class="font-semibold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($nama_kategori); ?></span> masih kosong.</p>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="border border-indigo-200 dark:border-indigo-800/50 rounded-xl overflow-hidden shadow-sm transition-colors">
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-200 dark:border-indigo-800/50 p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                <h3 class="font-bold text-indigo-900 dark:text-indigo-300 flex items-center gap-2">
                                    <i class="fas fa-business-time text-indigo-600 dark:text-indigo-400"></i> Rekap Jam Kerja Harian
                                </h3>
                                <span class="text-[11px] sm:text-xs font-semibold tracking-wide text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/50 px-3 py-1.5 rounded-full uppercase"></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse min-w-[600px]">
                                    <thead>
                                        <tr class="bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider font-semibold border-b border-slate-100 dark:border-slate-700/50">
                                            <th class="py-3 px-5 border-r border-slate-100 dark:border-slate-700/50">Nama</th>
                                            <th class="py-3 px-5 border-r border-slate-100 dark:border-slate-700/50">Tanggal</th>
                                            <th class="py-3 px-5 border-r border-slate-100 dark:border-slate-700/50 text-center">Masuk</th>
                                            <th class="py-3 px-5 border-r border-slate-100 dark:border-slate-700/50 text-center">Keluar</th>
                                            <th class="py-3 px-5 text-center bg-slate-50 dark:bg-slate-800/50">Total Durasi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-slate-700 dark:text-slate-300 text-sm divide-y divide-slate-100 dark:divide-slate-700/50 bg-white dark:bg-slate-800">
                                        <?php
                                        if (isset($laporan_kerja[$nama_kategori]) && count($laporan_kerja[$nama_kategori]) > 0) {
                                            foreach ($laporan_kerja[$nama_kategori] as $nama_pegawai => $data_harian) {
                                                krsort($data_harian);
                                                foreach ($data_harian as $tgl => $waktu) {
                                                    $tgl_format = date('d M Y', strtotime($tgl));
                                                    $jam_masuk = $waktu['masuk'] ? date('H:i:s', strtotime($waktu['masuk'])) : '-';
                                                    $jam_keluar = $waktu['keluar'] ? date('H:i:s', strtotime($waktu['keluar'])) : '-';
                                                    
                                                    // Styling badge jam masuk & keluar
                                                    $ui_jam_masuk = $waktu['masuk'] ? "<span class='bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 px-2 py-1 rounded text-xs font-mono font-bold'>{$jam_masuk}</span>" : "<span class='text-slate-400 dark:text-slate-500'>-</span>";
                                                    
                                                    $ui_jam_keluar = $waktu['keluar'] ? "<span class='bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-100 dark:border-rose-800 px-2 py-1 rounded text-xs font-mono font-bold'>{$jam_keluar}</span>" : "<span class='text-slate-400 dark:text-slate-500'>-</span>";

                                                    // Hitung Durasi Kerja
                                                    $durasi_teks = "<span class='text-amber-500 dark:text-amber-400 font-medium italic text-xs'>Belum Check-out</span>";
                                                    
                                                    if ($waktu['masuk'] && $waktu['keluar']) {
                                                        $selisih = strtotime($waktu['keluar']) - strtotime($waktu['masuk']);
                                                        if ($selisih > 0) {
                                                            $jam = floor($selisih / 3600);
                                                            $menit = floor(($selisih % 3600) / 60);
                                                            $durasi_teks = "<span class='text-emerald-600 dark:text-emerald-400 font-bold text-base'>{$jam} <span class='text-[10px] uppercase tracking-wide opacity-80'>Jam</span> {$menit} <span class='text-[10px] uppercase tracking-wide opacity-80'>Mnt</span></span>";
                                                        } else {
                                                            $durasi_teks = "<span class='text-rose-500 dark:text-rose-400 font-medium text-xs'>Data Error</span>";
                                                        }
                                                    } elseif (!$waktu['masuk'] && $waktu['keluar']) {
                                                        $durasi_teks = "<span class='text-rose-500 dark:text-rose-400 font-medium italic text-xs'>Lewati Check-in</span>";
                                                    }
                                        ?>
                                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                                        <td class="py-3 px-5 font-semibold text-slate-800 dark:text-slate-200 border-r border-slate-100 dark:border-slate-700/50 whitespace-nowrap"><?= htmlspecialchars($nama_pegawai); ?></td>
                                                        <td class="py-3 px-5 border-r border-slate-100 dark:border-slate-700/50 text-slate-500 dark:text-slate-400 whitespace-nowrap"><i class="far fa-calendar-alt opacity-70 mr-2"></i><?= $tgl_format; ?></td>
                                                        <td class="py-3 px-5 text-center border-r border-slate-100 dark:border-slate-700/50"><?= $ui_jam_masuk; ?></td>
                                                        <td class="py-3 px-5 text-center border-r border-slate-100 dark:border-slate-700/50"><?= $ui_jam_keluar; ?></td>
                                                        <td class="py-3 px-5 text-center bg-slate-50/50 dark:bg-slate-800/30 whitespace-nowrap"><?= $durasi_teks; ?></td>
                                                    </tr>
                                        <?php
                                                }
                                            }
                                        } else {
                                        ?>
                                            <tr>
                                                <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500 italic text-sm">Belum ada histori check-in/check-out.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                <?php 
                    $is_first_content = false;
                endforeach; 
                ?>
            </div>
        </div>
    </div>

    <script>
        // --- SCRIPT DARK MODE TOGGLE ---
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // Tampilkan ikon yang sesuai saat dimuat
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');
        themeToggleBtn.addEventListener('click', function() {
            // Toggle icon
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            // Set atau hapus di localStorage dan class HTML
            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            } else {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            }
        });

        // --- SCRIPT TAB NAVIGASI ---
        function openTab(evt, tabId) {
            let tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("block");
                tabContents[i].classList.add("hidden");
            }

            let tabBtns = document.getElementsByClassName("tab-btn");
            let tabBadges = document.getElementsByClassName("tab-badge");
            for (let i = 0; i < tabBtns.length; i++) {
                // Kelas saat tab TIDAK aktif (memperhitungkan mode gelap/terang)
                tabBtns[i].className = "tab-btn bg-transparent text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 px-5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 flex items-center gap-2 focus:outline-none";
                tabBadges[i].className = "tab-badge bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-300 text-xs py-0.5 px-2.5 rounded-full font-semibold transition-colors";
            }

            document.getElementById(tabId).classList.remove("hidden");
            document.getElementById(tabId).classList.add("block");

            let currentBtn = evt.currentTarget;
            // Kelas saat tab AKTIF (memperhitungkan mode gelap/terang)
            currentBtn.className = "tab-btn bg-indigo-600 text-white shadow-md shadow-indigo-200 dark:shadow-none px-5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 flex items-center gap-2 focus:outline-none";
            
            let currentBadge = currentBtn.querySelector('.tab-badge');
            currentBadge.className = "tab-badge bg-white/20 text-white text-xs py-0.5 px-2.5 rounded-full font-semibold transition-colors";
        }

        // --- SCRIPT KONFIRMASI HAPUS ---
        function konfirmasiHapus() {
            let kodeInput = prompt("PERINGATAN: Tindakan ini tidak bisa dibatalkan!\nMasukkan kode keamanan untuk menghapus data:");
            
            if (kodeInput === null) {
                return false; 
            }
            if (kodeInput === "12345678") {
                return true; 
            } else {
                alert("Kode salah! Data gagal dihapus.");
                return false; 
            }
        }
    </script>
</body>
</html>