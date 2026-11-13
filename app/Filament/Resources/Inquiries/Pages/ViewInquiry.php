<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    /**
     * Opening a new inquiry moves it to "in progress" so the badge count stays meaningful.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status === InquiryStatus::New) {
            $this->record->update(['status' => InquiryStatus::InProgress]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            InquiryResource::whatsappAction(),
            InquiryResource::callAction(),
            InquiryResource::emailAction(),
            InquiryResource::changeStatusAction(),
            DeleteAction::make(),
        ];
    }
}
