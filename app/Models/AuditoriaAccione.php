<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AuditoriaAccione
 * 
 * @property int $id
 * @property int $usuario_id
 * @property int $persona_id
 * @property string $accion
 * @property string $modulo
 * @property string $tabla_afectada
 * @property int|null $registro_id
 * @property string|null $descripcion
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $payload_antes
 * @property string|null $payload_despues
 * @property Carbon $created_at
 * 
 * @property Usuario $usuario
 * @property Persona $persona
 *
 * @package App\Models
 */
class AuditoriaAccione extends Model
{
	protected $table = 'auditoria_acciones';
	public $incrementing = true;
protected $keyType = 'int';
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'usuario_id' => 'int',
		'persona_id' => 'int',
		'registro_id' => 'int',
		'payload_antes' => 'binary',
		'payload_despues' => 'binary'
	];

	protected $fillable = [
		'usuario_id',
		'persona_id',
		'accion',
		'modulo',
		'tabla_afectada',
		'registro_id',
		'descripcion',
		'ip_address',
		'user_agent',
		'payload_antes',
		'payload_despues'
	];

	public function usuario()
	{
		return $this->belongsTo(Usuario::class);
	}

	public function persona()
	{
		return $this->belongsTo(Persona::class);
	}
}
