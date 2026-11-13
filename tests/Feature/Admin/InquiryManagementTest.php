<?php

namespace Tests\Feature\Admin;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Filament\Resources\Inquiries\RelationManagers\NotesRelationManager;
use App\Models\Inquiry;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_new_inquiry_marks_it_in_progress(): void
    {
        $this->actingAs($this->admin());
        $inquiry = Inquiry::factory()->create();

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getKey()])->assertSee('Тест Клиент');

        $this->assertSame(InquiryStatus::InProgress, $inquiry->fresh()->status);
        $this->assertNotNull($inquiry->fresh()->handled_at);
    }

    public function test_status_can_be_changed(): void
    {
        $this->actingAs($this->admin());
        $inquiry = Inquiry::factory()->create();

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getKey()])
            ->callAction('changeStatus', data: ['status' => InquiryStatus::Answered->value])
            ->assertHasNoActionErrors();

        $this->assertSame(InquiryStatus::Answered, $inquiry->fresh()->status);
    }

    public function test_internal_notes_record_their_author(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $inquiry = Inquiry::factory()->create();

        Livewire::test(NotesRelationManager::class, ['ownerRecord' => $inquiry, 'pageClass' => ViewInquiry::class])
            ->callAction(TestAction::make('create')->table(), data: ['body' => 'Перезвонили, ждём оплату'])
            ->assertHasNoActionErrors();

        $note = $inquiry->notes()->sole();
        $this->assertSame('Перезвонили, ждём оплату', $note->body);
        $this->assertSame($admin->id, $note->user_id);
    }

    public function test_list_can_be_filtered_by_status(): void
    {
        $this->actingAs($this->admin());
        $new = Inquiry::factory()->create();
        $spam = Inquiry::factory()->create(['status' => InquiryStatus::Spam, 'name' => 'Спамер']);

        Livewire::test(ListInquiries::class)
            ->assertCanSeeTableRecords([$new, $spam])
            ->filterTable('status', [InquiryStatus::New->value])
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$spam]);
    }

    public function test_inquiries_cannot_be_created_from_the_panel(): void
    {
        $this->actingAs($this->admin())->get('/admin/inquiries/create')->assertNotFound();
    }

    public function test_navigation_badge_counts_new_inquiries(): void
    {
        Inquiry::factory()->count(3)->create();
        Inquiry::factory()->create(['status' => InquiryStatus::Closed]);

        $this->assertSame('3', InquiryResource::getNavigationBadge());
    }
}
