<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Convocatoria
 * 
 * @property int $id
 * @property string $lugar
 * @property string|null $convocatoria
 * @property string|null $descripcion
 * @property Carbon|null $fecha_evento
 * @property Carbon $fecha_publicacion
 * @property Carbon $fecha_vencimiento
 * @property bool $publicar
 * @property bool $vigente
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $enlace_externo
 * 
 * @property Collection|Archivo[] $archivos
 *
 * @package App\Models
 */
class Convocatoria extends Model
{
	protected $table = 'convocatorias';
	public $incrementing = true;

	protected $casts = [
		'id' => 'int',
		'fecha_evento' => 'datetime',
		'fecha_publicacion' => 'datetime',
		'fecha_vencimiento' => 'datetime',
		'publicar' => 'bool',
		'vigente' => 'bool'
	];

	protected $fillable = [
		'lugar',
		'convocatoria',
		'descripcion',
		'fecha_evento',
		'fecha_publicacion',
		'fecha_vencimiento',
		'publicar',
		'vigente',
		'enlace_externo'
	];

	public function archivos()
	{
		return $this->belongsToMany(Archivo::class, 'convocatoria_archivos')
					->withPivot('id', 'tipo_relacion', 'orden', 'principal');
	}
}
