<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MenuItem
 * 
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $label
 * @property string|null $icon
 * @property string|null $badge
 * @property USER-DEFINED|null $link_type
 * @property string|null $router_link
 * @property string|null $external_url
 * @property string|null $target
 * @property int|null $sort_order
 * @property bool|null $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Menu $menu
 * @property MenuItem|null $menu_item
 * @property Collection|MenuItem[] $menu_items
 *
 * @package App\Models
 */
class MenuItem extends Model
{
	protected $table = 'menu_items';

	protected $casts = [
		'menu_id' => 'int',
		'parent_id' => 'int',
		'link_type' => 'string',
		'sort_order' => 'int',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'menu_id',
		'parent_id',
		'label',
		'icon',
		'badge',
		'link_type',
		'router_link',
		'external_url',
		'target',
		'sort_order',
		'is_active'
	];

	public function menu()
	{
		return $this->belongsTo(Menu::class);
	}

	public function menu_item()
	{
		return $this->belongsTo(MenuItem::class, 'parent_id');
	}

	public function menu_items()
	{
		return $this->hasMany(MenuItem::class, 'parent_id');
	}
}
