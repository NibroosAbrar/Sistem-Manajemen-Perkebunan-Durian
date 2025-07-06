<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plantation extends Model {
    use HasFactory;

    protected $fillable = [
        'shapefile_id',
        'name',
        'geometry',
        'latitude',
        'longitude',
        'luas_area',
        'tipe_tanah'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'luas_area' => 'decimal:4'
    ];

    /**
     * Accessor untuk memastikan geometri selalu dikembalikan dengan format yang benar
     * @param string|null $value
     * @return string|null
     */
    public function getGeometryAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Pastikan geometri memiliki SRID
        if (!preg_match('/^SRID=\d+;/i', $value)) {
            $value = 'SRID=4326;' . $value;
        }

        return $value;
    }

    /**
     * Mutator untuk memastikan geometri disimpan dengan format yang konsisten
     * @param string|null $value
     * @return void
     */
    public function setGeometryAttribute($value)
    {
        if (!$value) {
            $this->attributes['geometry'] = null;
            return;
        }

        // Pastikan geometri memiliki SRID
        if (!preg_match('/^SRID=\d+;/i', $value)) {
            $value = 'SRID=4326;' . $value;
        }

        // Normalisasi format (hapus spasi berlebih)
        $cleanValue = preg_replace('/\s+/', ' ', $value);
        $cleanValue = trim($cleanValue);

        $this->attributes['geometry'] = $cleanValue;
    }

    /**
     * Mutator untuk memastikan luas_area selalu disimpan dengan 4 desimal
     * @param float|null $value
     * @return void
     */
    public function setLuasAreaAttribute($value)
    {
        if ($value === null) {
            $this->attributes['luas_area'] = 0;
            return;
        }

        // Pastikan nilai numerik dengan 4 desimal
        $this->attributes['luas_area'] = round(floatval($value), 4);
    }

    /**
     * Accessor untuk mengembalikan geometry dalam format WKT standar
     * @param string|null $value
     * @return string|null
     */
    public function getGeometryTextAttribute($value)
    {
        // Jika ada nilai langsung dari database (hasil dari ST_AsText)
        if ($value) {
            // Pastikan memiliki SRID
            if (!preg_match('/^SRID=\d+;/i', $value)) {
                return 'SRID=4326;' . $value;
            }
            return $value;
        }

        // Jika geometry tidak ada, coba gunakan geometry biasa
        if (isset($this->attributes['geometry']) && $this->attributes['geometry']) {
            $geometry = $this->attributes['geometry'];

            // Konversi geometry ke WKT dengan PostGIS jika diperlukan
            if (!preg_match('/^SRID=|^POLYGON|^MULTIPOLYGON|^POINT/i', $geometry)) {
                try {
                    $result = DB::selectOne("SELECT ST_AsText(geometry) as wkt FROM plantations WHERE id = ?", [$this->id]);
                    if ($result && $result->wkt) {
                        return 'SRID=4326;' . $result->wkt;
                    }
                } catch (\Exception $e) {
                    // Fallback ke geometry biasa jika gagal konversi
                    return $this->getGeometryAttribute($geometry);
                }
            }

            // Jika sudah dalam format WKT, pastikan ada SRID
            return $this->getGeometryAttribute($geometry);
        }

        return null;
    }

    /**
     * Relasi ke shapefile
     */
    public function shapefile(): BelongsTo {
        return $this->belongsTo(Shapefile::class);
    }

    /**
     * Relasi ke owner melalui shapefile
     */
    public function user() {
        return $this->hasOneThrough(User::class, Shapefile::class, 'id', 'id', 'shapefile_id', 'user_id');
    }

    /**
     * Relasi ke trees
     */
    public function trees() {
        return $this->hasMany(Tree::class);
    }
}
