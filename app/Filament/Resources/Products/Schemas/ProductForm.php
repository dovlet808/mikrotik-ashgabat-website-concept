<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\Availability;
use App\Enums\Segment;
use App\Filament\Support\Translatable;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('product')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    static::mainTab(),
                    static::priceTab(),
                    static::contentTab(),
                    static::imagesTab(),
                    static::specsTab(),
                    static::internalTab(),
                ]),
        ]);
    }

    protected static function mainTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.main'))
            ->icon(Heroicon::OutlinedCube)
            ->schema([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    TextInput::make('name')
                        ->label(__('admin.product.name'))
                        ->helperText(__('admin.product.name_help'))
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                            if ($operation === 'create' && blank($get('slug')) && filled($state)) {
                                $set('slug', Product::slugFromName($state));
                            }
                        }),
                    TextInput::make('product_code')
                        ->label(__('admin.product.product_code'))
                        ->helperText(__('admin.product.product_code_help'))
                        ->maxLength(120),
                    TextInput::make('brand')
                        ->label(__('admin.product.brand'))
                        ->required()
                        ->default('MikroTik')
                        ->maxLength(60)
                        ->datalist(['MikroTik', 'Fortinet', 'Cisco']),
                    Select::make('category_id')
                        ->label(__('admin.product.category'))
                        ->options(fn () => Category::query()->orderBy('sort_order')->get()
                            ->mapWithKeys(fn (Category $c) => [$c->getKey() => $c->translate('name', 'ru')]))
                        ->searchable()
                        ->required(),
                    TextInput::make('slug')
                        ->label(__('admin.common.slug'))
                        ->helperText(__('admin.common.slug_help'))
                        ->required()
                        ->maxLength(120)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(Product::class, 'slug', ignoreRecord: true),
                    TextInput::make('sort_order')
                        ->label(__('admin.common.sort_order'))
                        ->helperText(__('admin.common.sort_order_help'))
                        ->numeric()->integer()->minValue(0)->default(0),
                ]),
                CheckboxList::make('segments')
                    ->label(__('admin.product.segments'))
                    ->options(collect(Segment::cases())->mapWithKeys(fn (Segment $s) => [$s->value => __('site.segments.'.$s->value.'.label', [], 'ru')]))
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 3]),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Toggle::make('is_published')
                        ->label(__('admin.product.is_published'))
                        ->helperText(__('admin.product.is_published_help'))
                        ->default(true),
                    Toggle::make('is_featured')
                        ->label(__('admin.product.is_featured')),
                ]),
            ]);
    }

    protected static function priceTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.price'))
            ->icon(Heroicon::OutlinedBanknotes)
            ->schema([
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    TextInput::make('price')
                        ->label(__('admin.product.price'))
                        ->helperText(__('admin.product.price_help'))
                        ->numeric()
                        ->inputMode('decimal')
                        ->minValue(0)
                        ->maxValue(99999999)
                        ->step(1),
                    Select::make('currency')
                        ->label(__('admin.product.currency'))
                        ->options(['TMT' => 'TMT — манат', 'USD' => 'USD'])
                        ->default('TMT')
                        ->selectablePlaceholder(false)
                        ->required(),
                ]),
                ToggleButtons::make('availability')
                    ->label(__('admin.product.availability'))
                    ->helperText(__('admin.product.availability_help'))
                    ->options(collect(Availability::cases())->mapWithKeys(fn (Availability $a) => [$a->value => __('site.availability.'.$a->value, [], 'ru')]))
                    ->colors(collect(Availability::cases())->mapWithKeys(fn (Availability $a) => [$a->value => $a->getColor()]))
                    ->default(Availability::Confirm->value)
                    ->inline()
                    ->required(),
            ]);
    }

    protected static function contentTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.content'))
            ->icon(Heroicon::OutlinedDocumentText)
            ->schema([
                Section::make()
                    ->description(__('admin.common.translations_help'))
                    ->schema([
                        Translatable::tabs([
                            'summary' => [__('admin.product.summary'), fn ($path) => Textarea::make($path)->rows(3)->maxLength(600)->helperText(__('admin.product.summary_help')), true],
                            'purpose' => [__('admin.product.purpose'), fn ($path) => Textarea::make($path)->rows(3)->maxLength(600)->helperText(__('admin.product.purpose_help'))],
                            'description' => [__('admin.product.description'), fn ($path) => Textarea::make($path)->rows(10)->maxLength(10000)->helperText(__('admin.product.description_help'))],
                        ]),
                    ]),
            ]);
    }

    protected static function imagesTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.images'))
            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                Repeater::make('images')
                    ->label(__('admin.product.images'))
                    ->helperText(__('admin.product.images_help'))
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->grid(['default' => 1, 'lg' => 2])
                    ->addActionLabel(__('admin.product.add_image'))
                    ->itemLabel(fn (array $state): ?string => $state['alt']['ru'] ?? null)
                    ->schema([
                        FileUpload::make('path')
                            ->label(__('admin.product.image'))
                            ->image()
                            ->disk('uploads')
                            ->directory('products')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize((int) config('site.images.max_upload_kb', 8192))
                            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::lower(Str::random(6)).'.'.strtolower($file->getClientOriginalExtension()))
                            ->required(),
                        Translatable::inline('alt', __('admin.product.alt'), fn ($path) => TextInput::make($path)->maxLength(200), columns: 1),
                    ]),
            ]);
    }

    protected static function specsTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.specs'))
            ->icon(Heroicon::OutlinedListBullet)
            ->schema([
                Repeater::make('specifications')
                    ->label(__('admin.product.specs'))
                    ->helperText(__('admin.product.specs_help'))
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->collapsible()
                    ->collapsed(fn (string $operation) => $operation === 'edit')
                    ->addActionLabel(__('admin.product.add_spec'))
                    ->itemLabel(fn (array $state): ?string => trim(($state['label']['ru'] ?? '').(filled($state['value']['ru'] ?? null) ? ': '.Str::limit($state['value']['ru'], 70) : '')) ?: null)
                    ->schema([
                        Translatable::tabs([
                            'group' => [__('admin.product.spec_group'), fn ($path) => TextInput::make($path)->maxLength(120)],
                            'label' => [__('admin.product.spec_label'), fn ($path) => TextInput::make($path)->maxLength(160), true],
                            'value' => [__('admin.product.spec_value'), fn ($path) => Textarea::make($path)->rows(2)->maxLength(500), true],
                        ]),
                    ]),
            ]);
    }

    protected static function internalTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.internal'))
            ->icon(Heroicon::OutlinedInformationCircle)
            ->schema([
                Textarea::make('source_reference')
                    ->label(__('admin.common.source'))
                    ->helperText(__('admin.common.source_help'))
                    ->rows(4)
                    ->maxLength(2000),
            ]);
    }
}
