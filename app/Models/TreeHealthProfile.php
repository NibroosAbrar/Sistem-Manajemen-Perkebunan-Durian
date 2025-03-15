<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeHealthProfile extends Model
{
    use HasFactory;

    protected $table = 'tree_health_profiles';

    protected $fillable = [
        'tree_id',
        'tanggal_pemeriksaan',
        'status_kesehatan',
        'gejala',
        'diagnosis',
        'tindakan_penanganan',
        'catatan_tambahan',
        'foto_kondisi'
    ];

    protected $casts = [
        'tanggal_pemeriksaan' => 'date',
    ];

    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
}
