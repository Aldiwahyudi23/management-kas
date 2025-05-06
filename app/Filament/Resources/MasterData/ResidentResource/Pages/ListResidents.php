<?php

namespace App\Filament\Resources\MasterData\ResidentResource\Pages;

use App\Filament\Resources\MasterData\ResidentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResidents extends ListRecords
{
    protected static string $resource = ResidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
