<?php

namespace App\Services;

use App\Models\Penindakan\Bap;
use App\Models\Penindakan\Penindakan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BAPGeneratorService
{
    public function generate(Penindakan $penindakan)
    {
        $nomorBap = app(NomorGeneratorService::class)->generateNomorBAP();

        $qrData = json_encode([
            'nomor_bap' => $nomorBap,
            'penindakan_id' => $penindakan->id,
            'verified_at' => now()->toDateTimeString(),
        ]);
 
        $qrBase64 = base64_encode(QrCode::format('png')->size(200)->generate($qrData));

        $pdf = app('dompdf.wrapper')->loadView('pdf.bap', [
            'penindakan' => $penindakan,
            'nomor_bap' => $nomorBap,
            'qr_base64' => $qrBase64
        ])->setPaper('A4', 'portrait');

        $filePath = "bap/{$nomorBap}.pdf";
        Storage::disk('public')->put($filePath, $pdf->output());

        return Bap::create([
            'nomor_bap' => $nomorBap,
            'penindakan_id' => $penindakan->id,
            'path_pdf' => $filePath,
            'data_qr' => $qrData,
            'created_by' => Auth::id(),
        ]);
    }
}
