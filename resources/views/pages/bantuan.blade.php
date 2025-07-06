@extends('layouts.app')

@section('title', 'Bantuan - Symadu')

@section('header-title', 'Bantuan')

@section('content')
<div class="container mx-auto px-4 py-6 md:py-8 overflow-y-auto" style="max-height: calc(100vh - 80px);">
    <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4 animate-fadeIn">
            <h2 class="text-2xl font-bold text-emerald-700">Pusat Bantuan</h2>
            <div class="flex flex-wrap items-center gap-3">
                @if(auth()->user() && auth()->user()->role_id === 1)
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors shadow-md btn-primary"
                    onclick="openVideoModal()"
                >
                    <i class="fas fa-video mr-2"></i>
                    Kelola Video
                </button>
                        <button
                            type="button"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors shadow-md btn-primary"
                    onclick="openDocumentModal()"
                        >
                    <i class="fas fa-file-pdf mr-2"></i>
                    Kelola Dokumen
                        </button>
                        @endif
            <a href="javascript:history.back()" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
                    </div>
                </div>

        <div class="mb-10 animate-fadeIn delay-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl section-heading">Tutorial Video</h3>
                    </div>

            <!-- Slider Controls & Indicators -->
            <div class="relative bg-gray-50 rounded-xl p-4 shadow-sm">
                <!-- Navigation Controls -->
                <div class="absolute inset-y-0 left-0 flex items-center z-20">
                    <a href="javascript:void(0)"
                        id="prevBtn"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white p-3 md:p-4 rounded-full ml-2 focus:outline-none shadow-lg flex items-center justify-center transition-all duration-300"
                    >
                        <i class="fas fa-chevron-left text-lg md:text-xl"></i>
                    </a>
                        </div>

                <div class="absolute inset-y-0 right-0 flex items-center z-20">
                    <a href="javascript:void(0)"
                        id="nextBtn"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white p-3 md:p-4 rounded-full mr-2 focus:outline-none shadow-lg flex items-center justify-center transition-all duration-300"
                    >
                        <i class="fas fa-chevron-right text-lg md:text-xl"></i>
                    </a>
                </div>

                <!-- Slides Container -->
                <div class="overflow-hidden rounded-lg">
                    <div class="flex transition-transform duration-300 ease-in-out" id="videoSlides">
                        <!-- Slide content will be rendered dynamically -->
                    </div>
                </div>

                <!-- Dots/Indicators -->
                <div class="flex justify-center mt-6" id="slideIndicators">
                    <span class="h-3 w-3 mx-1 rounded-full bg-emerald-600 cursor-pointer" onclick="goToSlide(0)"></span>
                    <!-- Dot indicators tambahan akan ditambahkan secara dinamis jika ada lebih dari 1 slide -->
                    </div>
                        </div>

            <!-- View More Button -->
            <div class="text-center mt-6">
                        <button
                            type="button"
                    class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-2 rounded-md hover:bg-emerald-100 transition-colors shadow-sm hover:shadow"
                    onclick="openAllVideosModal()"
                        >
                    Lihat Semua Video <i class="fas fa-video ml-2"></i>
                        </button>
                    </div>
                </div>

        <div class="mb-10 animate-fadeIn delay-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl section-heading">Panduan Pengguna</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="documentList">
                <!-- Document list will be rendered dynamically -->

                <!-- Guide 1 -->
                <div class="bg-white rounded-lg p-5 border border-gray-200 hover:shadow-lg transition-all card-hover">
                    <div class="flex items-center mb-3">
                        <div class="bg-red-100 p-3 rounded-full mr-4">
                            <i class="fas fa-file-pdf text-red-600 text-xl"></i>
                        </div>
                        <h4 class="font-medium text-lg">Panduan Dasar Pengguna</h4>
                    </div>
                    <p class="text-gray-600 mb-4 text-sm md:text-base">Dokumen panduan untuk memulai menggunakan aplikasi Symadu</p>
                    <div class="flex items-center gap-3">
                        <a href="#" class="text-emerald-600 hover:text-emerald-700 flex items-center bg-emerald-50 py-2 px-3 rounded-md transition-colors inline-flex hover:shadow">
                        <span>Unduh PDF</span>
                        <i class="fas fa-download ml-2"></i>
                    </a>
                        @if(auth()->user() && auth()->user()->role_id === 1)
                        <button onclick="editDocument(1)" class="text-yellow-600 hover:text-yellow-700 flex items-center bg-yellow-50 py-2 px-3 rounded-md transition-colors inline-flex hover:shadow">
                            <i class="fas fa-edit mr-1"></i>
                            <span>Edit</span>
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Guide 2 -->
                <div class="bg-white rounded-lg p-5 border border-gray-200 hover:shadow-lg transition-all card-hover">
                    <div class="flex items-center mb-3">
                        <div class="bg-red-100 p-3 rounded-full mr-4">
                            <i class="fas fa-file-pdf text-red-600 text-xl"></i>
                        </div>
                        <h4 class="font-medium text-lg">Panduan Fitur Lanjutan</h4>
                    </div>
                    <p class="text-gray-600 mb-4 text-sm md:text-base">Dokumentasi lengkap fitur-fitur lanjutan di Symadu</p>
                    <div class="flex items-center gap-3">
                        <a href="#" class="text-emerald-600 hover:text-emerald-700 flex items-center bg-emerald-50 py-2 px-3 rounded-md transition-colors inline-flex hover:shadow">
                        <span>Unduh PDF</span>
                        <i class="fas fa-download ml-2"></i>
                    </a>
                        @if(auth()->user() && auth()->user()->role_id === 1)
                        <button onclick="editDocument(2)" class="text-yellow-600 hover:text-yellow-700 flex items-center bg-yellow-50 py-2 px-3 rounded-md transition-colors inline-flex hover:shadow">
                            <i class="fas fa-edit mr-1"></i>
                            <span>Edit</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="animate-fadeIn delay-300">
            <h3 class="text-xl section-heading">Kontak Bantuan</h3>
            <div class="bg-gray-50 rounded-lg p-5 md:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-all">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Email Support -->
                    <div class="flex items-start">
                        <div class="bg-emerald-100 p-3 rounded-full mr-4 flex-shrink-0">
                            <i class="fas fa-envelope text-emerald-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-lg mb-2">Email Support</h4>
                            <p class="text-gray-600 flex items-center">
                                <a href="mailto:support@symadu.com" class="hover:text-emerald-600 transition-colors">support@symadu.com</a>
                                <i class="fas fa-external-link-alt ml-2 text-xs text-gray-400"></i>
                            </p>
                            <p class="text-gray-500 text-sm mt-1">Respon dalam 1-2 hari kerja</p>
                        </div>
                    </div>

                    <!-- Phone Support -->
                    <div class="flex items-start">
                        <div class="bg-emerald-100 p-3 rounded-full mr-4 flex-shrink-0">
                            <i class="fas fa-phone-alt text-emerald-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-lg mb-2">Telepon</h4>
                            <p class="text-gray-600 flex items-center">
                                <a href="tel:+6212345678890" class="hover:text-emerald-600 transition-colors">+62 123 4567 890</a>
                                <i class="fas fa-phone ml-2 text-xs text-gray-400"></i>
                            </p>
                            <p class="text-gray-500 text-sm mt-1">Senin-Jumat, 08.00-17.00 WIB</p>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Modal Kelola Dokumen -->
