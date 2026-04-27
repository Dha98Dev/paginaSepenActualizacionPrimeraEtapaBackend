<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ArchivosMicrositio
 * 
 * @property int $id
 * @property int $archivo_id
 * @property int $micrositio_id
 * 
 * @property Archivo $archivo
 * @property Micrositio $micrositio
 *
 * @package App\Models
 */
class ArchivosMicrositio extends Model
{
	protected $table = 'archivos_micrositio';
	public $incrementing = true;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'archivo_id' => 'int',
		'micrositio_id' => 'int'
	];

	protected $fillable = [
		'archivo_id',
		'micrositio_id'
	];

	public function archivo()
	{
		return $this->belongsTo(Archivo::class);
	}

	public function micrositio()
	{
		return $this->belongsTo(Micrositio::class);
	}
}
