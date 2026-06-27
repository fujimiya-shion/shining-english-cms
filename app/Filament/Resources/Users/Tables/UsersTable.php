<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->circular()
                    ->disk('public'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('nickname')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('birthday')
                    ->date()
                    ->sortable(),
                TextColumn::make('city.name')
                    ->searchable(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('city_id')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('email_verified_at'),
                        false: fn ($query) => $query->whereNull('email_verified_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')
                    ->label('Nhân bản')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function ($record): void {
                        $clone = $record->replicate();
                        $clone->name = $clone->name.' (Sao chép)';
                        $clone->email = $clone->email.'-copy';
                        $clone->save();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
