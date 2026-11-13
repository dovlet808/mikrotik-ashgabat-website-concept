<?php

namespace App\Filament\Resources\Services\Tables;

use App\Models\Service;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                IconColumn::make('icon')
                    ->label(__('admin.service.icon'))
                    ->icon(fn (string $state) => 'heroicon-o-'.$state)
                    ->color('primary'),
                TextColumn::make('title')
                    ->label(__('admin.service.title'))
                    // `title` is a {ru, tk, en} array: formatting it would render once per locale ("A, A, A").
                    ->state(fn (Service $record) => $record->translate('title', 'ru'))
                    ->description(fn (Service $record) => $record->translate('summary', 'ru'))
                    ->wrap(),
                IconColumn::make('is_confirmed')
                    ->label(__('admin.service.is_confirmed'))
                    ->boolean(),
                ToggleColumn::make('is_published')->label(__('admin.common.published')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
