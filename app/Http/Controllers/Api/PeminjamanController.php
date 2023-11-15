<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Peminjaman;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    public function actionPinjamBuku(Request $request, $bukuId, $memberId)
    {
        $status = 1;
        DB::beginTransaction();
        try {
            if ($request->bypass && $request->bypass == '1') {
                $status = 2;
            }
            $peminjaman = Peminjaman::create([
                'id_buku' => $bukuId,
                'id_member' => $memberId,
                'tanggal_peminjaman' => $request->tanggal_peminjaman != null ? $request->tanggal_peminjaman : Carbon::now()->toDateString(),
                'tanggal_pengembalian' => $request->tanggal_pengembalian != null ? $request->tanggal_pengembalian : Carbon::now()->addDays(7)->toDateString(),
                'status' => $status
            ]);

            $book = Book::findOrFail($bukuId);
            $book->update([
                'stok' => $book->stok - 1,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->internalServerError('Gagal melakukan peminjaman', $e->getMessage());
        }

        return response()->created(['peminjaman' => $peminjaman, 'book' => $book], 'Sukses melakukan peminjaman');
    }

    public function acceptPeminjamanBuku($id)
    {
        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::findOrFail($id);

            $peminjaman->update([
                'status' => 2
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->internalServerError('Gagal menyetujui peminjaman', $e->getMessage());
        }

        return response()->ok(['peminjaman' => $peminjaman], 'Sukses menyetujui peminjaman');
    }

    public function returnPeminjamanBuku(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::findOrFail($id);
            $buku = Book::findOrFail($peminjaman->id_buku);

            $peminjaman->update([
                'status' => 3,
                'tanggal_pengembalian' => $request->tanggal_pengembalian != null ? $request->tanggal_pengembalian : Carbon::now()->toDateString(),
            ]);

            $buku->update([
                'stok' => $buku->stok + 1,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->internalServerError('Gagal mengembalikan buku', $e->getMessage());
        }

        return response()->ok(['peminjaman' => $peminjaman, 'buku' => $buku], 'Berhasil mengembalikan buku');
    }



    public function getPeminjamanBuku(Request $request)
    {
        try {
            $q = $request->query('search');
            $user = User::with('roles')->find(Auth::id());
            // $user->getRoleNames();
            // dd($user->getRoleNames()[0] == 'admin');
            $data = Peminjaman::with('member')->whereHas('book', function ($query) use ($q) {
                $query->where('judul', 'ilike', "%{$q}%");
            })->with('book');
            if ($user->getRoleNames()[0] == 'member') {
                $data = $data->where('id_member', $user->id);
            }
            $data = $data->paginate(request('per_page'));
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data peminjaman buku', $e->getMessage());
        }

        return response()->ok(['peminjaman' => $data], 'Berhasil mengambil data peminjaman buku');
    }

    public function getAllPeminjamanBuku()
    {
        try {
            $user = User::with('roles')->find(Auth::id());
            $data = Peminjaman::with('member')->with('book')->latest()->get();
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data peminjaman buku', $e->getMessage());
        }

        return response()->ok(['peminjaman' => $data], 'Berhasil mengambil data peminjaman buku');
    }

    public function getPengembalianBuku()
    {
        try {
            $user = User::with('roles')->find(Auth::id());
            $data = Peminjaman::with('member')->with('book')->latest()->where('status', 3);
            if ($user->getRoleNames() == 'member') {
                $data = $data->where('id_member', $user->id);
            }
            $data = $data->get();
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data pengembalian buku', $e->getMessage());
        }

        return response()->ok(['peminjaman' => $data], 'Berhasil mengambil data pengembalian buku');
    }

    public function show($id)
    {
        $book = Peminjaman::with(['book' => function ($q) {
            $q->with(['category']);
        }])->with('member')->findOrFail($id);

        return response()->ok(['book' => $book]);
    }
}
