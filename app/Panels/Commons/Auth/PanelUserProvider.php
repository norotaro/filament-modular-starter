<?php

namespace App\Panels\Commons\Auth;

use App\Modules\Users\Enums\UserType;
use App\Panels\Commons\Enums\PanelID;
use Filament\Facades\Filament;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Restricts every user lookup to the role of the current Filament panel.
 *
 * The panel is read on each query, never when the provider is built: under
 * Octane one provider can outlive the request that created it.
 */
class PanelUserProvider extends EloquentUserProvider
{
    /**
     * A sign-in attempt outside a known panel finds nobody, so a panel missing
     * from PanelID cannot become a login that admits every role. Lookups by id
     * stay open: they restore users who already signed in, on any route.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        if ($this->currentPanel() === null) {
            return null;
        }

        return parent::retrieveByCredentials($credentials);
    }

    /**
     * @param  Model|null  $model
     * @return Builder<Model>
     */
    protected function newModelQuery($model = null)
    {
        $query = parent::newModelQuery($model);

        return match ($this->currentPanel()) {
            PanelID::SuperAdmin => $query->where('type', UserType::SuperAdmin),
            PanelID::Admin => $query->where('type', UserType::Admin),
            null => $query,
        };
    }

    private function currentPanel(): ?PanelID
    {
        return PanelID::tryFrom((string) Filament::getCurrentPanel()?->getId());
    }
}
