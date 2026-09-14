<?php

namespace App\Filament\Resources\Cosmetics\Pages;

use App\Filament\Resources\Cosmetics\CosmeticResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCosmetics extends ListRecords
{
    protected static string $resource = CosmeticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
