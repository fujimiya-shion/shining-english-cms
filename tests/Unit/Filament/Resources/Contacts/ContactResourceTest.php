<?php

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Contacts\ContactResource;
use App\Models\Contact;
use App\Services\Contact\IContactService;
use Illuminate\Database\Eloquent\Builder;

test('contact resource uses contact model and title attribute', function (): void {
    expect(ContactResource::getModel())->toBe(Contact::class);
    expect(ContactResource::getRecordTitleAttribute())->toBe('email');
    expect(is_subclass_of(ContactResource::class, BaseResource::class))->toBeTrue();
});

test('contact resource defines expected pages', function (): void {
    $pages = ContactResource::getPages();

    expect($pages)->toHaveKeys(['index', 'edit']);
});

test('contact resource configures form and table', function (): void {
    $schema = ContactResource::form(makeSchema());
    $table = ContactResource::table(makeTable());

    expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);
    expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);
});

test('contact resource builds query', function (): void {
    $query = ContactResource::getEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

test('contact resource resolves contact service', function (): void {
    $method = new ReflectionMethod(ContactResource::class, 'service');
    $method->setAccessible(true);
    $service = $method->invoke(null);

    expect($service)->toBeInstanceOf(IContactService::class);
});

