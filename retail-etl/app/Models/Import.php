<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'status',
        'total_records',      // Cambiado de 'total_processed'
        'successful_records', // Nuevo campo para diferenciar
        'error_count',        // Cambiado de 'total_errors'
        'error_message',      // Para guardar el error del job
        'completed_at'        // Para saber cuándo terminó
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    // Relación: Una importación tiene muchas ventas
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    // Relación: Una importación tiene muchos errores
    public function errors()
    {
        return $this->hasMany(ImportError::class);
    }
}