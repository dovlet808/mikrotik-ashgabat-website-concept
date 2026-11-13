<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\Translatable;
use App\Models\Category;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.category.name'))
                ->description(__('admin.common.translations_help'))
                ->schema([
                    Translatable::inline('name', __('admin.category.name'), fn ($path) => TextInput::make($path)->maxLength(120), required: true),
                    Translatable::tabs([
                        'description' => [__('admin.category.description'), fn ($path) => Textarea::make($path)->rows(3)->maxLength(1000)],
                    ]),
                ])
                ->columnSpanFull(),
            Section::make()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        TextInput::make('slug')
                            ->label(__('admin.common.slug'))
                            ->helperText(__('admin.common.slug_help'))
                            ->maxLength(120)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->unique(Category::class, 'slug', ignoreRecord: true),
                        TextInput::make('sort_order')
                            ->label(__('admin.common.sort_order'))
                            ->helperText(__('admin.common.sort_order_help'))
                            ->numeric()->integer()->minValue(0)->default(0),
                        Toggle::make('is_visible')
                            ->label(__('admin.common.visible'))
                            ->default(true)
                            ->inline(false),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
