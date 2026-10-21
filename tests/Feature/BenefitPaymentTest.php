<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\BenefitPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BenefitPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }

    private function deceasedOfficer(): BasicInformation
    {
        return BasicInformation::factory()->create(['is_deceased' => 1, 'surname' => 'Nwachukwu']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'context' => 'retirement',
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'benefit_type' => 'gratuity',
            'amount' => '1250000.00',
            'payment_date' => now()->subMonth()->toDateString(),
            'reference_number' => 'INEC/VCH/2026/0012',
        ], $overrides);
    }

    public function test_a_retirement_benefit_payment_is_recorded(): void
    {
        $this->actingAs($this->actor())
            ->post(route('benefit-payments.store'), $this->payload())
            ->assertRedirect(route('retirement-exit'));

        $this->assertDatabaseHas('benefit_payments', [
            'benefit_type' => 'gratuity',
            'amount' => '1250000.00',
            'reference_number' => 'INEC/VCH/2026/0012',
        ]);
    }

    public function test_the_three_death_benefits_can_each_be_paid(): void
    {
        $officer = $this->deceasedOfficer();

        foreach (['condolence-pause', 'hazard', 'group-life'] as $benefit) {
            $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
                'context' => 'deceased',
                'basic_information_ulid' => $officer->ulid,
                'benefit_type' => $benefit,
            ]))->assertRedirect(route('deceased'));
        }

        $this->assertSame(
            ['condolence-pause', 'hazard', 'group-life'],
            BenefitPayment::orderBy('id')->pluck('benefit_type')->all()
        );
    }

    public function test_the_death_benefits_are_labelled_as_the_commission_names_them(): void
    {
        $this->assertSame([
            'condolence-pause' => 'Condolence Pause',
            'hazard' => 'Hazard',
            'group-life' => 'Group Life',
        ], BenefitPayment::DEATH_BENEFITS);
    }

    public function test_a_receipt_is_filed_against_the_payment(): void
    {
        Storage::fake();

        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'receipt' => UploadedFile::fake()->create('gratuity-receipt.pdf', 120, 'application/pdf'),
        ]))->assertRedirect(route('retirement-exit'));

        $payment = BenefitPayment::firstOrFail();

        $this->assertTrue($payment->has_receipt);
        $this->assertSame('gratuity-receipt.pdf', $payment->receipt_name);
        Storage::assertExists($payment->receipt_path);
    }

    public function test_a_receipt_is_kept_off_the_public_disk(): void
    {
        Storage::fake();
        Storage::fake('public');

        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 50, 'application/pdf'),
        ]));

        // Payment evidence must not be readable by anyone who guesses the URL.
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_a_receipt_can_be_downloaded_back(): void
    {
        Storage::fake();

        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'receipt' => UploadedFile::fake()->create('voucher.pdf', 40, 'application/pdf'),
        ]));

        $payment = BenefitPayment::firstOrFail();

        $response = $this->actingAs($this->actor())
            ->get(route('benefit-payments.receipt', $payment->ulid));

        $response->assertOk();
        $response->assertDownload('voucher.pdf');
    }

    public function test_a_receipt_is_not_served_to_a_guest(): void
    {
        Storage::fake();

        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'receipt' => UploadedFile::fake()->create('voucher.pdf', 40, 'application/pdf'),
        ]));

        $payment = BenefitPayment::firstOrFail();

        $this->post(route('logout'));
        $this->get(route('benefit-payments.receipt', $payment->ulid))->assertRedirect('/login');
    }

    public function test_a_payment_without_a_receipt_is_still_recorded_and_flagged(): void
    {
        $this->actingAs($this->actor())
            ->post(route('benefit-payments.store'), $this->payload())
            ->assertRedirect(route('retirement-exit'));

        $payment = BenefitPayment::firstOrFail();

        $this->assertFalse($payment->has_receipt);

        $this->actingAs($this->actor())
            ->get(route('retirement-exit'))
            ->assertSee('No receipt');
    }

    public function test_asking_for_a_receipt_that_was_never_filed_is_a_404(): void
    {
        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload());

        $this->actingAs($this->actor())
            ->get(route('benefit-payments.receipt', BenefitPayment::firstOrFail()->ulid))
            ->assertNotFound();
    }

    public function test_a_death_benefit_cannot_be_paid_from_the_retirement_page(): void
    {
        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'context' => 'retirement',
            'benefit_type' => 'group-life',
        ]))->assertSessionHasErrors('benefit_type');

        $this->assertSame(0, BenefitPayment::count());
    }

    public function test_a_gratuity_cannot_be_paid_from_the_deceased_page(): void
    {
        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'context' => 'deceased',
            'basic_information_ulid' => $this->deceasedOfficer()->ulid,
            'benefit_type' => 'gratuity',
        ]))->assertSessionHasErrors('benefit_type');

        $this->assertSame(0, BenefitPayment::count());
    }

    public function test_an_oversized_receipt_is_refused(): void
    {
        Storage::fake();

        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'receipt' => UploadedFile::fake()->create('huge.pdf', 5000, 'application/pdf'),
        ]))->assertSessionHasErrors('receipt');

        $this->assertSame(0, BenefitPayment::count());
    }

    public function test_an_executable_dressed_as_a_receipt_is_refused(): void
    {
        Storage::fake();

        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'receipt' => UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
        ]))->assertSessionHasErrors('receipt');

        $this->assertSame(0, BenefitPayment::count());
    }

    public function test_a_payment_in_the_future_is_refused(): void
    {
        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'payment_date' => now()->addWeek()->toDateString(),
        ]))->assertSessionHasErrors('payment_date');
    }

    public function test_an_amount_is_required(): void
    {
        $payload = $this->payload();
        unset($payload['amount']);

        $this->actingAs($this->actor())
            ->post(route('benefit-payments.store'), $payload)
            ->assertSessionHasErrors('amount');
    }

    public function test_only_officers_on_record_as_deceased_may_be_paid_a_death_benefit(): void
    {
        $this->deceasedOfficer();
        BasicInformation::factory()->create(['surname' => 'Serving', 'is_deceased' => 0]);

        $options = $this->actingAs($this->actor())->get(route('deceased'))->viewData('deceasedOptions');

        $this->assertCount(1, $options);
        $this->assertStringContainsString('Nwachukwu', implode(' ', $options));
    }

    public function test_each_page_lists_only_the_payments_that_belong_to_it(): void
    {
        $officer = $this->deceasedOfficer();

        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'benefit_type' => 'pension',
        ]));
        $this->actingAs($this->actor())->post(route('benefit-payments.store'), $this->payload([
            'context' => 'deceased',
            'basic_information_ulid' => $officer->ulid,
            'benefit_type' => 'hazard',
        ]));

        $retirement = $this->actingAs($this->actor())->get(route('retirement-exit'))
            ->viewData('payments')->getCollection()->pluck('benefit_type');
        $deceased = $this->actingAs($this->actor())->get(route('deceased'))
            ->viewData('payments')->getCollection()->pluck('benefit_type');

        $this->assertSame(['pension'], $retirement->all());
        $this->assertSame(['hazard'], $deceased->all());
    }
}
