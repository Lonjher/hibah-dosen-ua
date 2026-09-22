<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $role_name
 * @property string $role_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method HasMany<User> users()
 */
#[Guarded(['id'])]
class Role extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
