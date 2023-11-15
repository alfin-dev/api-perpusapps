<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function getAllCategories(Request $request)
    {
        try {
            $q = $request->query('search');
            if ($q != null) {
                $categories = Category::where('nama_kategori', 'LIKE', '%' . $q . '%')->paginate(9);
            } else {
                $categories = Category::paginate(9);
            }
            // $allcategories = Category::all();
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data categories', $e->getMessage());
        }
        return response()->ok(['categories' => $categories], 'Sukses mengambil data categories');
    }

    public function searchKategori(Request $request)
    {
        $filter = [];
        if ($request->input('search'))
            $filter['search'] = $request->input('search');
        try {
            $categories = Category::filter($filter)->latest()->get();
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data categories', $e->getMessage());
        }
        return response()->ok(['categories' => $categories], 'Sukses mengambil data categories');
    }

    public function getAllCategoriesAll()
    {
        try {
            $categories = Category::all();
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data categories', $e->getMessage());
        }
        return response()->ok(['categories' => $categories], 'Sukses mengambil data categories');
    }

    public function getDetailCategory($id)
    {
        try {
            $category = Category::findOrFail($id);
        } catch (\Exception $e) {
            return response()->internalServerError('Gagal mengambil data category', $e->getMessage());
        }

        return response()->ok(['category' => $category], 'Sukses mengambil data category');
    }

    public function store(Request $request)
    {
        $validasi = Validator::make($request->all(), [
            'nama_kategori' => 'required',
        ]);

        $errors = $validasi->errors();

        if ($validasi->fails()) {
            return response()->json([
                'status' => 409,
                'message' => [
                    'name' => $errors->first('nama_kategori'),
                ],
            ]);
        }

        DB::beginTransaction();
        try {
            $category = Category::create([
                'nama_kategori' => $request->input('nama_kategori')
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->internalServerError('Gagal menambahkan data', $e->getMessage());
        }

        return response()->created(['category' => $category], 'Berhasil menambahkan data');
    }

    public function update(Request $request, $id)
    {
        $validasi = Validator::make($request->all(), [
            'nama_kategori' => 'required',
        ]);

        $errors = $validasi->errors();

        if ($validasi->fails()) {
            return response()->json([
                'status' => 409,
                'message' => [
                    'nama_kategori' => $errors->first('nama_kategori') . $request->input('nama_kategori'),
                ],
            ]);
        }

        DB::beginTransaction();
        try {
            $category = Category::findOrFail($id);

            $category->update([
                'nama_kategori' => $request->input('nama_kategori')
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->internalServerError('Gagal mengubah data', $e->getMessage());
        }

        return response()->ok(null, 'Berhasil mengubah data ' . $category->nama_kategori);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $message = 'Berhasil menghapus data category ' . $category->judul;
        $category->delete();

        return response()->ok(null, $message);
    }
}
