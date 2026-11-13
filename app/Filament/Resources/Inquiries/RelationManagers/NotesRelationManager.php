<?php

namespace App\Filament\Resources\Inquiries\RelationManagers;

use App\Models\InquiryNote;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.inquiry.notes');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->label(__('admin.inquiry.note'))
                ->required()
                ->rows(4)
                ->maxLength(4000)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(__('admin.inquiry.note'))
            ->pluralModelLabel(__('admin.inquiry.notes'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('body')->label(__('admin.inquiry.note'))->wrap(),
                TextColumn::make('user.name')->label(__('admin.inquiry.note_author'))->placeholder('—'),
                TextColumn::make('created_at')->label(__('admin.common.created'))->dateTime('d.m.Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.inquiry.add_note'))
                    ->mutateDataUsing(fn (array $data) => $data + ['user_id' => auth()->id()]),
            ])
            ->recordActions([
                DeleteAction::make()->visible(fn (InquiryNote $record) => $record->user_id === auth()->id()),
            ]);
    }
}
