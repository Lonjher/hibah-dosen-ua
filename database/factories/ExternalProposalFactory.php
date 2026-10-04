<?php

namespace Database\Factories;

use App\Models\ExternalProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalProposalFactory extends Factory
{
    protected $model = ExternalProposal::class;

    /* ============================================================
     |  DEFINITION
     ============================================================ */

    public function definition(): array
    {
        $isResearch = fake()->boolean(60); // 60% research, 40% dedication

        $startDate = fake()->dateTimeBetween('-3 years', 'now');
        $hasEndDate = fake()->boolean(70);

        $endDate = $hasEndDate
            ? fake()->dateTimeBetween($startDate, '+1 year')
            : null;

        // Status berdasarkan tanggal
        $status = $this->determineStatus($startDate, $endDate);

        return [
            'user_id'         => User::factory(),
            'is_research'     => $isResearch,
            'title'           => $isResearch
                                    ? $this->researchTitle()
                                    : $this->dedicationTitle(),
            'scheme'          => $isResearch
                                    ? fake()->randomElement($this->researchSchemes())
                                    : fake()->randomElement($this->dedicationSchemes()),
            'funding_source'  => fake()->randomElement($this->fundingSources()),
            'role'            => fake()->randomElement(['leader', 'member']),
            'start_date'      => $startDate->format('Y-m-d'),
            'end_date'        => $endDate?->format('Y-m-d'),
            'status'          => $status,
            'fund_amount'     => $this->fundAmount($isResearch),
            'description'     => fake()->paragraph(3),
            'document_path'   => null, // diisi oleh state/afterCreating jika perlu
            'is_verified'     => fake()->boolean(60), // 60% verified
        ];
    }

    /* ============================================================
     |  STATE METHODS — TYPE
     ============================================================ */

    public function research(): static
    {
        return $this->state(fn () => [
            'is_research' => true,
            'title'       => $this->researchTitle(),
            'scheme'      => fake()->randomElement($this->researchSchemes()),
            'fund_amount' => fake()->numberBetween(20_000_000, 250_000_000),
        ]);
    }

    public function dedication(): static
    {
        return $this->state(fn () => [
            'is_research' => false,
            'title'       => $this->dedicationTitle(),
            'scheme'      => fake()->randomElement($this->dedicationSchemes()),
            'fund_amount' => fake()->numberBetween(10_000_000, 100_000_000),
        ]);
    }

    /* ============================================================
     |  STATE METHODS — VERIFICATION
     ============================================================ */

    public function verified(): static
    {
        return $this->state(fn () => ['is_verified' => true]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['is_verified' => false]);
    }

    /* ============================================================
     |  STATE METHODS — STATUS
     ============================================================ */

    public function ongoing(): static
    {
        return $this->state(fn () => [
            'status'     => 'ongoing',
            'start_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date'   => fake()->dateTimeBetween('+3 months', '+2 years')->format('Y-m-d'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status'     => 'completed',
            'start_date' => fake()->dateTimeBetween('-3 years', '-1 year')->format('Y-m-d'),
            'end_date'   => fake()->dateTimeBetween('-11 months', 'now')->format('Y-m-d'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status'     => 'cancelled',
            'start_date' => fake()->dateTimeBetween('-2 years', '-6 months')->format('Y-m-d'),
            'end_date'   => fake()->dateTimeBetween('-5 months', 'now')->format('Y-m-d'),
        ]);
    }

    /* ============================================================
     |  STATE METHODS — ROLE
     ============================================================ */

    public function leader(): static
    {
        return $this->state(fn () => ['role' => 'leader']);
    }

    public function member(): static
    {
        return $this->state(fn () => ['role' => 'member']);
    }

    /* ============================================================
     |  HELPERS — TITLES
     ============================================================ */

    protected function researchTitle(): string
    {
        $subjects = [
            'Sistem Deteksi Dini Banjir Berbasis IoT',
            'Analisis Sentimen Media Sosial untuk Kebijakan Publik',
            'Pengembangan Model Machine Learning untuk Prediksi Cuaca',
            'Sistem Rekomendasi Berbasis Collaborative Filtering',
            'Optimasi Rantai Pasok dengan Algoritma Genetika',
            'Deteksi Penyakit Tanaman Menggunakan Computer Vision',
            'Sistem Monitoring Kualitas Air Berbasis Sensor Nirkabel',
            'Analisis Big Data untuk Prediksi Permintaan Pasar',
            'Pengembangan Chatbot Bahasa Madura Berbasis NLP',
            'Sistem Navigasi Dalam Ruangan Berbasis Beacon',
            'Prediksi Harga Komoditas Pertanian dengan LSTM',
            'Klasifikasi Citra Satelit untuk Pemetaan Lahan',
            'Sistem Keamanan Jaringan Berbasis Deep Learning',
            'Optimasi Energi pada Data Center Menggunakan AI',
            'Analisis Perilaku Konsumen E-Commerce',
            'Sistem Deteksi Hoaks Berita Indonesia',
            'Pengembangan Sistem Pendukung Keputusan Pertanian',
            'Prediksi Kegagalan Mesin dengan Predictive Maintenance',
            'Sistem Pengenalan Wajah untuk Absensi Otomatis',
            'Analisis Kualitas Udara Berbasis Sensor LoRa',
        ];

        $prefixes = ['', 'Pengembangan ', 'Optimasi ', 'Analisis ', 'Implementasi '];

        $title = fake()->randomElement($subjects);

        // 30% chance untuk tambah prefix
        if (fake()->boolean(30)) {
            $title = fake()->randomElement($prefixes) . $title;
        }

        return $title . ' ' . fake()->year();
    }

    protected function dedicationTitle(): string
    {
        $subjects = [
            'Pelatihan Digital Marketing untuk UMKM',
            'Pemberdayaan Ekonomi Masyarakat Pesisir',
            'Pendampingan Petani Organik di Desa Binaan',
            'Pelatihan Coding untuk Siswa SMK',
            'Program Literasi Digital untuk Lansia',
            'Pemberdayaan Ibu Rumah Tangga Melalui Kerajinan',
            'Pendampingan UMKM Kuliner Berbasis Online',
            'Pelatihan Keamanan Siber untuk Perangkat Desa',
            'Program Konservasi Mangrove Bersama Warga',
            'Pendampingan Bank Sampah Komunitas',
            'Pelatihan Hidroponik untuk Pemuda Desa',
            'Pemberdayaan Santri Melalui Wirausaha Digital',
            'Program Deteksi Stunting Berbasis Aplikasi',
            'Pendampingan Legalitas UMKM Mikro',
            'Pelatihan Manajemen Keuangan untuk Pedagang',
            'Program Desa Wisata Berbasis Teknologi',
            'Pelatihan Penulisan Konten Kreatif',
            'Pendampingan Digitalisasi Posyandu',
            'Program Edukasi Gizi Seimbang Sekolah Dasar',
            'Pemberdayaan Perempuan Pengrajin Batik',
        ];

        $title = fake()->randomElement($subjects);

        // Tambah lokasi
        $locations = ['Desa Guluk-Guluk', 'Kecamatan Sumenep', 'Desa Prenduan', 'Kabupaten Pamekasan', 'Desa Batuan'];
        $title .= ' di ' . fake()->randomElement($locations);

        return $title;
    }

    /* ============================================================
     |  HELPERS — SCHEMES & SOURCES
     ============================================================ */

    protected function researchSchemes(): array
    {
        return [
            'Penelitian Dasar Unggulan Perguruan Tinggi',
            'Penelitian Terapan Unggulan Perguruan Tinggi',
            'Penelitian Kerjasama Antar Perguruan Tinggi',
            'Penelitian Dosen Pemula',
            'Penelitian Fundamental',
            'Penelitian Kerjasama Internasional',
            'Penelitian Strategis Nasional',
        ];
    }

    protected function dedicationSchemes(): array
    {
        return [
            'Program Kemitraan Masyarakat',
            'Program Pemberdayaan Masyarakat Desa',
            'Program Kemitraan Wilayah',
            'Program Pengabdian Kepada Masyarakat',
            'Program Penerapan IPTEK di Masyarakat',
            'Program Kuliah Kerja Nyata Tematik',
            'Program Pengembangan Desa Mitra',
        ];
    }

    protected function fundingSources(): array
    {
        return [
            'DRTPM Kemendikbudristek',
            'BRIN',
            'Kemenristekdikti',
            'Pemerintah Daerah Sumenep',
            'Pemerintah Provinsi Jawa Timur',
            'Kerjasama Industri PT Telkom',
            'CSR Bank Jatim',
            'LPDP',
            'Yayasan Annuqayah',
            'Kerjasama Internasional AUSAID',
            'Hibah Internal Universitas',
        ];
    }

    /* ============================================================
     |  HELPERS — LOGIC
     ============================================================ */

    protected function determineStatus(\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): string
    {
        $now = now();

        // Kalau belum ada end_date dan start_date di masa lalu → ongoing
        if (! $endDate && $startDate < $now) {
            return 'ongoing';
        }

        // Kalau end_date sudah lewat → completed
        if ($endDate && $endDate < $now) {
            return 'completed';
        }

        // Kalau start_date masih future → ongoing (belum mulai, tapi aktif)
        if ($startDate > $now) {
            return 'ongoing';
        }

        return 'ongoing';
    }

    protected function fundAmount(bool $isResearch): int
    {
        return $isResearch
            ? fake()->numberBetween(20_000_000, 250_000_000)
            : fake()->numberBetween(10_000_000, 100_000_000);
    }
}
