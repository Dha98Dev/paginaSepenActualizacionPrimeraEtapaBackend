<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Storage;

/**
 * Class Archivo
 * 
 * @property int $id
 * @property string $nombre_original
 * @property string $nombre_guardado
 * @property string $ruta
 * @property string|null $url_publica
 * @property string|null $tipo_mime
 * @property string|null $extension
 * @property int|null $tamano_bytes
 * @property string|null $hash_archivo
 * @property string|null $descripcion
 * @property bool|null $es_publico
 * @property string|null $estado
 * @property int|null $creado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Convocatoria[] $convocatorias
 *
 * @package App\Models
 */
class Archivo extends Model
{
	protected $table = 'archivos';
	public $incrementing = true;
	protected $casts = [
		'tamano_bytes' => 'int',
		'es_publico' => 'bool',
		'creado_por' => 'int'
	];
	protected $appends = ['url_publica'];
	public function getUrlPublicaAttribute(): ?string
    {
        if (!$this->ruta) {
            return null;
        }

        return Storage::disk('public')->url($this->ruta);
    }
	protected $fillable = [
		'nombre_original',
		'nombre_guardado',
		'ruta',
		'url_publica',
		'tipo_mime',
		'extension',
		'tamano_bytes',
		'hash_archivo',
		'descripcion',
		'es_publico',
		'estado',
		'creado_por'
	];

	public function convocatorias()
	{
		return $this->belongsToMany(Convocatoria::class, 'convocatoria_archivos')
			->withPivot('id', 'tipo_relacion', 'orden', 'principal');
	}
}
