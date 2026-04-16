<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UsuarioSesione
 * 
 * @property int $id
 * @property int $usuario_id
 * @property string|null $token
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $ultima_actividad_at
 * @property Carbon|null $expira_at
 * @property Carbon|null $cerrada_at
 * @property bool $es_actual
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Usuario $usuario
 *
 * @package App\Models
 */
class UsuarioSesione extends Model
{
	protected $table = 'usuario_sesiones';
	public $incrementing = true;
protected $keyType = 'int';

	protected $casts = [
		'id' => 'int',
		'usuario_id' => 'int',
		'ultima_actividad_at' => 'datetime',
		'expira_at' => 'datetime',
		'cerrada_at' => 'datetime',
		'es_actual' => 'bool'
	];

	protected $hidden = [
		'token'
	];

	protected $fillable = [
		'usuario_id',
		'token',
		'ip_address',
		'user_agent',
		'ultima_actividad_at',
		'expira_at',
		'cerrada_at',
		'es_actual'
	];

	public function usuario()
	{
		return $this->belongsTo(Usuario::class);
	}
}
