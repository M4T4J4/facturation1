<?php

use App\Mail\InvoiceIssued;
use App\Models\Tariff;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Mail;

it('creates an effective tariff with ordered consumption tiers', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);

    $this->actingAs($administrator)->post(route('admin.tariffs.store'), [
        'name' => 'Tarif résidentiel',
        'usage_type' => 'residential',
        'effective_from' => '2026-10-01',
        'fixed_fee' => 500,
        'tax_percent' => 10,
        'tiers' => [
            ['consumption_from' => 1, 'consumption_to' => 10, 'unit_price' => 100],
            ['consumption_from' => 11, 'consumption_to' => null, 'unit_price' => 250],
        ],
    ])->assertRedirect(route('admin.tariffs.index'));

    $tariff = Tariff::query()->with('tiers')->firstOrFail();

    expect($tariff->tiers)->toHaveCount(2)
        ->and($tariff->tiers->last()->consumption_to)->toBeNull();
});

it('rejects tariff tranches with gaps and date ranges overlapping an existing tariff', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    Tariff::query()->create([
        'name' => 'Tarif déjà actif',
        'usage_type' => 'residential',
        'effective_from' => '2026-10-01',
        'fixed_fee' => 500,
        'tax_percent' => 0,
    ]);

    $this->actingAs($administrator)->post(route('admin.tariffs.store'), [
        'name' => 'Tarif en conflit',
        'usage_type' => 'residential',
        'effective_from' => '2026-10-15',
        'fixed_fee' => 500,
        'tax_percent' => 0,
        'tiers' => [
            ['consumption_from' => 1, 'consumption_to' => 10, 'unit_price' => 100],
            ['consumption_from' => 12, 'consumption_to' => null, 'unit_price' => 250],
        ],
    ])->assertSessionHasErrors(['effective_from', 'tiers.1.consumption_from']);

    expect(Tariff::query()->count())->toBe(1);
});

it('forbids customers from creating tariff profiles', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->post(route('admin.tariffs.store'), [])
        ->assertForbidden();
});

it('calculates a progressive invoice using the tariff effective for its period', function () {
    Mail::fake();

    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $connectionRequest = $customer->connectionRequests()->create([
        'service_address' => 'Bonamoussadi, Douala',
        'usage_type' => 'residential',
        'status' => 'connected',
    ]);
    $subscription = $customer->subscriptions()->create([
        'connection_request_id' => $connectionRequest->id,
        'reference' => 'EV-TARIFF-001',
        'service_address' => 'Bonamoussadi, Douala',
        'status' => 'active',
        'monthly_fee' => 1000,
        'price_per_m3' => 400,
    ]);
    $tariff = Tariff::query()->create([
        'name' => 'Tarif progressif',
        'usage_type' => 'residential',
        'effective_from' => '2026-10-01',
        'fixed_fee' => 500,
        'tax_percent' => 10,
    ]);
    $tariff->tiers()->createMany([
        ['position' => 1, 'consumption_from' => 1, 'consumption_to' => 10, 'unit_price' => 100],
        ['position' => 2, 'consumption_from' => 11, 'consumption_to' => null, 'unit_price' => 250],
    ]);
    $reading = $subscription->meterReadings()->create([
        'previous_index' => 20,
        'current_index' => 35,
        'read_at' => '2026-10-12',
        'status' => 'verified',
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.water.dashboard'))
        ->assertOk()
        ->assertSee('Relevés vérifiés sans facture')
        ->assertSee('Générer et envoyer la facture');

    $this->actingAs($administrator)->post(route('admin.water.readings.invoice', $reading))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $invoice = $subscription->invoices()->firstOrFail();
    expect($invoice->water_amount)->toBe(2250)
        ->and($invoice->monthly_fee)->toBe(500)
        ->and($invoice->tax_amount)->toBe(275)
        ->and($invoice->amount)->toBe(3025)
        ->and($invoice->tariff_breakdown)->toHaveCount(2);

    Mail::assertSent(InvoiceIssued::class, fn (InvoiceIssued $mail): bool => $mail->hasTo($customer->email)
        && $mail->invoice->is($invoice)
        && str_starts_with($mail->pdfContents, '%PDF'));

    $pdfResponse = $this->actingAs($administrator)
        ->get(route('admin.water.invoices.download', $invoice))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect(substr($pdfResponse->getContent(), 0, 4))->toBe('%PDF');

    $this->actingAs($administrator)
        ->post(route('admin.water.invoices.send', $invoice))
        ->assertSessionHas('status', 'invoice-email-sent');
    Mail::assertSentTimes(InvoiceIssued::class, 2);
});

it('lets customers generate a factura from verified readings and download the attached pdf', function () {
    Mail::fake();

    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-CLIENT-ISSUE-001',
        'service_address' => 'Akwa, Douala',
        'meter_number' => 'CP-CLIENT-001',
        'status' => 'active',
    ]);
    $meter = $subscription->waterMeter()->create([
        'serial_number' => 'CP-CLIENT-001',
        'status' => 'active',
        'installed_at' => '2026-10-01',
    ]);
    $submittedReading = $subscription->meterReadings()->create([
        'water_meter_id' => $meter->id,
        'previous_index' => 10,
        'current_index' => 16,
        'read_at' => '2026-10-20',
        'status' => 'submitted',
    ]);
    $verifiedReading = $subscription->meterReadings()->create([
        'water_meter_id' => $meter->id,
        'previous_index' => 16,
        'current_index' => 24,
        'read_at' => '2026-10-21',
        'status' => 'verified',
    ]);

    $this->actingAs($customer)
        ->get(route('client.readings.index'))
        ->assertOk()
        ->assertSee('Générer et envoyer')
        ->assertSee('Après vérification');

    $this->actingAs($customer)
        ->post(route('client.readings.invoice', $submittedReading))
        ->assertSessionHasErrors('invoice');

    $this->actingAs($customer)
        ->post(route('client.readings.invoice', $verifiedReading))
        ->assertRedirect(route('client.invoices.index'))
        ->assertSessionHas('status', 'invoice-created');

    $invoice = $subscription->invoices()->firstOrFail();
    expect($invoice->consumption_m3)->toBe(8);

    Mail::assertSent(InvoiceIssued::class, function (InvoiceIssued $mail) use ($customer, $invoice): bool {
        $mail->assertHasAttachedData($mail->pdfContents, $invoice->number.'.pdf', ['mime' => 'application/pdf']);

        return $mail->hasTo($customer->email)
            && $mail->invoice->is($invoice)
            && str_starts_with($mail->pdfContents, '%PDF');
    });

    $pdfResponse = $this->actingAs($customer)
        ->get(route('client.invoices.download', $invoice))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect(substr($pdfResponse->getContent(), 0, 4))->toBe('%PDF');

    $anotherCustomer = User::factory()->create(['role' => UserRole::Customer]);
    $this->actingAs($anotherCustomer)
        ->get(route('client.invoices.download', $invoice))
        ->assertForbidden();
});

