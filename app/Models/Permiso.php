<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Permiso
 * 
 * @property int $id
 * @property string $nombre
 * @property string $slug
 * @property string $modulo
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|RolPermiso[] $rol_permisos
 *
 * @package App\Models
 */
class Permiso extends Model
{
	protected $table = 'permisos';
	public $incrementing = true;
protected $keyType = 'int';

	protected $casts = [
		'id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'slug',
		'modulo',
		'descripcion'
	];

	public function rol_permisos()
	{
		return $this->hasMany(RolPermiso::class);
	}
}
