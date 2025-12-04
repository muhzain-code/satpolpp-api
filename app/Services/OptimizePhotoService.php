<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class OptimizePhotoService
{
    public function optimizeImage(UploadedFile $file, string $directory): string
    {
        try {
            // 1. Generate Nama File
            $fileName = Str::uuid()->toString() . '.webp';
            $path = "{$directory}/{$fileName}";

            // 2. Baca Gambar
            $image = Image::read($file->getRealPath());

            // 3. Resize untuk BUKTI LAPORAN (Scale Down)
            // Menggunakan width DAN height 1600 menciptakan "Bounding Box".
            // - Gambar Landscape (misal 4000x3000) -> menjadi 1600x1200
            // - Gambar Portrait (misal 3000x4000) -> menjadi 1200x1600
            // Ini memastikan sisi terpanjang tidak melebihi 1600px.
            // Resolusi 1600px dipilih agar petugas bisa zoom-in untuk melihat detail (plat nomor/wajah).
            $image->scaleDown(width: 1600, height: 1600);

            // 4. Sharpen (PENTING untuk Bukti)
            // Membantu memperjelas tepi objek (huruf di plat nomor/spanduk).
            // Nilai 10-15 cukup aman.
            $image->sharpen(12);

            // 5. Encode ke WebP
            // Quality 80 cukup tinggi untuk mempertahankan detail forensik sederhana
            // tapi jauh lebih kecil dari JPG asli dari kamera HP (yang bisa 5-10MB).
            $encoded = $image->encode(new WebpEncoder(quality: 80));

            // 6. Simpan
            Storage::disk('public')->put($path, (string) $encoded, 'public');

            return $path;
        } catch (\Throwable $e) {
            Log::error("[ComplaintImageService] Gagal memproses bukti foto:", [
                'error' => $e->getMessage(),
                'file'  => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);

            // FALLBACK: Simpan asli jika gagal (Evidence tidak boleh hilang)
            return $file->store($directory, 'public');
        }
    }
}
