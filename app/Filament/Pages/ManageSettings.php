<?php

namespace App\Filament\Pages;

use App\Filament\Support\Translatable;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('admin.groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.settings.title');
    }

    public function mount(): void
    {
        $values = app(Settings::class)->all();

        foreach (Settings::TRANSLATABLE as $key) {
            $values[$key] = is_array($values[$key] ?? null) ? $values[$key] : [];
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('admin.settings.sections.contacts'))
                    ->description(__('admin.settings.sections.contacts_help'))
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('phone')
                                ->label(__('admin.settings.phone'))
                                ->helperText(__('admin.settings.phone_help'))
                                ->tel()
                                ->regex('/^\+?[\d\s()\-]{6,40}$/')
                                ->required(),
                            TextInput::make('whatsapp')
                                ->label(__('admin.settings.whatsapp'))
                                ->helperText(__('admin.settings.whatsapp_help'))
                                ->regex('/^\d{8,15}$/')
                                ->required(),
                            TextInput::make('instagram_handle')
                                ->label(__('admin.settings.instagram_handle'))
                                ->prefix('@')
                                ->maxLength(60),
                            TextInput::make('instagram_url')
                                ->label(__('admin.settings.instagram_url'))
                                ->url()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label(__('admin.settings.email'))
                                ->email()
                                ->maxLength(160),
                            TextInput::make('city')
                                ->label(__('admin.settings.city'))
                                ->maxLength(80),
                        ]),
                    ]),
                Section::make(__('admin.settings.sections.location'))
                    ->description(__('admin.settings.sections.location_help'))
                    ->collapsible()
                    ->schema([
                        Translatable::tabs([
                            'address' => [__('admin.settings.address'), fn ($path) => Textarea::make($path)->rows(2)->maxLength(300)],
                            'working_hours' => [__('admin.settings.working_hours'), fn ($path) => TextInput::make($path)->maxLength(160)],
                        ]),
                    ]),
                Section::make(__('admin.settings.sections.delivery'))
                    ->collapsible()
                    ->schema([
                        Translatable::tabs([
                            'delivery_text' => [__('admin.settings.delivery_text'), fn ($path) => TextInput::make($path)->maxLength(160)->helperText(__('admin.settings.delivery_text_help'))],
                            'delivery_note' => [__('admin.settings.delivery_note'), fn ($path) => Textarea::make($path)->rows(2)->maxLength(400)],
                            'price_note' => [__('admin.settings.price_note'), fn ($path) => Textarea::make($path)->rows(2)->maxLength(400)],
                        ]),
                        Toggle::make('show_prices')->label(__('admin.settings.show_prices')),
                    ]),
                Section::make(__('admin.settings.sections.notifications'))
                    ->collapsible()
                    ->schema([
                        TextInput::make('notification_email')
                            ->label(__('admin.settings.notification_email'))
                            ->helperText(__('admin.settings.notification_email_help'))
                            ->email()
                            ->maxLength(160),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $data['whatsapp'] = preg_replace('/\D+/', '', (string) ($data['whatsapp'] ?? ''));
        $data['instagram_handle'] = ltrim((string) ($data['instagram_handle'] ?? ''), '@') ?: null;
        $data['show_prices'] = (bool) ($data['show_prices'] ?? true);

        foreach (Settings::TRANSLATABLE as $key) {
            $value = array_filter((array) ($data[$key] ?? []), fn ($v) => filled($v));
            $data[$key] = $value ?: null;
        }

        app(Settings::class)->save($data);

        Notification::make()->success()->title(__('admin.settings.saved'))->send();
    }
}
