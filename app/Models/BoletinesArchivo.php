<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BoletinesArchivo
 * 
 * @property int $id
 * @property int|null $archivo_id
 * @property int $boletin_id
 * @property string|null $enlace_externo
 * 
 * @property Archivo|null $archivo
 * @property Boletines $boletine
 *
 * @package App\Models
 */
class BoletinesArchivo extends Model
{
	protected $table = 'boletines_archivos';
	public $incrementing = true;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'archivo_id' => 'int',
		'boletin_id' => 'int'
	];

	protected $fillable = [
		'archivo_id',
		'boletin_id',
		'enlace_externo'
	];

public function archivo()
{
    return $this->belongsTo(Archivo::class, 'archivo_id');
}
	public function boletine()
	{
		return $this->belongsTo(Boletines::class, 'boletin_id');
	}
}
