<?php

namespace App\Filament\Resources\Inquiries\Tables;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class InquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['service']))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Inquiry $record) => InquiryResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->color('gray'),
                TextColumn::make('created_at')
                    ->label(__('admin.inquiry.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (Inquiry $record) => $record->created_at?->diffForHumans())
                    ->sortable(),
                TextColumn::make('status')->label(__('admin.inquiry.status'))->badge(),
                TextColumn::make('type')->label(__('admin.inquiry.type'))->badge()->color('gray'),
                TextColumn::make('name')
                    ->label(__('admin.inquiry.name'))
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (Inquiry $record) => $record->phone),
                TextColumn::make('product_snapshot')
                    ->label(__('admin.inquiry.product'))
                    ->state(fn (Inquiry $record) => $record->product_snapshot ?? $record->service?->translate('title', 'ru'))
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('preferred_contact')->label(__('admin.inquiry.preferred_contact'))->badge()->color('info')->toggleable(),
                TextColumn::make('phone')->label(__('admin.inquiry.phone'))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')->label(__('admin.inquiry.email'))->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.inquiry.status'))
                    ->options(InquiryStatus::class)
                    ->multiple(),
                SelectFilter::make('type')
                    ->label(__('admin.inquiry.type'))
                    ->options(InquiryType::class),
                Filter::make('created_at')
                    ->label(__('admin.inquiry.period'))
                    ->schema([
                        DatePicker::make('from')->label(__('admin.inquiry.from')),
                        DatePicker::make('until')->label(__('admin.inquiry.until')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = __('admin.inquiry.from').': '.$data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('admin.inquiry.until').': '.$data['until'];
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    InquiryResource::changeStatusAction(),
                    InquiryResource::whatsappAction(),
                    InquiryResource::callAction(),
                    InquiryResource::emailAction(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markInProgress')
                        ->label(__('admin.inquiry.mark_in_progress'))
                        ->icon(Heroicon::OutlinedPlay)
                        ->action(fn (Collection $records) => $records->each(fn (Inquiry $i) => $i->update(['status' => InquiryStatus::InProgress])))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('markSpam')
                        ->label(__('admin.inquiry.mark_spam'))
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(fn (Inquiry $i) => $i->update(['status' => InquiryStatus::Spam])))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
