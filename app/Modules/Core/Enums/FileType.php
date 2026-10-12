<?php

namespace App\Modules\Core\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum FileType: string implements HasIcon, HasLabel
{
    case Pdf = 'application/pdf';
    case PowerPoint = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    case Word = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    case Excel = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::PowerPoint => 'PowerPoint',
            self::Word => 'Word',
            self::Excel => 'Excel',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pdf => Heroicon::OutlinedDocumentText,
            self::PowerPoint => Heroicon::OutlinedPresentationChartBar,
            self::Word => Heroicon::OutlinedDocumentText,
            self::Excel => Heroicon::OutlinedDocumentChartBar,
        };
    }
}
