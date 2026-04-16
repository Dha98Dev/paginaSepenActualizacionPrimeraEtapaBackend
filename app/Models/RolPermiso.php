<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RolPermiso
 * 
 * @property int $id
 * @property int $rol_id
 * @property int $permiso_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Role $role
 * @property Permiso $permiso
 *
 * @package App\Models
 */
class RolPermiso extends Model
{
	protected $table = 'rol_permiso';
	public $incrementing = true;
protected $keyType = 'int';

	protected $casts = [
		'id' => 'int',
		'rol_id' => 'int',
		'permiso_id' => 'int'
	];

	protected $fillable = [
		'rol_id',
		'permiso_id'
	];

	public function role()
	{
		return $this->belongsTo(Role::class, 'rol_id');
	}

	public function permiso()
	{
		return $this->belongsTo(Permiso::class);
	}
}
