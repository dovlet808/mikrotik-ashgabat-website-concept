<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Inquiry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                Section::make(__('admin.inquiry.sections.request'))
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextEntry::make('type')->label(__('admin.inquiry.type'))->badge()->color('gray'),
                            TextEntry::make('status')->label(__('admin.inquiry.status'))->badge(),
                            TextEntry::make('product_snapshot')
                                ->label(__('admin.inquiry.product_snapshot'))
                                ->placeholder('—')
                                ->url(fn (Inquiry $record) => $record->product ? ProductResource::getUrl('edit', ['record' => $record->product]) : null),
                            TextEntry::make('service_id')
                                ->label(__('admin.inquiry.service'))
                                ->formatStateUsing(fn ($state, Inquiry $record) => $record->service?->translate('title', 'ru'))
                                ->placeholder('—'),
                        ]),
                        TextEntry::make('message')
                            ->label(__('admin.inquiry.message'))
                            ->placeholder(__('admin.inquiry.no_message'))
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.inquiry.sections.contact'))
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('name')->label(__('admin.inquiry.name'))->weight('semibold'),
                        TextEntry::make('phone')->label(__('admin.inquiry.phone'))->copyable()->fontFamily('mono'),
                        TextEntry::make('email')->label(__('admin.inquiry.email'))->copyable()->placeholder('—'),
                        TextEntry::make('preferred_contact')->label(__('admin.inquiry.preferred_contact'))->badge()->color('info'),
                    ]),
            ]),
            Section::make(__('admin.inquiry.sections.meta'))
                ->collapsible()
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 4])->schema([
                        TextEntry::make('created_at')->label(__('admin.inquiry.created_at'))->dateTime('d.m.Y H:i'),
                        TextEntry::make('handled_at')->label(__('admin.inquiry.handled_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
                        TextEntry::make('locale')->label(__('admin.inquiry.locale'))->formatStateUsing(fn ($state) => strtoupper($state)),
                        TextEntry::make('page_url')->label(__('admin.inquiry.page_url'))->url(fn ($state) => $state, true)->limit(60)->placeholder('—'),
                    ]),
                ]),
        ]);
    }
}
