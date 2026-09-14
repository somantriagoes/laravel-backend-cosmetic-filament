<?php

namespace App\Filament\Resources\Cosmetics\Pages;

use App\Filament\Resources\Cosmetics\CosmeticResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCosmetic extends EditRecord
{
    protected static string $resource = CosmeticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
