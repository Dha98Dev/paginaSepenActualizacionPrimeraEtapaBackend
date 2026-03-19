<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ConvocatoriaArchivo
 * 
 * @property int $id
 * @property int $convocatoria_id
 * @property int $archivo_id
 * @property string|null $tipo_relacion
 * @property int|null $orden
 * @property bool $principal
 * @property Carbon $created_at
 * 
 * @property Convocatoria $convocatoria
 * @property Archivo $archivo
 *
 * @package App\Models
 */
class ConvocatoriaArchivo extends Model
{
	protected $table = 'convocatoria_archivos';
	public $incrementing = true;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'convocatoria_id' => 'int',
		'archivo_id' => 'int',
		'orden' => 'int',
		'principal' => 'bool'
	];

	protected $fillable = [
		'convocatoria_id',
		'archivo_id',
		'tipo_relacion',
		'orden',
		'principal'
	];

	public function convocatoria()
	{
		return $this->belongsTo(Convocatoria::class);
	}

	public function archivo()
	{
		return $this->belongsTo(Archivo::class);
	}
}
