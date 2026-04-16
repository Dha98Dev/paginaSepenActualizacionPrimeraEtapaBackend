<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Documento
 * 
 * @property int $id
 * @property string $titulo
 * @property string|null $descripcion
 * @property int $tipo_documento_id
 * @property int|null $grupo_id
 * @property int $archivo_id
 * @property int|null $documento_padre_id
 * @property int|null $anio
 * @property int|null $consecutivo
 * @property int|null $orden
 * @property Carbon|null $fecha_publicacion
 * @property bool $publicado
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property TiposDocumento $tipos_documento
 * @property GruposEscalafon|null $grupos_escalafon
 * @property Archivo $archivo
 * @property Documento|null $documento
 * @property Collection|Documento[] $documentos
 *
 * @package App\Models
 */
class Documento extends Model
{
	protected $table = 'documentos';
	public $incrementing = true;
protected $keyType = 'int';

	protected $casts = [
		'id' => 'int',
		'tipo_documento_id' => 'int',
		'grupo_id' => 'int',
		'archivo_id' => 'int',
		'documento_padre_id' => 'int',
		'anio' => 'int',
		'consecutivo' => 'int',
		'orden' => 'int',
		'fecha_publicacion' => 'datetime',
		'publicado' => 'bool'
	];

	protected $fillable = [
		'titulo',
		'descripcion',
		'tipo_documento_id',
		'grupo_id',
		'archivo_id',
		'documento_padre_id',
		'anio',
		'consecutivo',
		'orden',
		'fecha_publicacion',
		'publicado'
	];

	public function tipos_documento()
	{
		return $this->belongsTo(TiposDocumento::class, 'tipo_documento_id');
	}

	public function grupos_escalafon()
	{
		return $this->belongsTo(GruposEscalafon::class, 'grupo_id');
	}

	public function archivo()
	{
		return $this->belongsTo(Archivo::class);
	}

	public function documentoPadre()
	{
		return $this->belongsTo(Documento::class, 'documento_padre_id');
	}

	public function resultados()
	{
		return $this->hasMany(Documento::class, 'documento_padre_id');
	}
}
