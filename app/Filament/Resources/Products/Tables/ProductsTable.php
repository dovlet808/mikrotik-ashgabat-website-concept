<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\Availability;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['primaryImage', 'category']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->striped()
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label(__('admin.product.thumbnail'))
                    ->state(fn (Product $record) => $record->primaryImage?->url(480))
                    ->imageSize(56)
                    ->square(),
                TextColumn::make('name')
                    ->label(__('admin.product.name'))
                    ->searchable(['name', 'product_code', 'brand'])
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Product $record) => $record->product_code),
                TextColumn::make('brand')
                    ->label(__('admin.product.brand'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('category_id')
                    ->label(__('admin.product.category'))
                    ->formatStateUsing(fn ($state, Product $record) => $record->category?->translate('name', 'ru'))
                    ->toggleable(),
                TextInputColumn::make('price')
                    ->label(__('admin.product.price').' (TMT)')
                    ->state(fn (Product $record) => $record->price === null ? null : (string) ((float) $record->price + 0))
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0', 'max:99999999'])
                    ->placeholder(__('admin.product.price_on_request'))
                    ->sortable()
                    ->width('9rem'),
                TextColumn::make('availability')
                    ->label(__('admin.product.availability'))
                    ->badge()
                    ->formatStateUsing(fn (Availability $state) => __('site.availability.'.$state->value, [], 'ru')),
                ToggleColumn::make('is_published')
                    ->label(__('admin.common.published')),
                ToggleColumn::make('is_featured')
                    ->label(__('admin.product.is_featured'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.common.updated'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('admin.product.category'))
                    ->options(fn () => Category::query()->orderBy('sort_order')->get()
                        ->mapWithKeys(fn (Category $c) => [$c->getKey() => $c->translate('name', 'ru')])),
                SelectFilter::make('availability')
                    ->label(__('admin.product.availability'))
                    ->options(collect(Availability::cases())->mapWithKeys(fn (Availability $a) => [$a->value => __('site.availability.'.$a->value, [], 'ru')])),
                SelectFilter::make('brand')
                    ->label(__('admin.product.brand'))
                    ->options(fn () => Product::query()->withTrashed()->distinct()->orderBy('brand')->pluck('brand', 'brand')->all()),
                TernaryFilter::make('is_published')->label(__('admin.common.published')),
                TernaryFilter::make('is_featured')->label(__('admin.product.is_featured')),
                TrashedFilter::make()->label(__('admin.product.trashed')),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ProductResource::viewOnSiteAction(),
                    ProductResource::duplicateAction(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label(__('admin.product.publish'))
                        ->icon(Heroicon::OutlinedEye)
                        ->action(function (Collection $records) {
                            $records->each(fn (Product $p) => $p->update(['is_published' => true]));
                            Notification::make()->success()->title(__('admin.product.published_done'))->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label(__('admin.product.unpublish'))
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(function (Collection $records) {
                            $records->each(fn (Product $p) => $p->update(['is_published' => false]));
                            Notification::make()->success()->title(__('admin.product.unpublished_done'))->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
