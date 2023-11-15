<?php

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/unautorized', function () {
    return response()->json([
        "status" => 401,
        "message" => "Unautorized"
    ]);
})->name('unautorized');

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/validate', function (Request $request) {
        return response()->json([
            "status" => 200,
            "message" => "Authorized"
        ]);
    });
    Route::get('/logout', [AuthController::class, 'logout']);

    // Buku Route
    Route::prefix('book')->group(function () {
        Route::get('/all', [BookController::class, 'getAllBooks']);
        Route::get('/dashboard', [BookController::class, 'dashboard']);
        Route::get('/', [BookController::class, 'getAll']);
        Route::post('/search', [BookController::class, 'searchBooks']);
        Route::get('/filter/{kategori}', [BookController::class, 'filterBooks']);
        Route::get('/{id}', [BookController::class, 'getBook']);
        //CRUD
        Route::get('/create', [BookController::class, 'create']);
        Route::post('/create', [BookController::class, 'store']);
        Route::get('/{id}/update', [BookController::class, 'show']);
        Route::post('/{id}/update', [BookController::class, 'update']);
        Route::delete('/{id}/delete', [BookController::class, 'destroy']);
        //import Export
        Route::get('/export/excel', [BookController::class, 'exportBook']);
        Route::get('/export/pdf', [BookController::class, 'exportBookPdf']);
        Route::get('/download/template', [BookController::class, 'exportTemplate']);
        Route::post('/import/excel', [BookController::class, 'importBook']);
    });

    // Kategori Route
    Route::prefix('category')->group(function () {
        Route::get('/{category}/book/all', [BookController::class, 'getBooksByCategory']);
        Route::post('/search', [CategoryController::class, 'searchKategori']);
        Route::get('/all', [CategoryController::class, 'getAllCategories']);
        Route::get('/all/all', [CategoryController::class, 'getAllCategoriesAll']);
        Route::get('/{id}', [CategoryController::class, 'getDetailCategory']);
        //CRUD
        Route::post('/create', [CategoryController::class, 'store']);
        Route::post('/update/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}/delete', [CategoryController::class, 'destroy']);
    });

    // User Route
    Route::prefix('user')->group(function () {
        Route::get('/all', [UserController::class, 'getAllUsers']);
        Route::get('/member/all', [UserController::class, 'getAllMember']);
        Route::get('/{id}', [UserController::class, 'getUser']);
    });

    // Peminjaman Route
    Route::prefix('peminjaman')->group(function () {
        Route::get('/', [PeminjamanController::class, 'getPeminjamanBuku']);
        Route::get('/all', [PeminjamanController::class, 'getAllPeminjamanBuku']);
        Route::get('/show/{id}', [PeminjamanController::class, 'show']);
        Route::get('/return', [PeminjamanController::class, 'getPengembalianBuku']);
        Route::post('/book/{bukuId}/member/{memberId}', [PeminjamanController::class, 'actionPinjamBuku']);
        Route::get('/book/{id}/accept', [PeminjamanController::class, 'acceptPeminjamanBuku']);
        Route::post('/book/{id}/return', [PeminjamanController::class, 'returnPeminjamanBuku']);
    });
});
