<?php

namespace App\Modules\Users\Models;

use App\Modules\Core\Concerns\HasModuleFactory;
use App\Modules\Users\Database\Factories\UserFactory;
use App\Modules\Users\Enums\UserType;
use App\Panels\Commons\Enums\PanelID;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Identity Model
 *
 * Base identity model representing the raw user record in the database.
 * Use this model when you need to avoid the automatic polymorphic casting
 * provided by the User model's HasChildren trait.
 *
 * @property-read int $id Primary key
 * @property string $name User's full name
 * @property string $email User's email address (unique)
 * @property string|null $timezone User's timezone
 * @property-read Carbon|null $email_verified_at Timestamp when email was verified
 * @property string $password Hashed password
 * @property-read UserType $type User type (SuperAdmin, Admin, Reviewer, Participant) — never mass assignable
 * @property-read string|null $remember_token Remember token for authentication
 * @property-read Carbon $created_at Timestamp when user was created
 * @property-read Carbon $updated_at Timestamp when user was last updated
 * @property-read Carbon|null $deleted_at Timestamp when user was soft deleted
 *
 * @method static UserFactory factory($count = null, $state = [])
 */
class Identity extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasModuleFactory<UserFactory> */
    use HasModuleFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * `type` is deliberately absent: the role column is written only by
     * trusted flows (seeders, factories, force-filled registration), never
     * by user-submitted payloads.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'timezone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => UserType::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = PanelID::tryFrom($panel->getId());

        return $panelId !== null && $panelId->isAccessibleBy($this->type);
    }

    /**
     * Consumed by stechstudio/filament-impersonate if that package is added;
     * kept as the documented rule: only Super Admins may impersonate, and a
     * Super Admin may never be impersonated.
     */
    public function canImpersonate(): bool
    {
        return auth()->user()?->type === UserType::SuperAdmin;
    }

    public function canBeImpersonated(): bool
    {
        return $this->type !== UserType::SuperAdmin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->type === UserType::SuperAdmin;
    }

    public function isParticipant(): bool
    {
        return $this->type === UserType::Participant;
    }
}
