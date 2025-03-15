<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AerialPhoto;
use Illuminate\Support\Facades\Log;

class AerialPhotoController extends Controller
{
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'aerial_photo' => 'nullable|image|max:10240', // Make it nullable
                'resolution' => 'required|numeric|min:0',
                'capture_time' => 'required|date',
                'drone_type' => 'required|string',
                'height' => 'required|numeric|min:0',
                'overlap' => 'required|numeric|min:0|max:100',
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
                'resolution' => $validated['resolution'],
                'capture_time' => $validated['capture_time'],
                'drone_type' => $validated['drone_type'],
                'height' => $validated['height'],
                'overlap' => $validated['overlap'],
            ]);

            return redirect()->route('webgis')->with('success', 'Foto udara berhasil diperbarui');

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

    public function store(Request $request)
    {
        $request->validate([
            'aerial_photo' => 'nullable|image|max:10240',
            'resolution' => 'required|numeric|min:0',
            'capture_time' => 'required|date',
            'drone_type' => 'required|string',
            'height' => 'required|numeric|min:0',
            'overlap' => 'required|numeric|min:0|max:100',
        ]);

        // Get the next available ID
        $nextId = $this->getNextId();

        // Store the image
        $path = null;
        if ($request->hasFile('aerial_photo')) {
            $path = $request->file('aerial_photo')->store('aerial_photos', 'public');
        }

        // Create new aerial photo record with the custom ID
        $photo = AerialPhoto::create([
            'id' => $nextId,
            'path' => $path,
            'resolution' => $request->input('resolution'),
            'capture_time' => $request->input('capture_time'),
            'drone_type' => $request->input('drone_type'),
            'height' => $request->input('height'),
            'overlap' => $request->input('overlap'),
        ]);

        return redirect()->route('webgis')->with('success', 'Foto udara berhasil ditambahkan');
    }

    public function edit()
    {
        $photo = AerialPhoto::latest()->first();
        return view('pages.aerial-photo.edit', compact('photo'));
        return redirect()->route('webgis');
    }

    public function create()
    {
        return view('pages.aerial-photo.create'); // Ensure this view exists
    }

    private function getNextId()
    {
        // Get the maximum ID from the table
        $maxId = AerialPhoto::max('id');

        // If there are no records, start from 1
        return $maxId ? $maxId + 1 : 1;
    }
}
