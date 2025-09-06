<?php

namespace App\Filament\Resources\FPSResource\Pages;

use App\Filament\Resources\FPSResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFPS extends ListRecords
{
    protected static string $resource = FPSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
