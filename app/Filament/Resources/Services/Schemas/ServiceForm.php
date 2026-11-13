<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Support\Translatable;
use App\Models\Service;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.service.title'))
                ->description(__('admin.common.translations_help'))
                ->schema([
                    Translatable::inline('title', __('admin.service.title'), fn ($path) => TextInput::make($path)->maxLength(120), required: true),
                    Translatable::tabs([
                        'summary' => [__('admin.service.summary'), fn ($path) => Textarea::make($path)->rows(2)->maxLength(400), true],
                        'body' => [__('admin.service.body'), fn ($path) => Textarea::make($path)->rows(6)->maxLength(4000)->helperText(__('admin.product.description_help'))],
                    ]),
                ])
                ->columnSpanFull(),
            Section::make()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Select::make('icon')
                            ->label(__('admin.service.icon'))
                            ->options(Service::ICONS)
                            ->default('wrench-screwdriver')
                            ->required(),
                        TextInput::make('slug')
                            ->label(__('admin.common.slug'))
                            ->helperText(__('admin.common.slug_help'))
                            ->maxLength(120)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->unique(Service::class, 'slug', ignoreRecord: true),
                        TextInput::make('sort_order')
                            ->label(__('admin.common.sort_order'))
                            ->numeric()->integer()->minValue(0)->default(0),
                    ]),
                    FileUpload::make('image_path')
                        ->label(__('admin.service.image'))
                        ->image()
                        ->disk('uploads')
                        ->directory('services')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize((int) config('site.images.max_upload_kb', 8192)),
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        Toggle::make('is_published')->label(__('admin.common.published'))->default(true),
                        Toggle::make('is_confirmed')->label(__('admin.service.is_confirmed'))->helperText(__('admin.service.is_confirmed_help')),
                    ]),
                    Textarea::make('source_reference')
                        ->label(__('admin.common.source'))
                        ->helperText(__('admin.common.source_help'))
                        ->rows(2)
                        ->maxLength(1000),
                ])
                ->columnSpanFull(),
        ]);
    }
}
