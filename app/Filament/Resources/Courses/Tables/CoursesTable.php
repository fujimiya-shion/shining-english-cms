<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->square()
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Tên khóa học')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Giá')
                    ->money('VND')
                    ->sortable(),
                IconColumn::make('allow_star_payment')
                    ->label('Thanh toán sao')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('star_price')
                    ->label('Giá sao')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('status')
                    ->label('Trạng thái'),
                TextColumn::make('category.name')
                    ->label('Danh mục')
                    ->searchable(),
                TextColumn::make('level.name')
                    ->label('Trình độ')
                    ->searchable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TernaryFilter::make('status'),
                TernaryFilter::make('allow_star_payment')
                    ->label('Thanh toán sao'),
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('level_id')
                    ->relationship('level', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')
                    ->label('Nhân bản')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function ($record): void {
                        $clone = $record->replicate();
                        $clone->name = $clone->name.' (Sao chép)';
                        $clone->slug = $clone->slug.'-copy';
                        $clone->save();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
