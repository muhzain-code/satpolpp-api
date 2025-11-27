<?php

namespace App\Http\Controllers\Api\Anggota;

use App\Http\Controllers\Controller;
use App\Imports\AnggotaApiImport;
use App\Services\NomorGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class AnggotaImportController extends Controller
{
    public function import(Request $request, NomorGeneratorService $nomorGeneratorService)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'File invalid', 'errors' => $validator->errors()], 400);
        }

        $importer = new AnggotaApiImport($nomorGeneratorService);

        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca file.', 'debug' => $e->getMessage()], 500);
        }

        if (count($importer->errors) > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi Gagal. Data dibatalkan seluruhnya.',
                'summary' => [
                    'total_checked' => $importer->processedRows,
                    'total_failed'  => count($importer->errors)
                ],
                'detail_errors' => $importer->errors
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Import Berhasil. ' . $importer->processedRows . ' data disimpan.',
        ], 200);
    }
}
