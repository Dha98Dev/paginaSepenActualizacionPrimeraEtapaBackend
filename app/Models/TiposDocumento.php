<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TiposDocumento
 * 
 * @property int $id
 * @property string $tipo_documento
 * @property int $modulo_id
 * 
 * @property Modulo $modulo
 * @property Collection|Documento[] $documentos
 *
 * @package App\Models
 */
class TiposDocumento extends Model
{
	protected $table = 'tipos_documentos';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'modulo_id' => 'int'
	];

	protected $fillable = [
		'tipo_documento',
		'modulo_id'
	];

	public function modulo()
	{
		return $this->belongsTo(Modulo::class);
	}

	public function documentos()
	{
		return $this->hasMany(Documento::class, 'tipo_documento_id');
	}
}
