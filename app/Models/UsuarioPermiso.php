<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UsuarioPermiso
 * 
 * @property int $id
 * @property int $usuario_id
 * @property int $permiso_id
 * @property bool $permitido
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Usuario $usuario
 * @property Permiso $permiso
 *
 * @package App\Models
 */
class UsuarioPermiso extends Model
{
	protected $table = 'usuario_permiso';

	protected $casts = [
		'usuario_id' => 'int',
		'permiso_id' => 'int',
		'permitido' => 'bool'
	];

	protected $fillable = [
		'usuario_id',
		'permiso_id',
		'permitido'
	];

	public function usuario()
	{
		return $this->belongsTo(Usuario::class);
	}

	public function permiso()
	{
		return $this->belongsTo(Permiso::class);
	}
}
