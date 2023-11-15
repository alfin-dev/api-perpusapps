<?php

namespace App\Http\Controllers\Api;

use App\Exports\BookExport;
use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\BookImport;
use App\Models\Book;
use App\Models\Category;
use App\Models\Peminjaman;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class BookController extends Controller
{
    public function dashboard()
    {
        $totalBuku = Book::all();
        $totalStok = 0;

        foreach ($totalBuku as $buku) {
            $totalStok = $totalStok + $buku->stok;
        }
        $totalBuku = $totalBuku->count();
        $totalMember = User::whereHas('roles', function ($q) {
            $q->where('name', 'member');
        })->count();
        $totalPegawai = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->count();
        $user = User::with('roles')->find(Auth::id());
        $totalDipinjam = Peminjaman::where('status', '2');
        if ($user->getRoleNames()[0] == 'member') {
            $totalDipinjam = $totalDipinjam->where('id_member', $user->id);
        }
        $totalDipinjam = $totalDipinjam->count();
        $totalDikembalikan = Peminjaman::where('status', '3');
        if ($user->getRoleNames()[0] == 'member') {
            $totalDikembalikan = $totalDikembalikan->where('id_member', $user->id);
        }
        $totalDikembalikan = $totalDikembalikan->count();

        $dashboard = [
            'totalBuku' => $totalBuku,
            'totalStok' => $totalStok,
            'totalMember' => $totalMember,
            'totalPegawai' => $totalPegawai,
            'totalDipinjam' => $totalDipinjam,
            'totalDikembalikan' => $totalDikembalikan
        ];
        return response()->ok(['dashboard' => $dashboard], 'Sukses mengambil semua data dashboard');
    }
    public function getAllBooks(Request $request)
    {
        $q = $request->query('search');
        // dd($q != null);
        try {
            $books = Book::with('category')->where('judul', 'ilike', "%{$q}%")->orderBy('judul', 'ASC');

            if (request('filter')) {
                $books =   $books->where('category_id', request('filter'));
            }

            $books = $books->paginate(request('per_page'));
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data buku ', $e->getMessage());
        }

        return response()->ok(['books' => $books], 'Sukses mengambil semua data buku');
    }

    public function getAll()
    {
        try {
            $books = Book::with('category')->latest()->get();
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data buku ', $e->getMessage());
        }

        return response()->ok(['books' => $books], 'Sukses mengambil semua data buku');
    }

    public function searchBooks(Request $request)
    {
        $filter = [];
        if ($request->input('search'))
            $filter['search'] = $request->input('search');
        try {
            $books = Book::with('category')->filter($filter)->latest()->get();
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data buku ', $e->getMessage());
        }

        return response()->ok(['books' => $books], 'Sukses mengambil semua data buku');
    }

    public function filterBooks($kategori)
    {
        $q = null;
        try {
            if ($kategori == 0)
                $books = Book::with('category')->latest()->get();
            else
                $books = Book::with('category')->where('category_id', $kategori)->latest()->get();
            $books = Book::with('category');
            if ($q != null) {
                // die('disini');
                $books = $books->where('judul', 'LIKE', '%' . $q . '%')->paginate(100);
            } else {
                $books = $books->paginate(10);
            }
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data buku ', $e->getMessage());
        }

        return response()->ok(['books' => $books], 'Sukses mengambil semua data buku');
    }

    public function getBook($id)
    {
        try {
            $book = Book::with('category')->findOrFail($id);
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data buku id = ' . $id, $e->getMessage());
        }

        return response()->ok(['book' => $book],  'Sukses mengambil data buku id = ' . $id);
    }

    public function getBooksByCategory($category)
    {
        $books = Book::with('category')->latest()->where('category_id', $category)->get();

        return response()->ok(['books' => $books], 'Sukses mengambil data buku berdasarkan kategori');
    }

    public function create()
    {
        $categories = Category::all();
        $years = [];

        $date = Carbon::now();
        for ($i = 0; $i <= 20; $i++) {
            array_push($years, $date->year - $i);
        }

        return response()->ok(['categories' => $categories, 'years' => $years]);
    }

    public function store(Request $request)
    {
        Log::info($request);
        $validate = Validator::make($request->all(), [
            'judul' => 'required',
            'category_id' => 'required',
            'pengarang' => 'required',
            'tahun' => 'required',
            'stok' => 'required',
            'path' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
        ]);

        $errors = $validate->errors();

        if ($validate->fails()) {
            return response()->json([
                'status' => 409,
                'message' => [
                    'judul' => $errors->first('judul') ?: 'kosong',
                    'category_id' => $errors->first('category_id') ?: 'kosong',
                    'pengarang' => $errors->first('pengarang') ?: 'kosong',
                    'tahun' => $errors->first('tahun') ?: 'kosong',
                    'stok' => $errors->first('stok') ?: 'kosong',
                ],
            ]);
        }


        DB::beginTransaction();
        try {
            $slug = explode(' ', strtolower($request->input('judul')));
            $slug = implode('-', $slug);
            $char = substr($request->input('judul'), 0, 1);

            $count_kode = Book::where('kode_buku', 'LIKE', $char . '%')->count();

            $penerbit = null;
            if ($request->input('penerbit'))
                $penerbit = $request->input('penerbit');

            $image_path = '/image/book/default-image.png';
            if ($request->file() || $request->file('path') != null)
                $image_path = $request->file('path')->store('image/book', 'public');

            $book = Book::create([
                'kode_buku' => $char . '-' . $count_kode + 1,
                'judul' => $request->input('judul'),
                'slug' => $slug,
                'category_id' => $request->input('category_id'),
                'pengarang' => $request->input('pengarang'),
                'penerbit' => $penerbit,
                'tahun' => $request->input('tahun'),
                'stok' => $request->input('stok'),
                'path' => $image_path,
            ]);
            DB::commit();
            return response()->created(['book' => $book], 'Buku berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->internalServerError('Gagal input data buku', $e->getMessage());
        }
    }

    public function show($id)
    {
        $categories = Category::all();
        $book = Book::findOrFail($id);
        $years = [];

        $date = Carbon::now();
        for ($i = 0; $i <= 20; $i++) {
            array_push($years, $date->year - $i);
        }

        return response()->ok(['book' => $book, 'year' => $years, 'categories' => $categories]);
    }

    public function update(Request $request, $id)
    {
        request()->validate([
            'judul' => 'required',
            'category_id' => 'required',
            'pengarang' => 'required',
            'penerbit' => 'required',
            'tahun' => 'required',
            'stok' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            $book = Book::findOrFail($id);

            $kode = $book->kode_buku;
            $slug = explode(' ', strtolower($request->input('judul')));
            $slug = implode('-', $slug);

            if ($request->input('judul') !== $book->judul) {
                $char = substr($request->input('judul'), 0, 1);

                $count_kode = Book::where('kode_buku', 'LIKE', $char . '%')->count();
                $kode = $char . '-' . $count_kode + 1;
            }

            $image_path = $book->path;
            if ($request->file() || $request->file('path') != null)
                $image_path = $request->file('path')->store('image/book', 'public');

            $book->update([
                'kode_buku' => $kode,
                'judul' => $request->input('judul'),
                'slug' => $slug,
                'category_id' => $request->input('category_id'),
                'pengarang' => $request->input('pengarang'),
                'penerbit' => $request->input('penerbit'),
                'tahun' => $request->input('tahun'),
                'stok' => $request->input('stok'),
                'path' => $image_path
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->internalServerError('Gagal mengupdate data buku ' . $book->judul, $e->getMessage());
        }

        return response()->ok(['book' => $book], 'Berhasil mengupdate data buku ' . $book->judul);
    }

    public function destroy($id)
    {
        // dd($id);
        $book = Book::findOrFail($id);
        $peminjaman = Peminjaman::where('id_buku', $id)->count();
        // dd($peminjaman);
        if ($peminjaman == 0) {
            $book->delete();
            $message = 'Berhasil menghapus data buku ' . $book->judul;
            return response()->ok(null, $message);
        } else {
            return response()->internalServerError('Gagal delete data buku ' . $book->judul . " : Terdapat peminjaman pada buku");
        }
    }

    public function exportBookPdf()
    {
        try {
            $books = Book::with('category')->latest()->get();

            $data = [
                'data_buku' => $books
            ];

            $name = '/export/buku_export.pdf';
            $path = public_path() . '/storage' . $name;
            $pdf = Pdf::loadView('book.viewPdf', $data);
            $pdf->render();
            $output = $pdf->output();
            file_put_contents($path, $output);
            return response()->json([
                'status' => 200,
                'message' => 'Berhasil Export File Buku Pdf',
                'path' => 'storage/' . $name,
            ]);
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal Download Buku Pdf ' . $e->getMessage(), $e->getMessage());
        }
    }

    public function exportBook()
    {
        try {
            $name = 'export/buku_export.xlsx';
            Excel::store(new BookExport, $name);
            return response()->json([
                'status' => 200,
                'message' => 'Berhasil Export File Buku Excel',
                'path' => 'storage/' . $name
            ]);
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal Export Buku Excel ' . $e->getMessage(), $e->getMessage());
        }
    }

    public function exportTemplate()
    {
        try {
            $name = 'template/template_export.xlsx';
            Excel::store(new TemplateExport, $name);
            return response()->json([
                'status' => 200,
                'message' => 'Berhasil Export Template Excel',
                'path' => 'storage/' . $name
            ]);
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal Download Template Import Buku', $e->getMessage());
        }
    }

    public function importBook(Request $request)
    {
        try {
            if (!$request->file('file_import')) {
                return response()->invalidInput(null, 'File import tidak ditemukan');
            }
            $import = new BookImport();
            $import->setStartRow(2);
            Excel::import($import, $request->file('file_import'));
            $books = Book::with('category')->latest()->get();
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal Import Buku', $e->getMessage());
        }
        return response()->ok(['buku' => $books], 'Berhasil Import Buku');
    }
}
