<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Download Buku Pdf</title>
     {{-- <!-- Canonical SEO -->
    <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Include Styles -->
    @include('pages.layouts/sections/styles')

    <!-- Include Scripts for customizer, helper, analytics, config -->
    @include('pages.layouts/sections/scriptsIncludes')

    <!-- Datatables -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"/>
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet"> --}}
</head>
<body>
    <div class="row">
        <div class="col-12">
          <!-- Bordered Table -->
          <h3>Daftar Buku Yang Tesedia</h3>
          <div class="card">
              <div class="card-body py-3">
                  <table class="table table-bordered yajra-datatable">
                      <thead>
                          <tr>
                              <th width="3%">No</th>
                              <th width="3%">Kode Buku</th>
                              <th width="24%">Judul</th>
                              <th width="10%">Kategori</th>
                              <th width="15%">Pengarang</th>
                              <th width="10%">Tahun</th>
                              <th width="10%">Stok</th>
                          </tr>
                      </thead>
                      <tbody>
                        @foreach ($data_buku as $key => $buku)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $buku->kode_buku }}</td>
                                <td>{{ $buku->judul }}</td>
                                <td>{{ $buku->category->nama_kategori }}</td>
                                <td>{{ $buku->pengarang }}</td>
                                <td>{{ $buku->tahun }}</td>
                                <td>{{ $buku->stok }}</td>
                            </tr>
                        @endforeach
                      </tbody>
                  </table>
              </div>
          </div>
          <!--/ Bordered Table -->
        </div>
      </div>

  <!-- Include Scripts -->
  {{-- @include('pages.layouts/sections/scripts') --}}
</body>
</html>