it('does not create an invoice from a reading before it is verified', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-UNVERIFIED-001',
        'service_address' => 'Akwa, Douala',
        'status' => 'active',
    ]);
    $reading = $subscription->meterReadings()->create([
        'previous_index' => 5,
        'current_index' => 15,
        'read_at' => '2026-10-20',
        'status' => 'submitted',
    ]);

    $this->actingAs($administrator)
        ->post(route('admin.water.readings.invoice', $reading))
        ->assertUnprocessable();

    expect($subscription->invoices()->count())->toBe(0)
        ->and($reading->fresh()->status)->toBe('submitted');
});

it('creates monthly invoices from validated readings while leaving unvalidated readings alone', function () {
    Mail::fake();
    $this->travelTo(now()->setDate(2026, 11, 1)->startOfDay());

    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-MONTHLY-001',
        'service_address' => 'Akwa, Douala',
        'status' => 'active',
        'monthly_fee' => 1000,
        'price_per_m3' => 400,
    ]);
    $verifiedReading = $subscription->meterReadings()->create([
        'previous_index' => 5,
        'current_index' => 15,
        'read_at' => '2026-10-20',
        'status' => 'verified',
    ]);
    $unverifiedReading = $subscription->meterReadings()->create([
        'previous_index' => 15,
        'current_index' => 25,
        'read_at' => '2026-10-25',
        'status' => 'submitted',
    ]);

    $this->artisan('water:generate-invoices')->assertSuccessful();
    $this->artisan('water:generate-invoices')->assertSuccessful();

    expect($subscription->invoices()->count())->toBe(1)
        ->and($verifiedReading->invoice()->exists())->toBeTrue()
        ->and($unverifiedReading->invoice()->exists())->toBeFalse();

    Mail::assertSent(InvoiceIssued::class, $customer->email);
});

it('keeps partial balances open until confirmed payments cover the invoice', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-PARTIAL-001',
        'service_address' => 'Deido, Douala',
        'status' => 'active',
    ]);
    $invoice = $subscription->invoices()->create([
        'number' => 'FAC-2610-PART01',
        'period' => '2026-10-01',
        'consumption_m3' => 5,
        'price_per_m3' => 400,
        'monthly_fee' => 1000,
        'amount' => 3000,
        'due_at' => '2026-11-14',
        'status' => 'unpaid',
    ]);

    $this->actingAs($customer)->post(route('client.invoices.payments.store', $invoice), [
        'channel' => 'wave',
        'amount' => 1200,
    ])->assertRedirect();
    $firstPayment = $invoice->payments()->firstOrFail();
    $this->actingAs($administrator)->patch(route('admin.water.payments.update', $firstPayment), [
        'status' => 'confirmed',
    ])->assertRedirect();

    expect($invoice->fresh()->amount_paid)->toBe(1200)
        ->and($invoice->fresh()->status)->toBe('partial');

    $this->actingAs($customer)->post(route('client.invoices.payments.store', $invoice), [
        'channel' => 'cash',
        'amount' => 1800,
    ])->assertRedirect();
    $secondPayment = $invoice->payments()->where('status', 'pending')->firstOrFail();
    $this->actingAs($administrator)->patch(route('admin.water.payments.update', $secondPayment), [
        'status' => 'confirmed',
    ])->assertRedirect();

    expect($invoice->fresh()->amount_paid)->toBe(3000)
        ->and($invoice->fresh()->status)->toBe('paid')
        ->and($invoice->fresh()->paid_at)->not->toBeNull();
});

it('rejects a payment request greater than the remaining invoice balance', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-OVERPAY-001',
        'service_address' => 'Akwa, Douala',
        'status' => 'active',
    ]);
    $invoice = $subscription->invoices()->create([
        'number' => 'FAC-2610-OVER01',
        'period' => '2026-10-01',
        'consumption_m3' => 5,
        'price_per_m3' => 400,
        'monthly_fee' => 1000,
        'amount' => 3000,
        'amount_paid' => 1200,
        'due_at' => '2026-11-14',
        'status' => 'partial',
    ]);

    $this->actingAs($customer)->post(route('client.invoices.payments.store', $invoice), [
        'channel' => 'wave',
        'amount' => 1801,
    ])->assertSessionHasErrors('amount');

    expect($invoice->payments()->count())->toBe(0);
});