@if(auth()->user() && auth()->user()->role_id === 1)
<div id="documentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4 md:p-6 lg:p-8">
    <div class="bg-white rounded-lg w-full max-w-4xl relative overflow-hidden flex flex-col my-8 md:my-10 shadow-2xl">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <h3 class="text-xl font-bold text-emerald-700">Kelola Dokumen Panduan</h3>
            <button type="button" class="absolute top-6 right-6 text-gray-600 hover:text-gray-900 p-1 rounded-full hover:bg-gray-200 transition-colors" onclick="closeDocumentModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200 px-6 bg-white">
            <button
                id="tabDaftarDokumen"
                class="py-3 px-5 border-b-2 border-emerald-500 text-emerald-600 font-medium"
                onclick="switchDocumentTab('daftarDokumen')"
            >
                Daftar Dokumen
            </button>
            <button
                id="tabTambahDokumen"
                class="py-3 px-5 border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                onclick="switchDocumentTab('tambahDokumen')"
            >
                Tambah Dokumen Baru
            </button>
        </div>

        <!-- Content Area - Scrollable -->
        <div class="flex-1 overflow-y-auto px-6 py-6" style="max-height: 60vh; min-height: 350px;">
            <!-- Daftar Dokumen Tab Content -->
            <div id="daftarDokumenContent" class="space-y-6 pb-4">
                <!-- Document list akan dirender secara dinamis di sini -->
            </div>

            <!-- Tambah Dokumen Tab Content -->
            <div id="tambahDokumenContent" class="hidden pb-4">
                <form id="documentForm" class="space-y-5" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="documentId" name="document_id" value="">
                    <input type="hidden" id="isNewDocument" name="is_new_document" value="1">

                    <div class="space-y-2">
                        <label for="documentTitle" class="block font-medium">Judul Dokumen</label>
                        <input type="text" id="documentTitle" name="title" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:ring-emerald-300">
                </div>

                    <div class="space-y-2">
                        <label for="documentDescription" class="block font-medium">Deskripsi Dokumen</label>
                        <textarea id="documentDescription" name="description" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:ring-emerald-300" rows="3"></textarea>
                </div>

                    <div class="space-y-2">
                        <label for="documentFile" class="block font-medium">File PDF</label>
                        <div class="flex items-center">
                            <input type="file" id="documentFile" name="document_file" class="hidden" accept=".pdf">
                            <button type="button" onclick="document.getElementById('documentFile').click()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-md inline-flex items-center">
                                <i class="fas fa-upload mr-2"></i> Pilih File
                            </button>
                            <span id="selectedFileName" class="ml-3 text-gray-600 text-sm"></span>
            </div>
                        <p class="text-xs text-gray-500 mt-1">Format file: PDF, ukuran maksimal 10MB</p>
        </div>

                    <div id="currentDocumentFile" class="hidden space-y-2 p-3 border border-gray-200 rounded-md bg-gray-50">
                        <p class="font-medium">File PDF Saat Ini:</p>
                        <div class="flex items-center">
                            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                            <span id="currentFileName">filename.pdf</span>
                            <a id="currentFileLink" href="#" target="_blank" class="ml-auto text-emerald-600 hover:text-emerald-700">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                Lihat
                            </a>
    </div>
</div>

                    <div id="documentSaveStatus" class="hidden p-3 rounded-md text-sm"></div>
                </form>
            </div>
        </div>

        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <div id="documentFormButtons">
                <button
                    type="button"
                    id="saveDocumentBtn"
                    class="w-full bg-emerald-600 text-white py-3 rounded-md hover:bg-emerald-700 transition-colors font-medium shadow-md"
                >
                    <i class="fas fa-save mr-2"></i> Simpan Dokumen
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Modal Edit Video Tutorial untuk Super Admin -->
@if(auth()->user() && auth()->user()->role_id === 1)
<!-- Modal Kelola Video (Unified Modal) -->
<div id="videoModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4 md:p-6 lg:p-8">
    <div class="bg-white rounded-lg w-full max-w-4xl relative overflow-hidden flex flex-col my-8 md:my-10 shadow-2xl">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <h3 class="text-xl font-bold text-emerald-700">Kelola Video Tutorial</h3>
            <button type="button" class="absolute top-6 right-6 text-gray-600 hover:text-gray-900 p-1 rounded-full hover:bg-gray-200 transition-colors" onclick="closeKelolalVideoModal()">
            <i class="fas fa-times"></i>
        </button>
        </div>

        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200 px-6 bg-white">
            <button
                id="tabDaftarVideo"
                class="py-3 px-5 border-b-2 border-emerald-500 text-emerald-600 font-medium"
                onclick="switchTab('daftarVideo')"
            >
                Daftar Video
            </button>
            <button
                id="tabTambahVideo"
                class="py-3 px-5 border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                onclick="switchTab('tambahVideo')"
            >
                Tambah Video Baru
            </button>
        </div>

        <!-- Content Area - Scrollable -->
        <div class="flex-1 overflow-y-auto px-6 py-6" style="max-height: 60vh; min-height: 350px;">
            <!-- Daftar Video Tab Content -->
            <div id="daftarVideoContent" class="space-y-6 pb-4">
                <!-- Video list akan dirender secara dinamis di sini -->
            </div>

            <!-- Tambah Video Tab Content -->
            <div id="tambahVideoContent" class="hidden pb-4">
                <form id="videoForm" class="space-y-5">
            @csrf
                    <input type="hidden" id="videoId" name="video_id" value="">
                    <input type="hidden" id="isNewVideo" name="is_new_video" value="1">

            <div class="space-y-2">
                <label for="videoTitle" class="block font-medium">Judul Video</label>
                        <input type="text" id="videoTitle" name="title" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:ring-emerald-300">
            </div>

            <div class="space-y-2">
                <label for="videoUrl" class="block font-medium">URL Video YouTube</label>
                        <input type="text" id="videoUrl" name="url" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:ring-emerald-300" placeholder="https://www.youtube.com/watch?v=VIDEO_ID" onchange="updateThumbnail()">
                        <p class="text-xs text-gray-500">Masukkan URL YouTube (https://www.youtube.com/watch?v=VIDEO_ID atau https://youtu.be/VIDEO_ID)</p>
            </div>

            <div class="space-y-2">
                        <label for="videoDescription" class="block font-medium">Deskripsi Video</label>
                        <textarea id="videoDescription" name="description" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:ring-emerald-300" rows="3"></textarea>
                    </div>

                    <div class="space-y-2">
                        <label class="block font-medium">Thumbnail Video</label>
                        <div class="flex flex-col md:flex-row items-start gap-4">
                            <div>
                                <img id="currentThumbnail" src="/path/to/placeholder.jpg" alt="Thumbnail" class="w-32 h-20 object-cover border rounded-md mb-2">
                                <div class="text-sm text-gray-600 mb-3">Thumbnail otomatis dari YouTube</div>

                                <div class="flex flex-col gap-2">
                                    <div>
                                        <input type="file" id="customThumbnail" name="custom_thumbnail" class="hidden" accept="image/*" onchange="previewCustomThumbnail(this)">
                                        <button type="button" onclick="document.getElementById('customThumbnail').click()" class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded-md text-sm inline-flex items-center">
                                            <i class="fas fa-upload mr-1"></i> Unggah Thumbnail Kustom
                        </button>
                    </div>

                                    <button type="button" id="resetThumbnailBtn" onclick="resetThumbnail()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-1 px-3 rounded-md text-sm inline-flex items-center">
                                        <i class="fas fa-undo mr-1"></i> Gunakan Thumbnail YouTube
                                    </button>
                </div>
            </div>

                            <div id="customThumbnailPreviewContainer" class="hidden">
                                <div class="border-l pl-4 md:border-l-0 md:border-t-0 md:pl-0">
                                    <div class="font-medium text-sm mb-2">Thumbnail Kustom:</div>
                                    <img id="customThumbnailPreview" src="" alt="Custom Thumbnail" class="w-32 h-20 object-cover border rounded-md">
                                    <div class="text-xs text-gray-500 mt-1">Ukuran disarankan: 1280x720 pixel</div>
            </div>
                            </div>
                        </div>
                        <input type="hidden" id="thumbnailUrl" name="thumbnail_url">
                        <input type="hidden" id="isCustomThumbnail" name="is_custom_thumbnail" value="0">
                </div>
                    <div id="saveStatus" class="hidden p-3 rounded-md text-sm"></div>
        </form>
    </div>
</div>

        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <div id="formButtons">
                <button
                    type="button"
                    id="saveVideoBtn"
                    class="w-full bg-emerald-600 text-white py-3 rounded-md hover:bg-emerald-700 transition-colors font-medium shadow-md"
                >
                    <i class="fas fa-save mr-2"></i> Simpan Video
        </button>
        </div>
                    </div>
                </div>
            </div>

<!-- Delete Unused Modals -->
@endif

<!-- Modal Video Player -->
<div id="videoPlayerModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-lg w-full max-w-4xl shadow-2xl">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 id="videoPlayerTitle" class="font-bold text-lg">Judul Video</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700 p-1 rounded-full hover:bg-gray-200 transition-colors" onclick="closeVideoModal()">
                <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
        <div class="aspect-w-16 aspect-h-9">
            <div id="videoPlayerContainer" class="w-full h-full"></div>
                </div>
            </div>
                    </div>

<!-- Modal Lihat Semua Video -->
<div id="allVideosModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4 md:p-6 lg:p-8">
    <div class="bg-white rounded-lg w-full max-w-5xl relative overflow-hidden flex flex-col my-8 md:my-10 shadow-2xl">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="text-xl font-bold text-emerald-700">Semua Video Tutorial</h3>
            <button type="button" class="text-gray-600 hover:text-gray-900 p-1 rounded-full hover:bg-gray-200 transition-colors" onclick="closeAllVideosModal()">
                <i class="fas fa-times"></i>
                        </button>
                    </div>

        <div class="overflow-y-auto p-6" style="max-height: 70vh;">
            <div id="allVideosList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Video list akan dirender secara dinamis di sini -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Pastikan halaman dapat di-scroll jika konten terlalu panjang */
    html, body {
        height: 100%;
        overflow-x: hidden;
    }

    /* Style untuk scroll */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #666;
    }

    /* Style untuk slider */
    #videoSlides {
        width: 100%;
        transition: transform 0.5s ease;
    }

    .carousel-card {
        transition: all 0.3s ease;
    }

    .carousel-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Style untuk play button */
    .play-btn {
        opacity: 0.9;
        transition: all 0.2s ease;
    }

    .video-container:hover .play-btn {
        opacity: 1;
    }

    .play-btn > div {
        transition: all 0.2s ease;
    }

    .play-btn:hover > div {
        transform: scale(1.1);
    }

    /* Style untuk container video iframe */
    .video-container {
        position: relative;
        width: 100%;
        height: 0;
        padding-bottom: 56.25%; /* 16:9 aspect ratio */
        overflow: hidden;
    }

    .video-container img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }

    /* Style untuk tombol navigasi slider */
    #prevBtn, #nextBtn {
        display: flex !important;
        opacity: 1 !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        z-index: 30;
        cursor: pointer;
        width: 40px;
        height: 40px;
        align-items: center;
        justify-content: center;
    }

    @media (min-width: 768px) {
        #prevBtn, #nextBtn {
            width: 50px;
            height: 50px;
        }
    }

    #prevBtn:hover, #nextBtn:hover {
        transform: scale(1.1);
        transition: transform 0.2s ease;
    }

    /* Membuat kontainer relatif agar tombol navigasi terposisi dengan benar */
    .relative:hover #prevBtn,
    .relative:hover #nextBtn {
        opacity: 1 !important;
    }

    /* Menyembunyikan dropdown menu yang tidak diinginkan */
    .dropdown-menu {
        display: none !important;
    }

    /* Style untuk section heading (disederhanakan) */
    .section-heading {
        font-weight: 600;
        color: #10b981;
        margin-bottom: 1.5rem;
    }

    /* Card dan button styles */
    .card-hover {
        transition: all 0.3s ease;
    }

    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .btn-primary {
        background-color: #10b981;
        color: white;
        transition: all 0.2s ease;
    }

    .btn-primary:hover {
        background-color: #059669;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Animation keyframes */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-fadeIn {
        animation: fadeIn 0.5s ease-out forwards;
    }

    /* Media queries untuk perangkat mobile */
    @media (max-width: 640px) {
        .carousel-card {
            margin-bottom: 1rem;
        }

        h2, h3 {
            text-align: left;
        }

        .flex-col-reverse-mobile {
            flex-direction: column-reverse;
        }
    }

    /* Animation delays for staggered entrance */
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
    .delay-500 { animation-delay: 0.5s; }
