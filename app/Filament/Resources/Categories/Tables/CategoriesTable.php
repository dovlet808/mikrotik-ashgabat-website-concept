<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('products'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.category.name'))
                    // `name` is a {ru, tk, en} array: formatting it would render once per locale ("A, A, A").
                    ->state(fn (Category $record) => $record->translate('name', 'ru'))
                    ->description(fn (Category $record) => $record->translate('name', 'en'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('name', 'like', '%'.$search.'%')),
                TextColumn::make('slug')->label(__('admin.common.slug'))->color('gray')->fontFamily('mono'),
                TextColumn::make('products_count')->label(__('admin.category.products_count'))->badge()->color('gray'),
                ToggleColumn::make('is_visible')->label(__('admin.common.visible')),
                TextColumn::make('sort_order')->label(__('admin.common.sort_order'))->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                CategoryResource::guardedDeleteAction(),
                CategoryResource::reassignAndDeleteAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('deleteEmpty')
                        ->label(__('filament-actions::delete.multiple.label'))
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $blocked = $records->reject(fn (Category $c) => $c->canBeDeleted());
                            $records->filter(fn (Category $c) => $c->canBeDeleted())->each->delete();
                            if ($blocked->isNotEmpty()) {
                                Notification::make()->warning()
                                    ->title(__('admin.category.delete_blocked_title'))
                                    ->body(__('admin.category.bulk_delete_skipped', ['count' => $blocked->count()]))
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
