<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Support\PlatformSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One switch on the platform itself, stored as a row so it can be changed
 * without a deploy.
 *
 * Deliberately not organization scoped: these settle how the platform behaves
 * before anyone has an organization at all — whether an account can be created
 * in the first place, for one — so there is no tenant to scope them to.
 *
 * Read through {@see PlatformSettings} rather than
 * queried directly; that is where the defaults and the cache live.
 *
 * @property string $key
 * @property mixed $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value'])]
class PlatformSetting extends Model
{
    /**
     * The primary key is the setting's name, and names are not sequential.
     */
    protected $primaryKey = 'key';

    /**
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
