<?php

namespace App\Services\Humas;

use App\Exceptions\CustomException;
use App\Models\Humas\Agenda;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgendaService
{
    public function listAgenda($currentPage, $perPage, $keyword = null): array
    {
        $query = Agenda::latest();

        if (!empty($keyword)) {
            $query->where('judul', 'like', '%' . $keyword . '%');
        }

        $agenda = $query->paginate($perPage, ['*'], 'page', $currentPage)
            ->through(function ($item) {
                $tanggal = $item->tanggal_kegiatan ? Carbon::parse($item->tanggal_kegiatan)->format('d-m-Y') : '-';

                $jamMulai = $item->waktu_mulai ? Carbon::parse($item->waktu_mulai)->format('H:i') : '-';
                $jamSelesai = $item->waktu_selesai ? Carbon::parse($item->waktu_selesai)->format('H:i') : '-';

                return [
                    'id'               => $item->id,
                    'judul'            => $item->judul,
                    'deskripsi'        => $item->deskripsi,
                    'lokasi'           => $item->lokasi,
                    'tanggal'          => $tanggal,
                    'waktu'            => $jamMulai . ' s/d ' . $jamSelesai,
                    'jam_mulai'        => $jamMulai,
                    'jam_selesai'      => $jamSelesai,
                    'tampilkan_publik' => (bool) $item->tampilkan_publik,
                ];
            });

        return [
            'message' => 'Data agenda berhasil ditampilkan',
            'data'    => [
                'current_page' => $agenda->currentPage(),
                'per_page'     => $agenda->perPage(),
                'total'        => $agenda->total(),
                'last_page'    => $agenda->lastPage(),
                'items'        => $agenda->items(),
            ]
        ];
    }

    public function createAgenda(array $data): array
    {
        DB::beginTransaction();

        try {
            $isPublic = $data['tampilkan_publik'] ?? true;
            $publishedAt = $isPublic ? now() : null;

            $deskripsi = $data['deskripsi'] ?? $data['isi'] ?? null;

            $agenda = Agenda::create([
                'judul'            => $data['judul'],
                'deskripsi'        => $deskripsi,
                'lokasi'           => $data['lokasi'] ?? null,
                'tanggal_kegiatan' => $data['tanggal_kegiatan'],
                'waktu_mulai'      => $data['waktu_mulai'],
                'waktu_selesai'    => $data['waktu_selesai'] ?? null,
                'tampilkan_publik' => $isPublic,
                'published_at'     => $publishedAt,
                'created_by'       => Auth::id(),
            ]);

            DB::commit();

            return [
                'message' => 'Berhasil menambahkan Agenda',
                'data'    => $agenda
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error createAgenda: ' . $e->getMessage());
            throw new CustomException('Gagal menambahkan data Agenda');
        }
    }

    public function updateAgendaById(array $data, $id): array
    {
        DB::beginTransaction();

        try {
            $agenda = Agenda::find($id);

            if (!$agenda) {
                throw new CustomException('Data agenda tidak ditemukan');
            }

            $isPublic = $data['tampilkan_publik'] ?? false;
            $publishedAt = $agenda->published_at;

            if (!$agenda->tampilkan_publik && $isPublic) {
                $publishedAt = now();
            }

            $deskripsi = $data['deskripsi'] ?? $data['isi'] ?? $agenda->deskripsi;

            $agenda->update([
                'judul'            => $data['judul'],
                'deskripsi'        => $deskripsi,
                'lokasi'           => $data['lokasi'],
                'tanggal_kegiatan' => $data['tanggal_kegiatan'],
                'waktu_mulai'      => $data['waktu_mulai'],
                'waktu_selesai'    => $data['waktu_selesai'],
                'tampilkan_publik' => $isPublic,
                'published_at'     => $publishedAt,
                'updated_by'       => Auth::id(),
            ]);

            DB::commit();

            return [
                'message' => 'Data agenda berhasil diperbarui',
                'data'    => $agenda
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updateAgenda: ' . $e->getMessage());
            throw new CustomException('Gagal memperbarui data Agenda');
        }
    }

    public function showAgendaById($id): array
    {
        $agenda = Agenda::find($id);

        if (!$agenda) {
            throw new CustomException('Data agenda tidak ditemukan');
        }

        return [
            'message' => 'Detail agenda berhasil ditampilkan',
            'data'    => $agenda
        ];
    }

    public function deleteAgendaById($id): array
    {
        DB::beginTransaction();

        try {
            $agenda = Agenda::find($id);

            if (!$agenda) {
                throw new CustomException('Data agenda tidak ditemukan');
            }

            $agenda->delete();

            DB::commit();

            return [
                'message' => 'Data agenda berhasil dihapus',
                'data'    => null
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error deleteAgenda: ' . $e->getMessage());
            throw new CustomException('Gagal menghapus data Agenda');
        }
    }

    public function agendaPublik($request): array
    {
        $limit = $request->input('limit', 10);

        $konten = Agenda::select([
            'judul',
            'deskripsi',
            'lokasi',
            'tanggal_kegiatan',
            'waktu_mulai',
            'waktu_selesai',
            'tampilkan_publik',
        ])
            ->where('tampilkan_publik', true)
            ->whereDate('tanggal_kegiatan', '>=', now())
            ->orderBy('tanggal_kegiatan', 'asc')
            ->limit($limit)
            ->get()
            ->transform(function ($item) {
                return [
                    'judul'            => $item->judul,
                    'deskripsi'        => $item->deskripsi,
                    'tanggal_kegiatan' => $item->tanggal_kegiatan,
                    'lokasi'           => $item->lokasi,
                    'waktu_mulai'      => $item->waktu_mulai ? Carbon::parse($item->waktu_mulai)->format('H:i') . ' WIB' : '-',
                    'waktu_selesai'    => $item->waktu_selesai ? Carbon::parse($item->waktu_selesai)->format('H:i') . ' WIB' : 'Selesai',
                    'tampilkan_publik' => (bool) $item->tampilkan_publik,
                ];
            });

        return [
            'message' => 'Data agenda berhasil ditampilkan',
            'data'    => $konten
        ];
    }
}
