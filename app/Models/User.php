<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
 * @property string $email_verification_code
 * @property Carbon|null $email_verification_code_expires_at
 * @property string $password
 * @property int $role_id
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
 * @method HasMany<ProgressReport> reviewedProgressReports()
 * @method HasMany<ReviewerNote> reviewerNotes()
 * @method HasMany<AdminNote> adminNotes()
 */
#[Guarded(['id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_verification_code_expires_at' => 'datetime',
        ];
    }

    public function initials(): string
    {
        $initials = Str::initials($this->full_name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1) . Str::substr($initials, -1)
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

    public function reviewedProgressReports(): HasMany
    {
        return $this->hasMany(ProgressReport::class, 'reviewer_id');
    }

    public function reviewerNotes(): HasMany
    {
        return $this->hasMany(ReviewerNote::class, 'reviewer_id');
    }

    public function adminNotes(): HasMany
    {
        return $this->hasMany(AdminNote::class, 'admin_id');
    }

    /**
     * Generate 6-digit code, simpan (hashed) di DB, return plain code.
     */
    public function generateEmailVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        return $code;
    }

    /**
     * Verifikasi kode. Return true kalau valid dan belum kedaluwarsa.
     */
    public function verifyEmailWithCode(string $code): bool
    {
        if (empty($this->email_verification_code) || ! $this->email_verification_code_expires_at) {
            return false;
        }

        if ($this->email_verification_code_expires_at->isPast()) {
            return false;
        }

        if (! Hash::check($code, $this->email_verification_code)) {
            return false;
        }

        $this->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        return true;
    }
}
