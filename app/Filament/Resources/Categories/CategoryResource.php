<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.groups.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('admin.category.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.category.plural');
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->translate('name', 'ru');
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    /**
     * Delete that refuses when the category still has products.
     */
    public static function guardedDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->before(function (DeleteAction $action, Category $record) {
                if (! $record->canBeDeleted()) {
                    Notification::make()
                        ->danger()
                        ->title(__('admin.category.delete_blocked_title'))
                        ->body(__('admin.category.delete_blocked_body', ['count' => $record->products()->withTrashed()->count()]))
                        ->persistent()
                        ->send();
                    $action->cancel();
                }
            });
    }

    /**
     * Moves every product (including soft-deleted ones) to another category, then deletes.
     */
    public static function reassignAndDeleteAction(): Action
    {
        return Action::make('reassignAndDelete')
            ->label(__('admin.category.reassign'))
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('danger')
            ->visible(fn (Category $record) => ! $record->canBeDeleted())
            ->schema([
                Select::make('target_category_id')
                    ->label(__('admin.category.reassign_target'))
                    ->options(fn (Category $record) => Category::query()->whereKeyNot($record->getKey())->orderBy('sort_order')->get()
                        ->mapWithKeys(fn (Category $c) => [$c->getKey() => $c->translate('name', 'ru')]))
                    ->required(),
            ])
            ->requiresConfirmation()
            ->action(function (Category $record, array $data) {
                DB::transaction(function () use ($record, $data) {
                    $record->products()->withTrashed()->update(['category_id' => $data['target_category_id']]);
                    $record->delete();
                });
                Notification::make()->success()->title(__('admin.category.reassign_done'))->send();
            })
            ->successRedirectUrl(fn () => static::getUrl('index'));
    }
}
