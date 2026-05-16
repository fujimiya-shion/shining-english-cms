<?php

use App\Models\Blog;
use App\Policies\BlogPolicy;

dataset('blogPolicyPermissions', [
    ['viewAny', 'ViewAny:Blog', false],
    ['view', 'View:Blog', true],
    ['create', 'Create:Blog', false],
    ['update', 'Update:Blog', true],
    ['delete', 'Delete:Blog', true],
    ['restore', 'Restore:Blog', true],
    ['forceDelete', 'ForceDelete:Blog', true],
    ['forceDeleteAny', 'ForceDeleteAny:Blog', false],
    ['restoreAny', 'RestoreAny:Blog', false],
    ['replicate', 'Replicate:Blog', true],
    ['reorder', 'Reorder:Blog', false],
]);

test('blog policy checks the expected permission', function (string $method, string $permission, bool $needsModel): void {
    $policy = new BlogPolicy;

    $arguments = $needsModel ? [new Blog] : [];

    assertPolicyChecksPermission($policy, $method, $permission, $arguments);
})->with('blogPolicyPermissions');
