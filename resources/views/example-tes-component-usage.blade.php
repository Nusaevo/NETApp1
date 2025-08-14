@extends('layout.default')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Contoh Penggunaan Komponen Dropdown Search Multiple Select</h4>
                </div>
                <div class="card-body">

                    <h5>Contoh 1: Dropdown dengan Data Ban</h5>
                    @php
                        $banItems = [
                            '1' => 'Ban Michelin 205/55R16',
                            '2' => 'Ban Bridgestone 195/65R15',
                            '3' => 'Ban Goodyear 225/45R17',
                            '4' => 'Ban Dunlop 185/70R14',
                            '5' => 'Ban Yokohama 215/60R16',
                            '6' => 'Ban Pirelli 235/40R18',
                            '7' => 'Ban Continental 195/55R16',
                            '8' => 'Ban Hankook 205/50R17',
                            '9' => 'Ban Toyo 225/50R17',
                            '10' => 'Ban Maxxis 185/65R15'
                        ];

                        $selectedBanItems = ['1', '3', '5'];
                    @endphp

                    <x-tes-component
                        :items="$banItems"
                        :selectedItems="$selectedBanItems"
                        placeholder="Pilih ban yang diinginkan..."
                        name="ban_selection"
                        id="ban_dropdown"
                        :multiple="true"
                    />

                    <hr class="my-4">

                    <h5>Contoh 2: Dropdown dengan Data Supplier</h5>
                    @php
                        $supplierItems = [
                            'SUP001' => 'PT Supplier Utama - Jakarta',
                            'SUP002' => 'CV Mitra Jaya - Bandung',
                            'SUP003' => 'UD Maju Bersama - Surabaya',
                            'SUP004' => 'PT Global Trading - Medan',
                            'SUP005' => 'CV Sukses Mandiri - Semarang',
                            'SUP006' => 'PT Indo Supplier - Makassar',
                            'SUP007' => 'UD Berkah Jaya - Palembang',
                            'SUP008' => 'CV Mitra Abadi - Yogyakarta'
                        ];

                        $selectedSupplierItems = ['SUP001', 'SUP003'];
                    @endphp

                    <x-tes-component
                        :items="$supplierItems"
                        :selectedItems="$selectedSupplierItems"
                        placeholder="Pilih supplier..."
                        name="supplier_selection"
                        id="supplier_dropdown"
                        :multiple="true"
                    />

                    <hr class="my-4">

                    <h5>Contoh 3: Dropdown dengan Data Kategori</h5>
                    @php
                        $categoryItems = [
                            'CAT001' => 'Ban Motor',
                            'CAT002' => 'Ban Mobil',
                            'CAT003' => 'Ban Truk',
                            'CAT004' => 'Ban Bus',
                            'CAT005' => 'Ban Traktor',
                            'CAT006' => 'Ban Forklift'
                        ];

                        $selectedCategoryItems = [];
                    @endphp

                    <x-tes-component
                        :items="$categoryItems"
                        :selectedItems="$selectedCategoryItems"
                        placeholder="Pilih kategori produk..."
                        name="category_selection"
                        id="category_dropdown"
                        :multiple="true"
                    />

                    <hr class="my-4">

                    <h5>Contoh 4: Dropdown Single Select (Multiple = false)</h5>
                    @php
                        $singleItems = [
                            'OPT1' => 'Opsi Pertama',
                            'OPT2' => 'Opsi Kedua',
                            'OPT3' => 'Opsi Ketiga',
                            'OPT4' => 'Opsi Keempat'
                        ];

                        $selectedSingleItems = ['OPT2'];
                    @endphp

                    <x-tes-component
                        :items="$singleItems"
                        :selectedItems="$selectedSingleItems"
                        placeholder="Pilih satu opsi..."
                        name="single_selection"
                        id="single_dropdown"
                        :multiple="false"
                    />

                    <hr class="my-4">

                    <div class="alert alert-info">
                        <h6>Fitur Komponen:</h6>
                        <ul>
                            <li>✅ Pencarian real-time dalam dropdown</li>
                            <li>✅ Multiple select dengan checkbox</li>
                            <li>✅ Tampilan jumlah item yang dipilih</li>
                            <li>✅ Data tersimpan dalam hidden input</li>
                            <li>✅ Responsive design dengan Bootstrap 5</li>
                            <li>✅ Keyboard navigation support</li>
                            <li>✅ Auto-clear search saat dropdown ditutup</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6>Cara Menggunakan:</h6>
                        <ol>
                            <li>Klik dropdown untuk membuka daftar item</li>
                            <li>Ketik di kotak pencarian untuk memfilter item</li>
                            <li>Centang checkbox untuk memilih item</li>
                            <li>Item yang dipilih akan ditampilkan di tombol dropdown</li>
                            <li>Data tersimpan dalam hidden input dengan nama <code>name_values</code></li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
