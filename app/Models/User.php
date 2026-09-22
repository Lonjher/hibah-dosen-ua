<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $nidn
 * @property string $full_name
 * @property Carbon $birthday
 * @property string $gender
 * @property string $address
 * @property string $phone_number
 * @property string $avatar
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Role $role_id
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<Role> role()
 * @method HasMany<Proposal> proposals()
 * @method HasMany<Proposal> reviewedProposals()
 * @method HasMany<ReviewerNotes> reviewerNotes()
 */
#[Guarded(['id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->full_name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'user_id');
    }

    public function reviewedProposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'reviewer_id');
    }

    public function reviewerNotes(): HasMany
    {
        return $this->hasMany(ReviewerNote::class, 'reviewer_id');
    }
}
