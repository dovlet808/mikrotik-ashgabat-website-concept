<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.groups.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('admin.product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.product.plural');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'product_code', 'brand'];
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function viewOnSiteAction(): Action
    {
        return Action::make('viewOnSite')
            ->label(__('admin.common.view_on_site'))
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (Product $record) => localized_route('products.show', $record->slug, config('site.default_locale')))
            ->openUrlInNewTab()
            ->visible(fn (Product $record) => $record->is_published && ! $record->trashed());
    }

    /**
     * Copies the product and its specifications as an unpublished draft (photos are not copied).
     */
    public static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label(__('admin.product.duplicate'))
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (Product $record, Action $action) {
                $copy = DB::transaction(function () use ($record) {
                    $copy = $record->replicate(['slug', 'search_text']);
                    $copy->name = $record->name.' '.__('admin.product.duplicate_suffix');
                    $copy->slug = Product::uniqueSlug($record->name.' copy');
                    $copy->is_published = false;
                    $copy->is_featured = false;
                    $copy->save();

                    foreach ($record->specifications as $spec) {
                        $copy->specifications()->create($spec->only(['group', 'label', 'value', 'sort_order']));
                    }

                    return $copy;
                });

                Notification::make()->success()->title(__('admin.product.duplicated'))->send();
                $action->redirect(static::getUrl('edit', ['record' => $copy]));
            })
            ->visible(fn (Product $record) => ! $record->trashed());
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            __('admin.product.product_code') => $record->product_code,
            __('admin.product.price') => $record->formattedPrice('ru'),
        ]);
    }
}
