<?php

use App\Models\Subscription;
use App\Models\User;
use App\Models\WaterZone;
use App\UserRole;

it('administrators can create a distribution zone and assign subscriptions and field agents', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $agent = User::factory()->create(['role' => UserRole::FieldAgent]);
    $subscription = Subscription::query()->create([
        'user_id' => $customer->id,
        'reference' => 'EV-ZONE-001',
        'service_address' => 'Bonamoussadi, Douala',
        'status' => 'active',
    ]);

    $this->actingAs($administrator)->post(route('admin.zones.store'), [
        'name' => 'Bonamoussadi',
        'code' => 'BON',
        'district' => 'Douala V',
        'description' => 'Secteur nord.',
    ])->assertSessionHasNoErrors();

    $zone = WaterZone::query()->where('code', 'BON')->firstOrFail();
    $this->post(route('admin.zones.subscriptions.assign', $zone), [
        'subscription_id' => $subscription->id,
    ])->assertSessionHasNoErrors();
    $this->put(route('admin.zones.agents.sync', $zone), [
        'agent_ids' => [$agent->id],
    ])->assertSessionHasNoErrors();

    expect($subscription->fresh()->water_zone_id)->toBe($zone->id)
        ->and($zone->agents()->whereKey($agent->id)->exists())->toBeTrue();
    $this->assertDatabaseHas('subscription_events', [
        'subscription_id' => $subscription->id,
        'event_type' => 'zone_changed',
    ]);
});

it('shows monthly invoiced consumption totals for each distribution zone', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $zone = WaterZone::query()->create([
        'name' => 'Akwa',
        'code' => 'AKW',
        'district' => 'Douala I',
    ]);
    $subscription = Subscription::query()->create([
        'user_id' => $customer->id,
        'reference' => 'EV-ZONE-002',
        'service_address' => 'Akwa, Douala',
        'status' => 'active',
        'water_zone_id' => $zone->id,
    ]);
    $reading = $subscription->meterReadings()->create([
        'previous_index' => 10,
        'current_index' => 37,
        'read_at' => now()->startOfMonth()->addDays(3)->toDateString(),
        'status' => 'verified',
    ]);
    $subscription->invoices()->create([
        'meter_reading_id' => $reading->id,
        'billed_to_user_id' => $customer->id,
        'water_zone_id' => $zone->id,
        'number' => 'FAC-ZONE-002',
        'period' => now()->startOfMonth()->toDateString(),
        'consumption_m3' => 27,
        'price_per_m3' => 400,
        'monthly_fee' => 1000,
        'amount' => 11800,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);
    $anotherZone = WaterZone::query()->create(['name' => 'New Bell', 'code' => 'NB']);
    $subscription->update(['water_zone_id' => $anotherZone->id]);

    $this->actingAs($administrator)
        ->get(route('admin.zones.index'))
        ->assertOk()
        ->assertViewIs('admin.zones.index')
        ->assertSee('Akwa')
        ->assertSee('27 m³')
        ->assertSee('11 800');
});

it('stores the distribution zone on invoices when billing a meter reading', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $zone = WaterZone::query()->create(['name' => 'Bépanda', 'code' => 'BEP']);
    $subscription = Subscription::query()->create([
        'user_id' => $customer->id,
        'reference' => 'EV-ZONE-004',
        'service_address' => 'Bépanda, Douala',
        'status' => 'active',
        'water_zone_id' => $zone->id,
    ]);
    $reading = $subscription->meterReadings()->create([
        'previous_index' => 10,
        'current_index' => 20,
        'read_at' => now()->toDateString(),
        'status' => 'verified',
    ]);

    $this->actingAs($administrator)
        ->post(route('admin.water.readings.invoice', $reading))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('invoices', [
        'subscription_id' => $subscription->id,
        'water_zone_id' => $zone->id,
        'consumption_m3' => 10,
    ]);
});

it('limits field agents to active subscriptions in zones assigned to them', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $assignedAgent = User::factory()->create(['role' => UserRole::FieldAgent]);
    $unassignedAgent = User::factory()->create(['role' => UserRole::FieldAgent]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $zone = WaterZone::query()->create(['name' => 'Deido', 'code' => 'DEI']);
    $subscription = Subscription::query()->create([
        'user_id' => $customer->id,
        'reference' => 'EV-ZONE-003',
        'service_address' => 'Deido, Douala',
        'status' => 'active',
        'water_zone_id' => $zone->id,
    ]);
    $subscription->waterMeter()->create([
        'serial_number' => 'METER-ZONE-003',
        'status' => 'active',
    ]);
    $zone->agents()->attach($assignedAgent);

    $this->actingAs($assignedAgent)
        ->get(route('agent.readings.index'))
        ->assertOk()
        ->assertSee('EV-ZONE-003');

    $this->actingAs($unassignedAgent)
        ->get(route('agent.readings.index'))
        ->assertOk()
        ->assertDontSee('EV-ZONE-003');
    $this->post(route('agent.readings.store'), [
        'reference' => $subscription->reference,
        'current_index' => 12,
        'read_at' => now()->toDateString(),
    ])->assertNotFound();

    expect($subscription->meterReadings()->count())->toBe(0);

    $this->actingAs($assignedAgent)->post(route('agent.readings.store'), [
        'reference' => $subscription->reference,
        'current_index' => 12,
        'read_at' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    expect($subscription->meterReadings()->count())->toBe(1);

    $zone->update(['is_active' => false]);
    $this->post(route('agent.readings.store'), [
        'reference' => $subscription->reference,
        'current_index' => 13,
        'read_at' => now()->toDateString(),
    ])->assertNotFound();

    expect($subscription->meterReadings()->count())->toBe(1);
});

it('does not allow an administrator to assign a customer account as a field agent', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $zone = WaterZone::query()->create(['name' => 'Bépanda', 'code' => 'BEP']);

    $this->actingAs($administrator)
        ->put(route('admin.zones.agents.sync', $zone), [
            'agent_ids' => [$customer->id],
        ])
        ->assertSessionHasErrors('agent_ids');

    expect($zone->agents()->exists())->toBeFalse();
});

it('denies distribution zone administration to non-administrators', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route('admin.zones.index'))->assertForbidden();
})->with([
    UserRole::Customer,
    UserRole::FieldAgent,
]);
