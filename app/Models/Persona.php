<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Persona
 * 
 * @property int $id
 * @property string $nombre
 * @property string $apellido_paterno
 * @property string|null $apellido_materno
 * @property string|null $curp
 * @property string|null $rfc
 * @property string|null $correo
 * @property string|null $telefono
 * @property string|null $puesto
 * @property string|null $area
 * @property string $estatus
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|Usuario[] $usuarios
 * @property Collection|AuditoriaAccione[] $auditoria_acciones
 *
 * @package App\Models
 */
class Persona extends Model
{
	use SoftDeletes;
	protected $table = 'personas';
	public $incrementing = true;
protected $keyType = 'int';

	protected $casts = [
		'id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'apellido_paterno',
		'apellido_materno',
		'curp',
		'rfc',
		'correo',
		'telefono',
		'puesto',
		'area',
		'estatus'
	];

	public function usuarios()
	{
		return $this->hasMany(Usuario::class);
	}

	public function auditoria_acciones()
	{
		return $this->hasMany(AuditoriaAccione::class);
	}
}
