<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Personaje extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'personajes';

    protected $fillable = [
        'nombre',
        'estilo_alegre',
        'estilo_pensando',
        'estilo_confundido',
        'estilo_celebrando',
        'estilo_triste',
        'estilo_motivado',
        'estilo_cansado',
        'estilo_tierno',
    ];
}
