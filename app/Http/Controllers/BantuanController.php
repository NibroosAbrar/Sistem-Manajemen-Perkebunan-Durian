<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BantuanController extends Controller
{
    /**
     * Update video tutorial (URL dan thumbnail)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateVideo(Request $request)
    {
        // Validasi input
        $request->validate([
            'video_id' => 'required',
            'url' => 'required|url',
            'title' => 'sometimes|string|max:255',
            'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Di implementasi nyata, Anda perlu menyimpan data ke database
            // Contoh: Video::where('id', $request->video_id)->update(['url' => $request->url, 'title' => $request->title]);

            // Untuk saat ini, kita hanya akan menangani thumbnail jika diunggah
            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $filename = 'thumbnail-' . $request->video_id . '.' . $thumbnail->getClientOriginalExtension();

                // Simpan thumbnail ke storage
                $thumbnail->storeAs('public/thumbnails', $filename);

                // Di implementasi nyata, update juga path thumbnail di database
                // Video::where('id', $request->video_id)->update(['thumbnail' => $filename]);
            }

            // Log aktivitas
            Log::info('Video tutorial diperbarui oleh ' . auth()->user()->name . ' (ID: ' . auth()->id() . ')');

            // Kembalikan respons sukses dengan data yang diperbarui
            return response()->json([
                'success' => true,
                'message' => 'Video berhasil diperbarui',
                'data' => [
                    'video_id' => $request->video_id,
                    'url' => $request->url,
                    'title' => $request->title ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            // Log error
            Log::error('Gagal memperbarui video: ' . $e->getMessage());

            // Kembalikan respons error
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui video',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