</style>
@endpush

@push('scripts')
<script src="https://www.youtube.com/iframe_api"></script>
<script>
    // YouTube player variable
    let ytPlayer;

    // Function to preview custom thumbnail
    function previewCustomThumbnail(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                // Simpan thumbnail kustom sebagai URL data
                document.getElementById('customThumbnailPreview').src = e.target.result;
                document.getElementById('thumbnailUrl').value = e.target.result;
                document.getElementById('isCustomThumbnail').value = '1';

                // Tampilkan container preview
                document.getElementById('customThumbnailPreviewContainer').classList.remove('hidden');
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    // Function to reset to YouTube thumbnail
    function resetThumbnail() {
        document.getElementById('isCustomThumbnail').value = '0';
        document.getElementById('customThumbnailPreviewContainer').classList.add('hidden');
        document.getElementById('customThumbnail').value = '';

        // Kembalikan ke thumbnail YouTube
        updateThumbnail();
    }

    // Array untuk menyimpan data video tutorial
    let videoData = [
        {
            id: 1,
            title: 'Cara Menggunakan Peta',
            description: 'Tutorial lengkap cara menggunakan fitur peta interaktif',
            url: 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            thumbnail: 'thumbnail-1.jpg'
        },
        {
            id: 2,
            title: 'Cara Mengelola Data Pohon',
            description: 'Panduan lengkap untuk menambah, mengedit, dan menghapus data pohon',
            url: 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            thumbnail: 'thumbnail-2.jpg'
        },
        {
            id: 3,
            title: 'Cara Menggunakan Dashboard',
            description: 'Panduan lengkap untuk memahami dan memanfaatkan dashboard',
            url: 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            thumbnail: 'thumbnail-3.jpg'
        },
    ];

    // Load data video dari localStorage jika ada
    const savedVideoData = localStorage.getItem('symadu_video_data');
    if (savedVideoData) {
        try {
            videoData = JSON.parse(savedVideoData);
        } catch (e) {
            console.error('Error loading video data from localStorage', e);
        }
    }

    // Variabel untuk slider
    let currentSlide = 0;

    // Sembunyikan semua dropdown menu saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Sembunyikan dropdown menu yang mungkin terbuka
        const dropdowns = document.querySelectorAll('.dropdown-menu, .dropdown-content');
        dropdowns.forEach(function(dropdown) {
            dropdown.style.display = 'none';
            dropdown.classList.add('hidden');
        });

        // Override fungsi dropdown jika ada
        if (window.toggleDropdown) {
            const originalToggle = window.toggleDropdown;
            window.toggleDropdown = function(event) {
                // Hanya jalankan untuk dropdown yang bukan di halaman bantuan
                if (!window.location.pathname.includes('bantuan')) {
                    originalToggle(event);
                } else {
                    event.preventDefault();
                }
            };
        }

        // Tambahkan event listener untuk tombol simpan video
        const saveVideoBtn = document.getElementById('saveVideoBtn');
        if (saveVideoBtn) {
            saveVideoBtn.addEventListener('click', function(e) {
                e.preventDefault();
                saveVideo();
            });
        }

        // Tambahkan event listener untuk tombol simpan dokumen
        const saveDocumentBtn = document.getElementById('saveDocumentBtn');
        if (saveDocumentBtn) {
            saveDocumentBtn.addEventListener('click', function(e) {
                e.preventDefault();
                saveDocument();
            });
        }

        // Tambahkan event listener untuk file input
        const documentFileInput = document.getElementById('documentFile');
        if (documentFileInput) {
            documentFileInput.addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : '';
                document.getElementById('selectedFileName').textContent = fileName;
            });
        }

        // Tambahkan event listener untuk tombol Lihat Semua Video
        const viewAllBtn = document.querySelector('button[onclick="openAllVideosModal()"]');
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openAllVideosModal();
            });
        }

        // Tambahkan event listener untuk tombol Kelola Dokumen
        const manageDocBtn = document.querySelector('button[onclick="openDocumentModal()"]');
        if (manageDocBtn) {
            manageDocBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openDocumentModal();
            });
        }

        // Tambahkan event listener untuk tombol navigasi slider
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if (prevBtn) {
            prevBtn.onclick = function(e) {
                e.preventDefault();
                changeSlide(-1);
                return false;
            };
        }

        if (nextBtn) {
            nextBtn.onclick = function(e) {
                e.preventDefault();
                changeSlide(1);
                return false;
            };
        }

        // Update tampilan carousel dengan video dari localStorage
        setTimeout(() => {
            updateVideoCarousel();
            initDocuments(); // Inisialisasi daftar dokumen

            // Inisialisasi daftar video jika ada di localStorage
            if (savedVideoData) {
                renderVideoList();
            }

            // Debug - periksa data dokumen
            console.log('Data dokumen tersedia:', documentData);
        }, 100);
    });

    // Array untuk menyimpan data dokumen panduan
    let documentData = [
        {
            id: 1,
            title: 'Panduan Dasar Pengguna',
            description: 'Dokumen panduan untuk memulai menggunakan aplikasi Symadu',
            file_url: '/documents/panduan-dasar.pdf',
            file_name: 'panduan-dasar.pdf'
        },
        {
            id: 2,
            title: 'Panduan Fitur Lanjutan',
            description: 'Dokumentasi lengkap fitur-fitur lanjutan di Symadu',
            file_url: '/documents/panduan-lanjutan.pdf',
            file_name: 'panduan-lanjutan.pdf'
        }
    ];

    // Load data dokumen dari localStorage jika ada
    const savedDocumentData = localStorage.getItem('symadu_document_data');
    if (savedDocumentData) {
        try {
            documentData = JSON.parse(savedDocumentData);
        } catch (e) {
            console.error('Error loading document data from localStorage', e);
        }
    }

    // Fungsi untuk cek apakah user adalah admin
    function isAdmin() {
        // Cek apakah user adalah superadmin (role_id === 1)
        return document.querySelector('button[onclick="openDocumentModal()"]') !== null;
    }

    // Fungsi untuk membuka modal dokumen
    function openDocumentModal() {
        console.log('Membuka modal dokumen...');
        const modal = document.getElementById('documentModal');
        if (!modal) {
            console.error('Modal dokumen tidak ditemukan!');
            return;
        }

        console.log('Data dokumen saat ini:', documentData);

        // Tampilkan modal
        modal.classList.remove('hidden');

        // Default ke tab Daftar Dokumen
        switchDocumentTab('daftarDokumen');

        // Debug - periksa apakah container daftar dokumen ada
        const container = document.getElementById('daftarDokumenContent');
        console.log('Container daftar dokumen ditemukan:', !!container);
    }

    // Fungsi untuk menutup modal kelola dokumen
    function closeDocumentModal() {
        const modal = document.getElementById('documentModal');

        // Kembalikan kondisi tombol simpan jika masih dalam proses
        resetSaveDocumentButton();

        // Sembunyikan modal
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // Fungsi untuk beralih tab dokumen
    function switchDocumentTab(tabName) {
        // Reset semua tab
        document.getElementById('tabDaftarDokumen').classList.remove('border-emerald-500', 'text-emerald-600');
        document.getElementById('tabDaftarDokumen').classList.add('border-transparent', 'text-gray-500');
        document.getElementById('tabTambahDokumen').classList.remove('border-emerald-500', 'text-emerald-600');
        document.getElementById('tabTambahDokumen').classList.add('border-transparent', 'text-gray-500');

        document.getElementById('daftarDokumenContent').classList.add('hidden');
        document.getElementById('tambahDokumenContent').classList.add('hidden');

        // Set active tab
        if (tabName === 'daftarDokumen') {
            document.getElementById('tabDaftarDokumen').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('tabDaftarDokumen').classList.add('border-emerald-500', 'text-emerald-600');
            document.getElementById('daftarDokumenContent').classList.remove('hidden');

            // Render daftar dokumen di modal
            renderDocumentListInModal();
        } else {
            document.getElementById('tabTambahDokumen').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('tabTambahDokumen').classList.add('border-emerald-500', 'text-emerald-600');
            document.getElementById('tambahDokumenContent').classList.remove('hidden');

            // Reset form untuk tambah baru
            resetDocumentForm();
        }
    }

    // Fungsi untuk inisialisasi daftar dokumen
    function initDocuments() {
        renderDocumentList();
    }

    // Fungsi untuk render daftar dokumen di halaman utama
    function renderDocumentList() {
        const container = document.getElementById('documentList');
        if (!container) return;

        // Kosongkan container
        container.innerHTML = '';

        documentData.forEach(doc => {
            const documentItem = document.createElement('div');
            documentItem.className = 'bg-white rounded-lg p-5 border border-gray-200 hover:shadow-lg transition-all card-hover';
            documentItem.innerHTML = `
                <div class="flex items-center mb-3">
                    <div class="bg-red-100 p-3 rounded-full mr-4">
                        <i class="fas fa-file-pdf text-red-600 text-xl"></i>
                    </div>
                    <h4 class="font-medium text-lg">${doc.title}</h4>
                </div>
                <p class="text-gray-600 mb-4 text-sm md:text-base">${doc.description}</p>
                <div class="flex items-center gap-3">
                    <a href="${doc.file_url}" target="_blank" class="text-emerald-600 hover:text-emerald-700 flex items-center bg-emerald-50 py-2 px-3 rounded-md transition-colors inline-flex hover:shadow">
                        <span>Unduh PDF</span>
                        <i class="fas fa-download ml-2"></i>
                    </a>
                </div>
            `;
            container.appendChild(documentItem);
        });
    }

    // Fungsi untuk reset form dokumen
    function resetDocumentForm() {
        document.getElementById('documentId').value = '';
        document.getElementById('documentTitle').value = '';
        document.getElementById('documentDescription').value = '';
        document.getElementById('documentFile').value = '';
        document.getElementById('selectedFileName').textContent = '';
        document.getElementById('isNewDocument').value = '1';

        // Sembunyikan info file saat ini
        document.getElementById('currentDocumentFile').classList.add('hidden');
    }

    // Fungsi untuk edit dokumen
    function editDocument(documentId) {
        // Cari dokumen berdasarkan id
        const doc = documentData.find(d => d.id === documentId);
        if (!doc) return;

        // Buka modal dokumen
        openDocumentModal();

        // Switch ke tab tambah/edit dokumen
        switchDocumentTab('tambahDokumen');

        // Isi form dengan data dokumen
        document.getElementById('documentId').value = doc.id;
        document.getElementById('documentTitle').value = doc.title;
        document.getElementById('documentDescription').value = doc.description;
        document.getElementById('isNewDocument').value = '0';

        // Tampilkan info file saat ini
        const currentDocFile = document.getElementById('currentDocumentFile');
        currentDocFile.classList.remove('hidden');

        document.getElementById('currentFileName').textContent = doc.file_name;
        document.getElementById('currentFileLink').href = doc.file_url;
    }

    // Fungsi untuk menghapus dokumen
    function deleteDocument(documentId) {
        if (confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) {
            // Hapus dari array
            const index = documentData.findIndex(d => d.id === documentId);
            if (index !== -1) {
                documentData.splice(index, 1);

                // Render ulang daftar dokumen
                renderDocumentList();

                // Simpan ke localStorage
                localStorage.setItem('symadu_document_data', JSON.stringify(documentData));

                // Ajax request untuk menyimpan ke server
                saveDocumentToServer('delete', { id: documentId });

                // Tampilkan pesan sukses
                showDocumentSaveStatus('Dokumen berhasil dihapus', 'success');
            }
        }
    }

    // Fungsi untuk menyimpan dokumen
    function saveDocument() {
        // Nonaktifkan tombol simpan untuk mencegah double-click
        const saveButton = document.getElementById('saveDocumentBtn');
        if (saveButton) {
            // Jika tombol sudah dalam proses, abaikan klik berikutnya
            if (saveButton.classList.contains('processing')) {
                return;
            }

            // Tandai tombol sedang dalam proses
            saveButton.classList.add('processing');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...';
        }

        const documentId = document.getElementById('documentId').value;
        const documentTitle = document.getElementById('documentTitle').value;
        const documentDesc = document.getElementById('documentDescription').value;
        const isNewDocument = document.getElementById('isNewDocument').value === '1';
        const documentFile = document.getElementById('documentFile').files[0];

        // Validasi input
        if (!documentTitle) {
            showDocumentSaveStatus('Judul dokumen harus diisi', 'error');
            resetSaveDocumentButton();
            return;
        }

        // Validasi file untuk dokumen baru
        if (isNewDocument && !documentFile) {
            showDocumentSaveStatus('File PDF harus diunggah', 'error');
            resetSaveDocumentButton();
            return;
        }

        // Objek data untuk dikirim/disimpan
        const documentDataToSave = {
            title: documentTitle,
            description: documentDesc
        };

        // Simulasikan proses upload file
        let fileUrl = '';
        let fileName = '';

        if (documentFile) {
            // Simulasi URL file (dalam produksi ini akan diganti dengan URL yang sebenarnya)
            fileName = documentFile.name;
            fileUrl = `/documents/${fileName}`;
        } else if (!isNewDocument) {
            // Gunakan file yang sudah ada jika tidak ada file baru diunggah
            const existingDoc = documentData.find(d => d.id === parseInt(documentId));
            if (existingDoc) {
                fileUrl = existingDoc.file_url;
                fileName = existingDoc.file_name;
            }
        }

        documentDataToSave.file_url = fileUrl;
        documentDataToSave.file_name = fileName;

        // Simulasikan proses penyimpanan
        if (isNewDocument) {
            // Tambah dokumen baru
            const newId = documentData.length > 0 ? Math.max(...documentData.map(d => d.id)) + 1 : 1;
            documentDataToSave.id = newId;

            documentData.push(documentDataToSave);
            showDocumentSaveStatus('Dokumen baru berhasil ditambahkan', 'success');

            // Ajax request untuk menyimpan ke server
            saveDocumentToServer('add', documentDataToSave);
        } else {
            // Update dokumen yang ada
            const idInt = parseInt(documentId);
            documentDataToSave.id = idInt;

            const index = documentData.findIndex(d => d.id === idInt);
            if (index !== -1) {
                documentData[index] = documentDataToSave;

                showDocumentSaveStatus('Dokumen berhasil diperbarui', 'success');

                // Ajax request untuk menyimpan ke server
                saveDocumentToServer('update', documentDataToSave);
            }
        }

        // Simpan ke localStorage
        localStorage.setItem('symadu_document_data', JSON.stringify(documentData));

        // Render ulang daftar dokumen
        renderDocumentList();

        // Tutup modal setelah waktu singkat
        setTimeout(() => {
            closeDocumentModal();
            resetSaveDocumentButton();
        }, 500);
    }

    // Fungsi untuk mengembalikan tombol simpan dokumen ke kondisi awal
    function resetSaveDocumentButton() {
        const saveButton = document.getElementById('saveDocumentBtn');
        if (saveButton) {
            saveButton.classList.remove('processing');
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fas fa-save mr-2"></i> Simpan Dokumen';
        }
    }

    // Fungsi untuk menampilkan status simpan dokumen
    function showDocumentSaveStatus(message, type) {
        const statusElement = document.getElementById('documentSaveStatus');
        statusElement.textContent = message;
        statusElement.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');

        if (type === 'error') {
            statusElement.classList.add('bg-red-100', 'text-red-700');
        } else {
            statusElement.classList.add('bg-green-100', 'text-green-700');
        }

        statusElement.classList.remove('hidden');

        // Sembunyikan pesan setelah 3 detik
        setTimeout(() => {
            statusElement.classList.add('hidden');
        }, 3000);
    }

    // Fungsi untuk menyimpan data dokumen ke server dengan Ajax
    function saveDocumentToServer(action, data) {
        // Dapatkan CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content') ||
                          document.querySelector('input[name="_token"]').value;

        // Kirim data dengan fetch API
        fetch('/api/documents', {
                        method: 'POST',
                        headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: action,
                data: data
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(responseData => {
            console.log('Data berhasil disimpan ke server:', responseData);
        })
        .catch(error => {
            console.warn('Gagal menyimpan data ke server:', error);
            // Data tetap tersimpan di localStorage sebagai fallback
        });
    }

    // Fungsi untuk mengubah slide
    function changeSlide(direction) {
        // Dapatkan total slides yang sebenarnya
        const slideElements = document.querySelectorAll('#videoSlides > div');
        const actualTotalSlides = slideElements.length;

        if (actualTotalSlides <= 1) return; // Jangan lakukan apa-apa jika hanya ada 1 slide

        currentSlide = currentSlide + direction;

        // Validasi batasan slide
        if (currentSlide >= actualTotalSlides) {
            currentSlide = 0;
        }
        if (currentSlide < 0) {
            currentSlide = actualTotalSlides - 1;
        }

        // Update slider dengan animasi smooth
        const slides = document.getElementById('videoSlides');
        slides.style.transform = `translateX(-${currentSlide * 100}%)`;

        // Update indikator
        const indicators = document.querySelectorAll('#slideIndicators span');
        indicators.forEach((dot, index) => {
            if (index === currentSlide) {
                dot.classList.add('bg-emerald-600');
                dot.classList.remove('bg-gray-300');
            } else {
                dot.classList.add('bg-gray-300');
                dot.classList.remove('bg-emerald-600');
            }
        });

        console.log(`Slide diubah ke ${currentSlide} dari total ${actualTotalSlides} slides`);
    }

    // Fungsi untuk menuju slide spesifik
    function goToSlide(slideIndex) {
        currentSlide = slideIndex;
        updateSlider();
    }

    // Update tampilan slider
    function updateSlider() {
        const slides = document.getElementById('videoSlides');
        slides.style.transform = `translateX(-${currentSlide * 100}%)`;

        // Update indikator
        const indicators = document.querySelectorAll('#slideIndicators span');
        indicators.forEach((dot, index) => {
            if (index === currentSlide) {
                dot.classList.add('bg-emerald-600');
                dot.classList.remove('bg-gray-300');
                    } else {
                dot.classList.add('bg-gray-300');
                dot.classList.remove('bg-emerald-600');
                }
            });
        }

    // Fungsi untuk beralih tab
    function switchTab(tabName) {
        // Reset semua tab
        document.getElementById('tabDaftarVideo').classList.remove('border-emerald-500', 'text-emerald-500');
        document.getElementById('tabDaftarVideo').classList.add('border-transparent', 'text-gray-500');
        document.getElementById('tabTambahVideo').classList.remove('border-emerald-500', 'text-emerald-500');
        document.getElementById('tabTambahVideo').classList.add('border-transparent', 'text-gray-500');

        document.getElementById('daftarVideoContent').classList.add('hidden');
        document.getElementById('tambahVideoContent').classList.add('hidden');

        // Set active tab
        if (tabName === 'daftarVideo') {
            document.getElementById('tabDaftarVideo').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('tabDaftarVideo').classList.add('border-emerald-500', 'text-emerald-500');
            document.getElementById('daftarVideoContent').classList.remove('hidden');

            // Render daftar video
            renderVideoList();
        } else {
            document.getElementById('tabTambahVideo').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('tabTambahVideo').classList.add('border-emerald-500', 'text-emerald-500');
            document.getElementById('tambahVideoContent').classList.remove('hidden');

            // Reset form untuk tambah baru
            resetForm();
        }
    }

    // Fungsi untuk render daftar video
    function renderVideoList() {
        console.log('Merender daftar video...');
        const container = document.getElementById('daftarVideoContent');
        if (!container) {
            console.error('Container daftarVideoContent tidak ditemukan!');
            return;
        }

        console.log('Jumlah video tersedia:', videoData.length);
        container.innerHTML = '';

        if (videoData.length === 0) {
            container.innerHTML = '<div class="text-center py-6 text-gray-500">Tidak ada video tutorial yang tersedia.</div>';
            return;
        }

        videoData.forEach(video => {
            // Dapatkan thumbnail dari YouTube
            const ytId = extractYoutubeId(video.url);
            // Gunakan thumbnail kustom jika tersedia, atau thumbnail YouTube
            const thumbnailUrl = video.thumbnail && video.thumbnail !== 'thumbnail-' + video.id + '.jpg' ?
                video.thumbnail :
                (ytId ? `https://img.youtube.com/vi/${ytId}/hqdefault.jpg` : '/path/to/placeholder.jpg');
            const youtubeUrl = ytId ? `https://www.youtube.com/watch?v=${ytId}` : '#';

            const videoItem = document.createElement('div');
            videoItem.className = 'video-item bg-white p-5 rounded-lg border border-gray-200 hover:shadow-lg transition-all mb-5';
            videoItem.innerHTML = `
                <div class="flex flex-col md:flex-row gap-5">
                    <div class="w-full md:w-1/3">
                        <div class="aspect-w-16 aspect-h-9 mb-3 relative overflow-hidden rounded-md shadow-sm">
                            <img src="${thumbnailUrl}" alt="${video.title}" class="w-full h-full object-cover rounded-md transition-transform hover:scale-105 duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-70"></div>
                        </div>
                        <div class="text-center">
                            <a href="${youtubeUrl}" target="_blank" class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-md shadow-sm transition-all hover:shadow-md">
                                <i class="fab fa-youtube mr-2"></i> Tonton di YouTube
                            </a>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-lg mb-2">${video.title}</h4>
                        <p class="text-gray-600 mb-4 text-sm md:text-base">${video.description}</p>
                        <div class="flex flex-wrap gap-3">
                            <button type="button"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm flex items-center shadow-sm hover:shadow transition-all"
                                onclick="editVideo(${video.id})">
                                <i class="fas fa-edit mr-2"></i> Edit
                            </button>
                            <button type="button"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm flex items-center shadow-sm hover:shadow transition-all"
                                onclick="deleteVideo(${video.id})">
                                <i class="fas fa-trash mr-2"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(videoItem);
        });
    }

    // Fungsi untuk reset form
    function resetForm() {
        document.getElementById('videoId').value = '';
        document.getElementById('videoTitle').value = '';
        document.getElementById('videoUrl').value = '';
        document.getElementById('videoDescription').value = '';
        document.getElementById('currentThumbnail').src = '/path/to/placeholder.jpg';
        document.getElementById('isNewVideo').value = '1';
        document.getElementById('isCustomThumbnail').value = '0';
        document.getElementById('customThumbnail').value = '';
        document.getElementById('customThumbnailPreviewContainer').classList.add('hidden');
    }

    // Fungsi untuk edit video
    function editVideo(videoId) {
        // Cari video berdasarkan id
        const video = videoData.find(v => v.id === videoId);
        if (!video) return;

        // Switch ke tab tambah/edit video
        switchTab('tambahVideo');

        // Isi form dengan data video
        document.getElementById('videoId').value = video.id;
        document.getElementById('videoTitle').value = video.title;
        document.getElementById('videoUrl').value = video.url;
        document.getElementById('videoDescription').value = video.description;

        // Set thumbnail dari data
        const ytId = extractYoutubeId(video.url);
        const ytThumbnailUrl = ytId ? `https://img.youtube.com/vi/${ytId}/hqdefault.jpg` : '/path/to/placeholder.jpg';

        // Cek apakah menggunakan thumbnail kustom
        if (video.thumbnail && video.thumbnail !== ytThumbnailUrl &&
            (video.thumbnail.startsWith('data:') || !video.thumbnail.includes('youtube'))) {
            // Ini adalah thumbnail kustom
            document.getElementById('currentThumbnail').src = ytThumbnailUrl; // Tetap tampilkan thumbnail YouTube di panel kiri
            document.getElementById('customThumbnailPreview').src = video.thumbnail;
            document.getElementById('thumbnailUrl').value = video.thumbnail;
            document.getElementById('isCustomThumbnail').value = '1';
            document.getElementById('customThumbnailPreviewContainer').classList.remove('hidden');
        } else {
            // Gunakan thumbnail YouTube
            document.getElementById('currentThumbnail').src = ytThumbnailUrl;
            document.getElementById('thumbnailUrl').value = ytThumbnailUrl;
            document.getElementById('isCustomThumbnail').value = '0';
            document.getElementById('customThumbnailPreviewContainer').classList.add('hidden');
        }

        document.getElementById('isNewVideo').value = '0';
    }

    // Fungsi untuk menghapus video
    function deleteVideo(videoId) {
        if (confirm('Apakah Anda yakin ingin menghapus video ini?')) {
            // Implementasi penghapusan video
            const index = videoData.findIndex(v => v.id === videoId);
            if (index !== -1) {
                videoData.splice(index, 1);

                // Perbarui daftar video
                renderVideoList();

                // Simpan ke localStorage
                localStorage.setItem('symadu_video_data', JSON.stringify(videoData));

                // Ajax request untuk menyimpan ke server
                saveToServer('delete', { id: videoId });

                // Perbarui tampilan carousel
                updateVideoCarousel();

                // Jika modal semua video sedang terbuka, perbarui isinya
                const allVideosModal = document.getElementById('allVideosModal');
                if (allVideosModal && !allVideosModal.classList.contains('hidden')) {
                    renderAllVideos();
                }

                showSaveStatus('Video berhasil dihapus', 'success');
            }
        }
    }

    // Fungsi untuk menyimpan video (tambah atau edit)
    function saveVideo() {
        // Nonaktifkan tombol simpan untuk mencegah double-click
        const saveButton = document.getElementById('saveVideoBtn');
        if (saveButton) {
            // Jika tombol sudah dalam proses, abaikan klik berikutnya
            if (saveButton.classList.contains('processing')) {
                return;
            }

            // Tandai tombol sedang dalam proses
            saveButton.classList.add('processing');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...';
        }

        const videoId = document.getElementById('videoId').value;
        const videoTitle = document.getElementById('videoTitle').value;
        const videoUrl = document.getElementById('videoUrl').value;
        const videoDesc = document.getElementById('videoDescription').value;
        const isNewVideo = document.getElementById('isNewVideo').value === '1';
        const thumbnailUrl = document.getElementById('thumbnailUrl').value;
        const isCustomThumbnail = document.getElementById('isCustomThumbnail') && document.getElementById('isCustomThumbnail').value === '1';

        // Validasi input
        if (!videoTitle || !videoUrl) {
            showSaveStatus('Judul dan URL video harus diisi', 'error');
            resetSaveButton();
            return;
        }

        // Ekstrak YouTube ID untuk thumbnail dan URL jika belum ada
        const ytId = extractYoutubeId(videoUrl);
        let finalUrl = videoUrl;

        if (!ytId) {
            showSaveStatus('URL YouTube tidak valid', 'error');
            resetSaveButton();
            return;
        }

        // Pastikan URL dalam format embed
        if (!finalUrl.includes('/embed/')) {
            finalUrl = `https://www.youtube.com/embed/${ytId}`;
        }

        // Gunakan thumbnail kustom jika ada, atau thumbnail YouTube
        let finalThumbnail = thumbnailUrl;
        if (!isCustomThumbnail || !finalThumbnail) {
            finalThumbnail = `https://img.youtube.com/vi/${ytId}/hqdefault.jpg`;
        }

        // Objek data untuk dikirim/disimpan
        const videoDataToSave = {
            title: videoTitle,
            description: videoDesc,
            url: finalUrl,
            thumbnail: finalThumbnail
        };

        // Simulasikan proses penyimpanan
        if (isNewVideo) {
            // Tambah video baru
            const newId = videoData.length > 0 ? Math.max(...videoData.map(v => v.id)) + 1 : 1;
            videoDataToSave.id = newId;

            videoData.push(videoDataToSave);
            showSaveStatus('Video baru berhasil ditambahkan', 'success');

            // Ajax request untuk menyimpan ke server
            saveToServer('add', videoDataToSave);
        } else {
            // Update video yang ada
            const idInt = parseInt(videoId);
            videoDataToSave.id = idInt;

            const index = videoData.findIndex(v => v.id === idInt);
            if (index !== -1) {
                videoData[index] = videoDataToSave;

                // Update iframe pada slider jika perlu
                const videoFrame = document.getElementById(`video-frame-${idInt}`);
                if (videoFrame) {
                    videoFrame.src = finalUrl;
                }

                showSaveStatus('Video berhasil diperbarui', 'success');

                // Ajax request untuk menyimpan ke server
                saveToServer('update', videoDataToSave);
            }
        }

        // Simpan ke localStorage
        localStorage.setItem('symadu_video_data', JSON.stringify(videoData));

        // Perbarui tampilan carousel dengan video terbaru
        updateVideoCarousel();

        // Jika modal semua video sedang terbuka, perbarui isinya
        const allVideosModal = document.getElementById('allVideosModal');
        if (allVideosModal && !allVideosModal.classList.contains('hidden')) {
            renderAllVideos();
        }

        // Perbarui daftar video jika tab daftar video sedang aktif
        const daftarVideoContent = document.getElementById('daftarVideoContent');
        if (daftarVideoContent && !daftarVideoContent.classList.contains('hidden')) {
            renderVideoList();
        }

        // Tutup modal kelola video
        setTimeout(() => {
            closeKelolalVideoModal();
            resetSaveButton();
        }, 1000); // Beri waktu untuk melihat status sukses
    }

    // Fungsi untuk mengembalikan tombol simpan ke kondisi awal
    function resetSaveButton() {
        const saveButton = document.getElementById('saveVideoBtn');
        if (saveButton) {
            saveButton.classList.remove('processing');
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fas fa-save mr-2"></i> Simpan Video';
        }
    }

    // Fungsi untuk menampilkan status simpan
    function showSaveStatus(message, type) {
        const statusElement = document.getElementById('saveStatus');
        statusElement.textContent = message;
        statusElement.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');

        if (type === 'error') {
            statusElement.classList.add('bg-red-100', 'text-red-700');
        } else {
            statusElement.classList.add('bg-green-100', 'text-green-700');
        }

        statusElement.classList.remove('hidden');

        // Sembunyikan pesan setelah 3 detik
        setTimeout(() => {
            statusElement.classList.add('hidden');
        }, 3000);
    }

    // Fungsi untuk menyimpan data ke server dengan Ajax
    function saveToServer(action, data) {
        // Dapatkan CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content') ||
                          document.querySelector('input[name="_token"]').value;

        // Kirim data dengan fetch API
        fetch('/api/tutorials/videos', {
                        method: 'POST',
                        headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: action,
                data: data
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(responseData => {
            console.log('Data berhasil disimpan ke server:', responseData);
        })
        .catch(error => {
            console.warn('Gagal menyimpan data ke server:', error);
            // Data tetap tersimpan di localStorage sebagai fallback
        });
    }

    // Fungsi untuk membuka modal video
    function openVideoModal() {
        document.getElementById('videoModal').classList.remove('hidden');
        // Default ke tab Daftar Video
        switchTab('daftarVideo');
    }

    // Fungsi untuk menutup modal kelola video
    function closeKelolalVideoModal() {
        const modal = document.getElementById('videoModal');

        // Kembalikan kondisi tombol simpan jika masih dalam proses
        resetSaveButton();

        // Sembunyikan modal
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // Fungsi untuk menutup modal video player
    function closeVideoModal() {
        const modal = document.getElementById('videoPlayerModal');
        const container = document.getElementById('videoPlayerContainer');

        // Hapus iframe untuk menghentikan pemutaran
        container.innerHTML = '';

        // Sembunyikan modal
        modal.style.display = 'none';
    }

    // Fungsi untuk membuka modal semua video
    function openAllVideosModal() {
        const modal = document.getElementById('allVideosModal');
        if (modal) {
            modal.classList.remove('hidden');
            renderAllVideos();
        }
    }

    // Fungsi untuk menutup modal semua video
    function closeAllVideosModal() {
        const modal = document.getElementById('allVideosModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // Fungsi untuk menampilkan semua video dalam modal
    function renderAllVideos() {
        const container = document.getElementById('allVideosList');
        if (!container) return;

        container.innerHTML = '';

        if (videoData.length === 0) {
            container.innerHTML = '<p class="text-center text-gray-500 py-8">Tidak ada video yang tersedia saat ini.</p>';
            return;
        }

        videoData.forEach(video => {
            const ytId = extractYoutubeId(video.url);
            // Gunakan thumbnail kustom jika tersedia, atau thumbnail YouTube
            const thumbnailUrl = video.thumbnail && video.thumbnail !== 'thumbnail-' + video.id + '.jpg' ?
                video.thumbnail :
                (ytId ? `https://img.youtube.com/vi/${ytId}/hqdefault.jpg` : '/path/to/placeholder.jpg');

            const videoEl = document.createElement('div');
            videoEl.className = 'video-item flex flex-col md:flex-row gap-4 p-4 border-b border-gray-200 hover:bg-gray-50 transition-colors';
            videoEl.innerHTML = `
                <div class="md:w-1/3">
                    <div class="aspect-w-16 aspect-h-9 cursor-pointer" onclick="openVideoPlayer('${video.url}', '${video.title}')">
                        <img src="${thumbnailUrl}" alt="${video.title}" class="w-full h-full object-cover rounded-md shadow-sm">
                        <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-20 hover:bg-opacity-10 transition-all">
                            <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-md transform hover:scale-110 transition-transform">
                                <i class="fas fa-play text-emerald-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-lg">${video.title}</h4>
                    <p class="text-gray-600 text-sm mt-1">${video.description}</p>
                </div>
            `;
            container.appendChild(videoEl);
        });
    }

    // Function to update video carousel at the top of the page
    function updateVideoCarousel() {
        // Jika tidak ada data video, tidak perlu melakukan apa-apa
        if (!videoData || videoData.length === 0) return;

        // Batasi jumlah video dalam carousel
        const maxVideosPerSlide = 3;
        const totalVideosToShow = Math.min(videoData.length, 6); // Maksimal 6 video (2 slide)

        // Bersihkan slides container
        const slidesContainer = document.getElementById('videoSlides');
        if (!slidesContainer) return;

        slidesContainer.innerHTML = '';

        // Hitung jumlah slide yang diperlukan
        const totalSlides = Math.ceil(totalVideosToShow / maxVideosPerSlide);

        // Buat slide untuk setiap 3 video
        for (let slideIndex = 0; slideIndex < totalSlides; slideIndex++) {
            const slideDiv = document.createElement('div');
            slideDiv.className = 'w-full flex-none grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 py-2';

            // Tambahkan 3 video ke slide ini
            for (let i = 0; i < maxVideosPerSlide; i++) {
                const videoIndex = slideIndex * maxVideosPerSlide + i;
                if (videoIndex < totalVideosToShow) {
                    const video = videoData[videoIndex];

                    // Ekstrak YouTube ID
                    const ytId = extractYoutubeId(video.url);
                    // Gunakan thumbnail kustom jika tersedia, atau thumbnail YouTube
                    const thumbnailUrl = video.thumbnail && video.thumbnail !== 'thumbnail-' + video.id + '.jpg' ?
                        video.thumbnail :
                        (ytId ? `https://img.youtube.com/vi/${ytId}/hqdefault.jpg` : '/path/to/placeholder.jpg');
                    const youtubeUrl = ytId ? `https://www.youtube.com/watch?v=${ytId}` : '#';

                    const videoCard = document.createElement('div');
                    videoCard.className = 'bg-white rounded-lg p-4 border border-gray-200 hover:shadow-lg transition-all carousel-card';
                    videoCard.innerHTML = `
                        <div class="aspect-w-16 aspect-h-9 mb-3 relative video-container overflow-hidden rounded-md shadow-sm">
                            <img src="${thumbnailUrl}" alt="${video.title}" class="w-full h-48 object-cover rounded-md transition-transform hover:scale-105 duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-70"></div>
                            <a href="${youtubeUrl}" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-20 hover:bg-opacity-10 transition-all play-btn">
                                <div class="w-14 h-14 md:w-16 md:h-16 bg-white rounded-full flex items-center justify-center shadow-md transform hover:scale-110 transition-transform">
                                    <i class="fas fa-play text-emerald-600 text-xl"></i>
                                </div>
                            </a>
                        </div>
                        <div>
                            <h4 class="font-medium text-lg">${video.title}</h4>
                            <p class="text-gray-600 mt-1 text-sm md:text-base">${video.description}</p>
                        </div>
                    `;
                    slideDiv.appendChild(videoCard);
                }
            }

            slidesContainer.appendChild(slideDiv);
        }

        // Update indikator dots
        const indicatorsContainer = document.getElementById('slideIndicators');
        if (!indicatorsContainer) return;

        indicatorsContainer.innerHTML = '';

        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('span');
            dot.className = i === 0 ?
                'h-3 w-3 mx-1 rounded-full bg-emerald-600 cursor-pointer transition-all hover:scale-125' :
                'h-3 w-3 mx-1 rounded-full bg-gray-300 cursor-pointer transition-all hover:scale-125';
            dot.onclick = () => goToSlide(i);
            indicatorsContainer.appendChild(dot);
        }

        // Update tombol navigasi - pastikan selalu terlihat
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if (prevBtn) prevBtn.style.display = totalSlides > 1 ? 'flex' : 'none';
        if (nextBtn) nextBtn.style.display = totalSlides > 1 ? 'flex' : 'none';

        // Reset current slide
        currentSlide = 0;

        // Update slider position
        const slides = document.getElementById('videoSlides');
        if (slides) slides.style.transform = `translateX(0%)`;

        console.log(`Carousel diperbarui dengan ${totalVideosToShow} video dalam ${totalSlides} slides`);
    }

    // Fungsi untuk ekstrak ID video dari URL YouTube
    function extractYoutubeId(url) {
        if (!url) return null;

        // Pattern untuk URL YouTube standar dan pendek
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        const match = url.match(regExp);

        return (match && match[2].length === 11) ? match[2] : null;
    }

    // Fungsi untuk update thumbnail berdasarkan URL YouTube
    function updateThumbnail() {
        const videoUrl = document.getElementById('videoUrl').value;
        const ytId = extractYoutubeId(videoUrl);
        const isCustom = document.getElementById('isCustomThumbnail') && document.getElementById('isCustomThumbnail').value === '1';

        // Jika sedang menggunakan thumbnail kustom, jangan ubah
        if (isCustom) {
            return;
        }

        if (ytId) {
            const thumbnailUrl = `https://img.youtube.com/vi/${ytId}/hqdefault.jpg`;
            document.getElementById('currentThumbnail').src = thumbnailUrl;
            document.getElementById('thumbnailUrl').value = thumbnailUrl;
        } else {
            document.getElementById('currentThumbnail').src = '/path/to/placeholder.jpg';
            document.getElementById('thumbnailUrl').value = '';
        }
    }

    // Fungsi untuk menampilkan preview thumbnail kustom
    function previewCustomThumbnail(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                // Simpan thumbnail kustom sebagai URL data
                document.getElementById('customThumbnailPreview').src = e.target.result;
                document.getElementById('thumbnailUrl').value = e.target.result;
                document.getElementById('isCustomThumbnail').value = '1';

                // Tampilkan container preview
                document.getElementById('customThumbnailPreviewContainer').classList.remove('hidden');
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    // Fungsi untuk mereset ke thumbnail YouTube
    function resetThumbnail() {
        document.getElementById('isCustomThumbnail').value = '0';
        document.getElementById('customThumbnailPreviewContainer').classList.add('hidden');
        document.getElementById('customThumbnail').value = '';

        // Kembalikan ke thumbnail YouTube
        updateThumbnail();
    }

    // Fungsi untuk render daftar dokumen di modal
    function renderDocumentListInModal() {
        console.log('Merender daftar dokumen di modal...');
        const container = document.getElementById('daftarDokumenContent');
        if (!container) {
            console.error('Container daftarDokumenContent tidak ditemukan!');
            return;
        }

        console.log('Jumlah dokumen:', documentData.length);
        container.innerHTML = '';

        documentData.forEach(doc => {
            const documentItem = document.createElement('div');
            documentItem.className = 'document-item bg-white p-5 rounded-lg border border-gray-200 hover:shadow-lg transition-all';
            documentItem.innerHTML = `
                <div class="flex flex-col md:flex-row gap-5">
                    <div class="w-full md:w-1/4">
                        <div class="bg-gray-100 p-6 rounded-md flex items-center justify-center">
                            <i class="fas fa-file-pdf text-red-500 text-4xl"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-lg mb-2">${doc.title}</h4>
                        <p class="text-gray-600 mb-4 text-sm md:text-base">${doc.description}</p>
                        <div class="flex flex-wrap gap-3">
                            <a href="${doc.file_url}" target="_blank" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-md text-sm flex items-center shadow-sm hover:shadow transition-all">
                                <i class="fas fa-download mr-2"></i> Unduh
                            </a>
                            <button type="button"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm flex items-center shadow-sm hover:shadow transition-all"
                                onclick="editDocument(${doc.id})">
                                <i class="fas fa-edit mr-2"></i> Edit
                            </button>
                            <button type="button"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm flex items-center shadow-sm hover:shadow transition-all"
                                onclick="deleteDocument(${doc.id})">
                                <i class="fas fa-trash mr-2"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(documentItem);
        });

        if (documentData.length === 0) {
            container.innerHTML = '<div class="text-center py-6 text-gray-500">Tidak ada dokumen panduan yang tersedia.</div>';
        }
    }

    // Fungsi untuk membuka pemutar video
    function openVideoPlayer(videoUrl, videoTitle) {
        const modal = document.getElementById('videoPlayerModal');
        const container = document.getElementById('videoPlayerContainer');
        const titleEl = document.getElementById('videoPlayerTitle');

        if (!modal || !container || !titleEl) {
            console.error('Elemen modal video player tidak ditemukan!');
            return;
        }

        // Extract YouTube ID
        const ytId = extractYoutubeId(videoUrl);

        if (!ytId) {
            console.error('Tidak dapat mengekstrak ID YouTube dari URL:', videoUrl);
            return;
        }

        // Set judul video
        titleEl.textContent = videoTitle;

        // Buat iframe untuk YouTube
        container.innerHTML = `
            <iframe
                src="https://www.youtube.com/embed/${ytId}?autoplay=1&rel=0"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                class="w-full h-full rounded-b-lg"
            ></iframe>
        `;

        // Tampilkan modal
        modal.style.display = 'flex';
    }
</script>
@endpush
