<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Menu
 * 
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool|null $is_active
 * 
 * @property Collection|MenuItem[] $menu_items
 *
 * @package App\Models
 */
class Menu extends Model
{
	protected $table = 'menus';
	public $timestamps = false;

	protected $casts = [
		'is_active' => 'bool'
	];

	protected $fillable = [
		'code',
		'name',
		'is_active'
	];

	public function menu_items()
	{
		return $this->hasMany(MenuItem::class);
	}
}
