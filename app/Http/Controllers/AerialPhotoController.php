<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\AerialPhoto;
use Illuminate\Support\Facades\Log;

class AerialPhotoController extends Controller
{
    public function index()
    {
        $photos = AerialPhoto::orderBy('created_at', 'desc')->get();
        return view('pages.aerial-photo.aerial', compact('photos'));
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'aerial_photo' => 'nullable|image|max:51200', // Make it nullable
                'bounds_topleft' => 'required|string',
                'bounds_bottomright' => 'required|string',
                'resolution' => 'required|numeric|min:0',
                'capture_time' => 'required|date',
                'drone_type' => 'required|string',
                'height' => 'required|numeric|min:0',
                'overlap' => 'required|numeric|min:0|max:100',
            ]);

            // Process bounds coordinates
            $topLeft = array_map('trim', explode(',', $request->bounds_topleft));
            $bottomRight = array_map('trim', explode(',', $request->bounds_bottomright));
            $bounds = json_encode([
                [$topLeft[0] ?? 0, $topLeft[1] ?? 0],
                [$bottomRight[0] ?? 0, $bottomRight[1] ?? 0]
            ]);

            // Store the image only if a new file is uploaded
            $path = null;
            if ($request->hasFile('aerial_photo')) {
                $path = $request->file('aerial_photo')->store('aerial_photos', 'public');
            }

            // Update the aerial photo record
            $photo = AerialPhoto::findOrFail($request->id); // Ensure you get the correct photo
            $photo->update([
                'path' => $path ?? $photo->path, // Keep the old path if no new file is uploaded
                'bounds' => $bounds,
                'resolution' => $validated['resolution'],
                'capture_time' => $validated['capture_time'],
                'drone_type' => $validated['drone_type'],
                'height' => $validated['height'],
                'overlap' => $validated['overlap'],
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('aerial-photo.index')->with('success', 'Foto udara berhasil diperbarui');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', array_map(function($errors) {
                    return implode(', ', $errors);
                }, $e->errors()))
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating aerial photo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload foto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLatest()
    {
        try {
            $photo = AerialPhoto::latest()->first();

            if (!$photo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum ada foto udara yang tersedia'
                ], 404);
            }

            // Add full URL for the image path
            $photo->path = asset('storage/' . $photo->path);

            return response()->json([
                'success' => true,
                'data' => $photo
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting latest aerial photo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data foto udara'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Memvalidasi request
        $request->validate([
            'aerial_photo' => 'required|file|mimes:jpeg,jpg,png,tif,tiff,geotiff|max:51200', // 50MB dalam kilobytes
            'bounds_topleft' => 'required|string',
            'bounds_bottomright' => 'required|string',
            'resolution' => 'required|numeric',
            'capture_time' => 'required|date',
            'drone_type' => 'required|string',
            'height' => 'required|numeric',
            'overlap' => 'required|numeric',
        ]);

        // Simpan file
        $path = null;
        if ($request->hasFile('aerial_photo')) {
            $file = $request->file('aerial_photo');

            // Generate nama file unik
            $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

            // Buat direktori untuk foto udara dengan ID yang akan dibuat
            // Kita akan mendapatkan ID terbaru + 1
            $lastId = AerialPhoto::max('id') ?? 0;
            $newId = $lastId + 1;

            // Simpan file ke dalam folder aerial-photos dengan struktur ID
            $directory = 'aerial-photos/' . $newId;
            $path = $file->storeAs($directory, 'preview.jpg', 'public');

            // Log informasi upload file
            \Illuminate\Support\Facades\Log::info('File aerial photo berhasil disimpan di: ' . $path);
            \Illuminate\Support\Facades\Log::info('Path lengkap: ' . storage_path('app/public/' . $path));
        }

        // Parsing bounds
        $topLeft = explode(',', $request->bounds_topleft);
        $bottomRight = explode(',', $request->bounds_bottomright);

        $bounds = [
            'top_left' => [
                'lat' => floatval(trim($topLeft[0])),
                'lng' => floatval(trim($topLeft[1])),
            ],
            'bottom_right' => [
                'lat' => floatval(trim($bottomRight[0])),
                'lng' => floatval(trim($bottomRight[1])),
            ],
        ];

        // Simpan ke database
        $aerialPhoto = AerialPhoto::create([
            'user_id' => auth()->id(),
            'path' => $path,
            'bounds' => json_encode($bounds),
            'resolution' => $request->resolution,
            'capture_time' => $request->capture_time,
            'drone_type' => $request->drone_type,
            'height' => $request->height,
            'overlap' => $request->overlap,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $aerialPhoto,
                'message' => 'Foto udara berhasil disimpan!'
            ]);
        }

        return redirect()->route('aerial-photo.index')
            ->with('success', 'Foto udara berhasil disimpan!');
    }

    public function edit($id)
    {
        $photo = AerialPhoto::findOrFail($id);
        return view('pages.aerial-photo.edit', compact('photo'));
    }

    public function create()
    {
        return view('pages.aerial-photo.create'); // Ensure this view exists
    }

    public function destroy($id)
    {
        try {
            $photo = AerialPhoto::findOrFail($id);

            // Delete file from storage if it exists
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }

            // Delete record from database
            $photo->delete();

            return redirect()->route('aerial-photo.index')->with('success', 'Foto udara berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting aerial photo: ' . $e->getMessage());
            return redirect()->route('aerial-photo.index')->with('error', 'Gagal menghapus foto udara');
        }
    }

    /**
     * Get all aerial photos for API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll()
    {
        try {
            $photos = AerialPhoto::orderBy('capture_time', 'desc')->get();

            $formattedPhotos = $photos->map(function($photo) {
                // Format tanggal untuk tampilan yang rapi
                $captureDate = $photo->capture_time
                    ? $photo->capture_time->format('d M Y')
                    : 'Tanggal tidak tersedia';

                return [
                    'id' => $photo->id,
                    'capture_time' => $photo->capture_time,
                    'display_date' => $captureDate,
                    'path' => $photo->path ? asset('storage/' . $photo->path) : null,
                    'resolution' => $photo->resolution,
                    'bounds' => $photo->bounds
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPhotos
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting aerial photos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getNextId()
    {
        // Reset auto-increment sequence pada tabel aerial_photos jika ada
        // Tidak perlu dilakukan karena kita sudah mengatur incrementing = false di model

        // Ambil semua ID yang sudah digunakan, urutkan secara ascending
        $usedIds = AerialPhoto::orderBy('id')->pluck('id')->toArray();

        // Jika tidak ada record, mulai dari 1
        if (empty($usedIds)) {
            return 1;
        }

        // Jika ada ID yang digunakan, cari ID terkecil yang belum digunakan (gap)
        $id = 1;
        foreach ($usedIds as $usedId) {
            if ($usedId > $id) {
                // Ditemukan gap, gunakan ID ini
                return $id;
            }
            $id = $usedId + 1;
        }

        // Jika tidak ada gap, gunakan ID selanjutnya
        return $id;
    }
}
