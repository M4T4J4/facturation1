<?php

use App\Models\Subscription;
use App\Models\User;
use App\UserRole;

it('administrators can update subscription status and keep an audit trail', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = Subscription::query()->create([
        'user_id' => $customer->id,
        'reference' => 'EV-SUB-001',
        'service_address' => 'Bonamoussadi, Douala',
        'status' => 'active',
        'monthly_fee' => 1000,
        'price_per_m3' => 400,
    ]);

    $this->actingAs($administrator)
        ->patch(route('admin.subscriptions.status.update', $subscription), [
            'status' => 'suspended',
        ])
        ->assertSessionHasNoErrors();

    expect($subscription->fresh()->status)->toBe('suspended')
        ->and($subscription->events()->exists())->toBeTrue();
});

it('administrators can transfer a subscription holder to an existing or new customer', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $oldCustomer = User::factory()->create(['role' => UserRole::Customer]);
    $newCustomer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = Subscription::query()->create([
        'user_id' => $oldCustomer->id,
        'reference' => 'EV-SUB-002',
        'service_address' => 'Nkolbisson, Yaoundé',
        'status' => 'active',
        'monthly_fee' => 1000,
        'price_per_m3' => 400,
    ]);

    $this->actingAs($administrator)
        ->patch(route('admin.subscriptions.holder.transfer', $subscription), [
            'new_customer' => false,
            'user_id' => $newCustomer->id,
        ])
        ->assertSessionHasNoErrors();

    expect($subscription->fresh()->user_id)->toBe($newCustomer->id)
        ->and($subscription->events()->where('event_type', 'holder_changed')->exists())->toBeTrue();
});

it('administrators can update the occupant details for a subscription', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = Subscription::query()->create([
        'user_id' => $customer->id,
        'reference' => 'EV-SUB-003',
        'service_address' => 'Melen, Yaoundé',
        'status' => 'active',
        'monthly_fee' => 1000,
        'price_per_m3' => 400,
        'occupant_name' => 'Ancien occupant',
        'occupant_phone' => '+237699000111',
    ]);

    $this->actingAs($administrator)
        ->patch(route('admin.subscriptions.occupant.update', $subscription), [
            'occupant_name' => 'Nouveau occupant',
            'occupant_phone' => '+237699000222',
        ])
        ->assertSessionHasNoErrors();

    expect($subscription->fresh()->occupant_name)->toBe('Nouveau occupant')
        ->and($subscription->fresh()->occupant_phone)->toBe('+237699000222');
});
