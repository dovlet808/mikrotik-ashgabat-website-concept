<?php

namespace App\Filament\Resources\Inquiries;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Filament\Resources\Inquiries\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Inquiries\Schemas\InquiryInfolist;
use App\Filament\Resources\Inquiries\Tables\InquiriesTable;
use App\Models\Inquiry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.groups.leads');
    }

    public static function getModelLabel(): string
    {
        return __('admin.inquiry.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.inquiry.plural');
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record ? '#'.$record->getKey().' · '.$record->name : null;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Inquiry::query()->new()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return InquiryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InquiriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [NotesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInquiries::route('/'),
            'view' => ViewInquiry::route('/{record}'),
        ];
    }

    public static function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label(__('admin.inquiry.change_status'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->fillForm(fn (Inquiry $record) => ['status' => $record->status->value])
            ->schema([
                ToggleButtons::make('status')
                    ->label(__('admin.inquiry.status'))
                    ->options(InquiryStatus::class)
                    ->inline()
                    ->required(),
            ])
            ->action(function (Inquiry $record, array $data) {
                $record->update(['status' => $data['status']]);
                Notification::make()->success()->title(__('admin.inquiry.status_changed'))->send();
            });
    }

    public static function whatsappAction(): Action
    {
        return Action::make('whatsapp')
            ->label(__('admin.inquiry.whatsapp'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->url(fn (Inquiry $record) => 'https://wa.me/'.$record->whatsappDigits())
            ->openUrlInNewTab()
            ->visible(fn (Inquiry $record) => strlen($record->whatsappDigits()) >= 8);
    }

    public static function callAction(): Action
    {
        return Action::make('call')
            ->label(__('admin.inquiry.call'))
            ->icon(Heroicon::OutlinedPhone)
            ->color('gray')
            ->url(fn (Inquiry $record) => 'tel:+'.$record->whatsappDigits());
    }

    public static function emailAction(): Action
    {
        return Action::make('email')
            ->label(__('admin.inquiry.email_action'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->url(fn (Inquiry $record) => 'mailto:'.$record->email)
            ->visible(fn (Inquiry $record) => filled($record->email));
    }
}
