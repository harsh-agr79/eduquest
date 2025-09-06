<?php

namespace App\Filament\Resources\FPSResource\Pages;

use App\Filament\Resources\FPSResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFPS extends EditRecord
{
    protected static string $resource = FPSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
