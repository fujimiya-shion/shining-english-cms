<?php

use App\Models\Contact;
use App\Policies\ContactPolicy;

dataset('contactPolicyPermissions', [
    ['viewAny', 'ViewAny:Contact', false],
    ['view', 'View:Contact', true],
    ['create', 'Create:Contact', false],
    ['update', 'Update:Contact', true],
    ['delete', 'Delete:Contact', true],
    ['restore', 'Restore:Contact', true],
    ['forceDelete', 'ForceDelete:Contact', true],
    ['forceDeleteAny', 'ForceDeleteAny:Contact', false],
    ['restoreAny', 'RestoreAny:Contact', false],
    ['replicate', 'Replicate:Contact', true],
    ['reorder', 'Reorder:Contact', false],
]);

test('contact policy checks the expected permission', function (string $method, string $permission, bool $needsModel): void {
    $policy = new ContactPolicy;
    $arguments = $needsModel ? [new Contact] : [];

    assertPolicyChecksPermission($policy, $method, $permission, $arguments);
})->with('contactPolicyPermissions');
