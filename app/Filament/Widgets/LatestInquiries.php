<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestInquiries extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.dashboard.latest_inquiries'))
            ->query(Inquiry::query()->with('service')->latest()->limit(8))
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard.empty_inquiries'))
            ->recordUrl(fn (Inquiry $record) => InquiryResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('created_at')->label(__('admin.inquiry.created_at'))->since(),
                TextColumn::make('status')->label(__('admin.inquiry.status'))->badge(),
                TextColumn::make('type')->label(__('admin.inquiry.type'))->badge()->color('gray'),
                TextColumn::make('name')->label(__('admin.inquiry.name'))->weight('semibold')->description(fn (Inquiry $r) => $r->phone),
                TextColumn::make('product_snapshot')
                    ->label(__('admin.inquiry.product'))
                    ->state(fn (Inquiry $r) => $r->product_snapshot ?? $r->service?->translate('title', 'ru'))
                    ->limit(40)
                    ->placeholder('—'),
            ]);
    }
}
