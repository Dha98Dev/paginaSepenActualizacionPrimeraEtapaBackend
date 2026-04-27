<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Micrositio
 * 
 * @property int $id
 * @property string $nombre
 * @property string $slug
 * @property string $estructura
 * @property string|null $estatus
 * @property int|null $creado_por
 * @property int|null $actualizado_por
 * @property int|null $publicado_por
 * @property Carbon|null $publicado_en
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class Micrositio extends Model
{
	use SoftDeletes;
	protected $table = 'micrositios';

	protected $casts = [
		'estructura' => 'array',
		'creado_por' => 'int',
		'actualizado_por' => 'int',
		'publicado_por' => 'int',
		'publicado_en' => 'datetime'
	];

	protected $fillable = [
		'nombre',
		'slug',
		'estructura',
		'estatus',
		'creado_por',
		'actualizado_por',
		'publicado_por',
		'publicado_en'
	];
	public function archivos()
{
    return $this->belongsToMany(Archivo::class, 'archivos_micrositio', 'micrositio_id', 'archivo_id')
        ->withPivot('id');
}
}
