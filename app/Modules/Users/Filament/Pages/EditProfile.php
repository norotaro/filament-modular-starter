<?php

namespace App\Modules\Users\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),

                $this->getTimezoneFormComponent(),
            ]);
    }

    protected function getTimezoneFormComponent(): Component
    {
        return Select::make('timezone')
            ->label(__('Timezone'))
            ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
            ->searchable()
            ->native(false)
            ->required();
    }
}
