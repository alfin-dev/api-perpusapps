<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            ['nama_kategori' => 'Novel'],
            ['nama_kategori' => 'Cergam'],
            ['nama_kategori' => 'Komik'],
            ['nama_kategori' => 'Ensiklopedi'],
            // ['nama_kategori' => 'Nomik'],
            // ['nama_kategori' => 'Antologi'],
            ['nama_kategori' => 'Dongeng'],
            ['nama_kategori' => 'Biografi'],
            // ['nama_kategori' => 'Catatan Harian'],
            // ['nama_kategori' => 'Novelet'],
            ['nama_kategori' => 'Fotografi'],
            ['nama_kategori' => 'Karya Ilmiah'],
            // ['nama_kategori' => 'Tafsir'],
            ['nama_kategori' => 'Kamus'],
            // ['nama_kategori' => 'Panduan (how to)'],
            // ['nama_kategori' => 'Atlas'],
            ['nama_kategori' => 'Buku Ilmiah'],
            // ['nama_kategori' => 'Teks'],
            ['nama_kategori' => 'Majalah'],
            ['nama_kategori' => 'Buku Digital'],
        ];

        DB::table('categories')->insert($categories);
    }
}
