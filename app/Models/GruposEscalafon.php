<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class GruposEscalafon
 * 
 * @property int $id
 * @property string $descripcion
 * 
 * @property Collection|Documento[] $documentos
 *
 * @package App\Models
 */
class GruposEscalafon extends Model
{
	protected $table = 'grupos_escalafon';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int'
	];

	protected $fillable = [
		'descripcion'
	];

	public function documentos()
	{
		return $this->hasMany(Documento::class, 'grupo_id');
	}
}
