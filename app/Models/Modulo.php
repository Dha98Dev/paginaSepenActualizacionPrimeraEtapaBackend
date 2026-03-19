<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Modulo
 * 
 * @property int $id
 * @property string $descripcion
 * 
 * @property Collection|TiposDocumento[] $tipos_documentos
 *
 * @package App\Models
 */
class Modulo extends Model
{
	protected $table = 'modulos';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int'
	];

	protected $fillable = [
		'descripcion'
	];

	public function tipos_documentos()
	{
		return $this->hasMany(TiposDocumento::class);
	}
}
