<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Boletine
 * 
 * @property int $id
 * @property string $titulo
 * @property Carbon $fecha_evento
 * @property string|null $resumen
 * @property string $nota
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int|null $id_anterior
 * 
 * @property Collection|Archivo[] $archivos
 *
 * @package App\Models
 */
class Boletines extends Model
{
	protected $table = 'boletines';
	public $incrementing = true;

	protected $casts = [
		'id' => 'int',
		'fecha_evento' => 'datetime',
		'id_anterior' => 'int'
	];

	protected $fillable = [
		'titulo',
		'fecha_evento',
		'resumen',
		'nota',
		'id_anterior'
	];

public function archivos()
{
    return $this->hasMany(BoletinesArchivo::class, 'boletin_id');
}
}
