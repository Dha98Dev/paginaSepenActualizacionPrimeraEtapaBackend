<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UsuarioRol
 * 
 * @property int $id
 * @property int $usuario_id
 * @property int $rol_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Usuario $usuario
 * @property Role $role
 *
 * @package App\Models
 */
class UsuarioRol extends Model
{
	protected $table = 'usuario_rol';
	public $incrementing = true;
protected $keyType = 'int';

	protected $casts = [
		'id' => 'int',
		'usuario_id' => 'int',
		'rol_id' => 'int'
	];

	protected $fillable = [
		'usuario_id',
		'rol_id'
	];

	public function usuario()
	{
		return $this->belongsTo(Usuario::class);
	}

	public function role()
	{
		return $this->belongsTo(Role::class, 'rol_id');
	}
}
