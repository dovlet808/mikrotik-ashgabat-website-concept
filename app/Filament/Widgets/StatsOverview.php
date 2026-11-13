<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $withoutPhoto = Product::query()->where('is_published', true)->doesntHave('images')->count();
        $withoutPrice = Product::query()->where('is_published', true)->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0))->count();

        return [
            Stat::make(__('admin.dashboard.products'), Product::query()->count())
                ->description(__('admin.dashboard.products_desc', ['count' => Product::query()->where('is_published', true)->count()]))
                ->descriptionIcon(Heroicon::OutlinedCube)
                ->url(route('filament.admin.resources.products.index')),
            Stat::make(__('admin.dashboard.categories'), Category::query()->count())
                ->description(__('admin.dashboard.categories_desc', ['count' => Category::query()->where('is_visible', true)->count()]))
                ->descriptionIcon(Heroicon::OutlinedRectangleStack)
                ->url(route('filament.admin.resources.categories.index')),
            Stat::make(__('admin.dashboard.new_inquiries'), Inquiry::query()->new()->count())
                ->description(__('admin.dashboard.new_inquiries_desc', ['count' => Inquiry::query()->where('created_at', '>=', now()->subDays(7))->count()]))
                ->descriptionIcon(Heroicon::OutlinedInboxArrowDown)
                ->color(Inquiry::query()->new()->exists() ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.inquiries.index')),
            Stat::make(__('admin.dashboard.attention'), $withoutPhoto + $withoutPrice)
                ->description(__('admin.dashboard.attention_desc', ['photos' => $withoutPhoto, 'prices' => $withoutPrice]))
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($withoutPhoto + $withoutPrice > 0 ? 'warning' : 'success'),
        ];
    }
}
