<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 * 
 * @property int $id
 * @property string $nombre
 * @property string $slug
 * @property string|null $descripcion
 * @property bool $activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|UsuarioRol[] $usuario_rols
 * @property Collection|RolPermiso[] $rol_permisos
 *
 * @package App\Models
 */
class Role extends Model
{
	protected $table = 'roles';
	public $incrementing = true;
	protected $keyType = 'int';

	protected $casts = [
		'id' => 'int',
		'activo' => 'bool'
	];

	protected $fillable = [
		'nombre',
		'slug',
		'descripcion',
		'activo'
	];

	public function usuario_rols()
	{
		return $this->hasMany(UsuarioRol::class, 'rol_id');
	}

	public function rol_permisos()
	{
		return $this->hasMany(RolPermiso::class, 'rol_id');
	}
	public function usuarios()
	{
		return $this->belongsToMany(
			Usuario::class,
			'usuario_rol',
			'rol_id',
			'usuario_id'
		)->withTimestamps();
	}
	public function permisos()
	{
		return $this->belongsToMany(
			Permiso::class,
			'rol_permiso',
			'rol_id',
			'permiso_id'
		)->withTimestamps();
	}
}
