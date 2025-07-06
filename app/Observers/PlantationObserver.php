<?php

namespace App\Observers;

use App\Models\Plantation;
use Illuminate\Support\Facades\Log;

class PlantationObserver
{
    /**
     * Handle the Plantation "deleted" event.
     */
    public function deleted(Plantation $plantation): void
    {
        try {
            // Cari shapefile yang terkait dengan plantation ini
            $shapefiles = $plantation->shapefiles;
            
            if ($shapefiles->count() > 0) {
                foreach ($shapefiles as $shapefile) {
                    // Reset geometry menjadi null agar dianggap belum diproses
                    $shapefile->geometry = null;
                    // plantation_id sudah otomatis null karena nullOnDelete constraint
                    $shapefile->save();
                    
                    Log::info("Reset shapefile #{$shapefile->id} setelah plantation #{$plantation->id} dihapus");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error saat reset shapefile setelah plantation dihapus: " . $e->getMessage());
        }
    }
} 