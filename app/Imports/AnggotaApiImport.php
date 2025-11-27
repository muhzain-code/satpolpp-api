<?php

namespace App\Imports;

use App\Models\Alamat\Kabupaten;
use App\Models\Alamat\Kecamatan;
use App\Models\Alamat\Provinsi;
use App\Models\Anggota\Anggota;
use App\Models\Anggota\Jabatan;
use App\Models\Anggota\Unit;
use App\Services\NomorGeneratorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class AnggotaApiImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    public int $processedRows = 0;
    public bool $isSuccess = false;

    private array $dupNik = [];
    private array $dupNip = [];

    protected $NomorGeneratorService;

    public function __construct(NomorGeneratorService $NomorGeneratorService)
    {
        $this->NomorGeneratorService = $NomorGeneratorService;
    }
    public function headingRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        $validDataQueue = [];
        $this->processedRows = $rows->count();

        foreach ($rows as $index => $row) {
            $rowIndex = $index + $this->headingRow() + 1;
            $row = $row->map(fn($v) => is_string($v) ? trim($v) : $v);
            $data = [
                'nama'              => $row['nama'] ?? null,
                'nik'               => $row['nik'] ?? null,
                'nip'               => $row['nip'] ?? null,
                'jenis_kelamin'     => strtoupper($row['jenis_kelamin'] ?? ''),
                'tempat_lahir'      => $row['tempat_lahir'] ?? null,
                'tanggal_lahir'     => $this->transformDate($row['tanggal_lahir'] ?? null),
                'provinsi'          => $row['provinsi'] ?? null,
                'kabupaten'         => $row['kabupaten'] ?? null,
                'kecamatan'         => $row['kecamatan'] ?? null,
                'no_hp'             => $row['no_hp'] ?? null,
                'jabatan'           => $row['jabatan'] ?? null,
                'unit'              => $row['unit'] ?? null,
                'status'            => strtolower($row['status'] ?? 'aktif'),
                'jenis_kepegawaian' => strtolower($row['jenis_kepegawaian'] ?? ''),
            ];
            $validator = Validator::make($data, [
                'nama' => 'required|string|max:255',
                'nik' => [
                    'required',
                    'digits:16',
                    'numeric',
                    'unique:anggota,nik',
                ],
                'nip' => [
                    'nullable',
                    'unique:anggota,nip',
                ],
                'jenis_kelamin'     => 'required|in:L,P',
                'jenis_kepegawaian' => 'nullable|in:asn,p3k,nonasn',
                'jabatan'           => 'nullable|exists:jabatan,nama',
                'unit'              => 'nullable|exists:unit,nama',
            ], [
                'nik.unique' => "NIK '{$data['nik']}' sudah ada di database.",
                'nip.unique' => "NIP '{$data['nip']}' sudah ada di database.",
                'jenis_kelamin.in' => "Jenis Kelamin harus 'L' atau 'P'.",
                'jabatan.exists' => "Jabatan '{$data['jabatan']}' tidak ditemukan di master data.",
                'unit.exists' => "Unit '{$data['unit']}' tidak ditemukan di master data.",
            ]);

            if ($validator->fails()) {
                $this->addError($rowIndex, $validator->errors()->all());
                continue;
            }

            if ($data['nik']) {
                if (isset($this->dupNik[$data['nik']])) {
                    $this->addError($rowIndex, ["NIK '{$data['nik']}' duplikat dengan baris {$this->dupNik[$data['nik']]}"]);
                    continue;
                }
                $this->dupNik[$data['nik']] = $rowIndex;
            }

            if ($data['nip']) {
                if (isset($this->dupNip[$data['nip']])) {
                    $this->addError($rowIndex, ["NIP '{$data['nip']}' duplikat dengan baris {$this->dupNip[$data['nip']]}"]);
                    continue;
                }
                $this->dupNip[$data['nip']] = $rowIndex;
            }

            $prov = Provinsi::where('nama_provinsi', $data['provinsi'])->first();
            if (!$prov) {
                $this->addError($rowIndex, ["Provinsi '{$data['provinsi']}' tidak ditemukan"]);
                continue;
            }

            $kab = Kabupaten::where('provinsi_id', $prov->id)
                ->where('nama_kabupaten', $data['kabupaten'])
                ->first();

            if (!$kab) {
                $this->addError($rowIndex, ["Kabupaten '{$data['kabupaten']}' tidak sesuai dengan provinsi '{$data['provinsi']}'"]);
                continue;
            }

            $kec = Kecamatan::where('kabupaten_id', $kab->id)
                ->where('nama_kecamatan', $data['kecamatan'])
                ->first();

            if (!$kec) {
                $this->addError($rowIndex, ["Kecamatan '{$data['kecamatan']}' tidak sesuai dengan kabupaten '{$data['kabupaten']}'"]);
                continue;
            }

            $data['provinsi_id']  = $prov->id;
            $data['kabupaten_id'] = $kab->id;
            $data['kecamatan_id'] = $kec->id;

            $validDataQueue[] = $data;
        }

        if (count($this->errors) > 0) return;

        DB::beginTransaction();
        try {
            foreach ($validDataQueue as $data) {
                $jab = Jabatan::where('nama', $data['jabatan'])->first();
                $unit = Unit::where('nama', $data['unit'])->first();

                $kodeBaru = $this->NomorGeneratorService->generateKodeAnggota();

                Anggota::create([
                    'kode_anggota'      => $kodeBaru,
                    'nama'              => $data['nama'],
                    'nik'               => $data['nik'],
                    'nip'               => $data['nip'],
                    'jenis_kelamin'     => $data['jenis_kelamin'],
                    'tempat_lahir'      => $data['tempat_lahir'],
                    'tanggal_lahir'     => $data['tanggal_lahir'],

                    'provinsi_id'       => $data['provinsi_id'],
                    'kabupaten_id'      => $data['kabupaten_id'],
                    'kecamatan_id'      => $data['kecamatan_id'],

                    'no_hp'             => $data['no_hp'],
                    'jabatan_id'        => $jab?->id,
                    'unit_id'           => $unit?->id,

                    'status'            => $data['status'],
                    'jenis_kepegawaian' => $data['jenis_kepegawaian'],

                    'created_by'        => Auth::id(),
                ]);
            }

            DB::commit();
            $this->isSuccess = true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('SYSTEM', ['Database Error: ' . $e->getMessage()]);
        }
    }

    private function addError($row, array $messages)
    {
        $this->errors[] = [
            'row' => $row,
            'errors' => $messages
        ];
    }

    private function transformDate($value): ?string
    {
        if (empty($value)) return null;

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            // Jika format teks (misal: 1990-01-01 atau 01/01/1990)
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
