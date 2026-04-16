<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
/**
 * Class Usuario
 * 
 * @property int $id
 * @property int $persona_id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property bool $activo
 * @property bool $debe_cambiar_password
 * @property int $intentos_fallidos
 * @property Carbon|null $bloqueado_hasta
 * @property Carbon|null $ultimo_acceso_at
 * @property Carbon|null $ultimo_cambio_password_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Persona $persona
 * @property Collection|UsuarioRol[] $usuario_rols
 * @property Collection|UsuarioSesione[] $usuario_sesiones
 * @property Collection|AuditoriaAccione[] $auditoria_acciones
 *
 * @package App\Models
 */

class Usuario extends Authenticatable
{
	use HasApiTokens, Notifiable, SoftDeletes;
	protected $table = 'usuarios';
	public $incrementing = true;
	protected $keyType = 'int';

	protected $casts = [
		'id' => 'int',
		'persona_id' => 'int',
		'activo' => 'bool',
		'debe_cambiar_password' => 'bool',
		'intentos_fallidos' => 'int',
		'bloqueado_hasta' => 'datetime',
		'ultimo_acceso_at' => 'datetime',
		'ultimo_cambio_password_at' => 'datetime'
	];

	protected $hidden = [
		'password',
		'debe_cambiar_password',
		'remember_token'
	];

	protected $fillable = [
		'persona_id',
		'username',
		'email',
		'password',
		'activo',
		'debe_cambiar_password',
		'intentos_fallidos',
		'bloqueado_hasta',
		'ultimo_acceso_at',
		'ultimo_cambio_password_at',
		'remember_token'
	];

	public function persona()
	{
		return $this->belongsTo(Persona::class);
	}

	public function usuario_rols()
	{
		return $this->hasMany(UsuarioRol::class);
	}
	public function roles()
	{
		return $this->belongsToMany(
			Role::class,
			'usuario_rol',
			'usuario_id',
			'rol_id'
		)->withTimestamps();
	}

	public function usuario_sesiones()
	{
		return $this->hasMany(UsuarioSesione::class);
	}

	public function auditoria_acciones()
	{
		return $this->hasMany(AuditoriaAccione::class);
	}
	public function usuario_permisos()
	{
		return $this->hasMany(UsuarioPermiso::class, 'usuario_id');
	}
}
