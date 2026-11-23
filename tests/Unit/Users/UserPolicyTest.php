<?php

use App\Modules\Users\Enums\UserType;
use App\Modules\Users\Models\Identity;
use App\Modules\Users\Policies\UserPolicy;
use Tests\TestCase;

uses(TestCase::class);

function policyActor(UserType $type, int $id): Identity
{
    $actor = new Identity;
    $actor->id = $id;
    $actor->type = $type;

    return $actor;
}

function policyTarget(UserType $type, int $id): Identity
{
    return policyActor($type, $id);
}

describe('UserPolicy', function () {
    test('Super Admin bypasses every ability', function () {
        $policy = new UserPolicy;

        expect($policy->before(policyActor(UserType::SuperAdmin, 1), 'view'))->toBeTrue();
    });

    test('before returns null for non Super Admins so abilities still run', function () {
        $policy = new UserPolicy;

        expect($policy->before(policyActor(UserType::Admin, 2), 'view'))->toBeNull();
    });

    test('Admin may view any and create', function () {
        $policy = new UserPolicy;

        expect($policy->viewAny(policyActor(UserType::Admin, 2)))->toBeTrue()
            ->and($policy->create(policyActor(UserType::Admin, 2)))->toBeTrue();
    });

    test('Admin may view and update other non Super Admin users', function () {
        $policy = new UserPolicy;
        $actor = policyActor(UserType::Admin, 2);

        expect($policy->view($actor, policyTarget(UserType::Admin, 3)))->toBeTrue()
            ->and($policy->update($actor, policyTarget(UserType::Admin, 3)))->toBeTrue();
    });

    test('Admin may never mutate or delete a Super Admin row', function () {
        $policy = new UserPolicy;
        $actor = policyActor(UserType::Admin, 2);

        expect($policy->update($actor, policyTarget(UserType::SuperAdmin, 1)))->toBeFalse()
            ->and($policy->delete($actor, policyTarget(UserType::SuperAdmin, 1)))->toBeFalse()
            ->and($policy->changeType($actor, policyTarget(UserType::SuperAdmin, 1)))->toBeFalse();
    });

    test('Admin may not delete themselves', function () {
        $policy = new UserPolicy;
        $actor = policyActor(UserType::Admin, 2);

        expect($policy->delete($actor, policyTarget(UserType::Admin, 2)))->toBeFalse();
    });
});
