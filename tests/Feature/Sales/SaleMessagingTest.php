<?php

namespace Tests\Feature\Sales;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Notifications\BuyerInvoicePaymentConfirmation;
use App\Modules\Invoices\Notifications\LandlordSalePaymentConfirmation;
use App\Modules\Invoices\Notifications\SaleInvoiceCreated;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Notifications\SaleCreatedNotice;
use App\Modules\Sales\Services\SaleService;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * A sale used to text the buyer once per installment (each installment
 * invoice fired its own "new bill" message). It now sends one message
 * describing the whole sale, then a message to the buyer AND the
 * landlord for every installment payment.
 */
class SaleMessagingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{Sale, Buyer, Landlord}
     */
    protected function makeSale(): array
    {
        $company = Company::factory()->create();
        $buyer = Buyer::factory()->create(['company_id' => $company->id, 'name' => 'Asha Buyer']);
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id, 'name' => 'Palm Court']);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => 'A1']);

        $sale = app(SaleService::class)->create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'new_buyer_id' => $buyer->id,
            'sale_date' => now()->toDateString(),
            'total_price' => 10000,
            'down_payment' => 2000,
            'installment_period' => 4,
            'installment_amount' => 2000,
        ]);

        return [$sale, $buyer, $landlord];
    }

    public function test_creating_a_sale_sends_the_buyer_one_message_not_one_per_installment(): void
    {
        Notification::fake();

        [$sale, $buyer] = $this->makeSale();

        $this->assertSame(4, $sale->installments()->count());
        Notification::assertSentToTimes($buyer, SaleCreatedNotice::class, 1);
        Notification::assertNotSentTo($buyer, SaleInvoiceCreated::class);
    }

    public function test_the_sale_message_carries_the_price_paid_balance_and_plan(): void
    {
        Notification::fake();

        [$sale, $buyer] = $this->makeSale();

        Notification::assertSentTo($buyer, SaleCreatedNotice::class, function ($notification) {
            $data = (fn () => $this->data)->call($notification);

            return $data['total_price'] === '10,000'
                && $data['total_paid'] === '2,000'
                && $data['balance'] === '8,000'
                && $data['installments_total'] === 4
                && $data['installment_amount'] === '2,000';
        });
    }

    public function test_paying_an_installment_messages_the_buyer_and_the_landlord_with_what_remains(): void
    {
        [$sale, $buyer, $landlord] = $this->makeSale();
        Notification::fake();

        app(SaleService::class)->recordPayment($sale->installments()->orderBy('installment_number')->first(), 2000);

        $check = function ($notification) {
            $data = (fn () => $this->data)->call($notification);

            return $data['installment_number'] === 1
                && $data['installments_total'] === 4
                && $data['installments_remaining'] === 3
                && $data['balance'] === '6,000'
                && $data['amount'] === '2,000';
        };

        Notification::assertSentTo($buyer, BuyerInvoicePaymentConfirmation::class, $check);
        Notification::assertSentTo($landlord, LandlordSalePaymentConfirmation::class, $check);
    }

    public function test_the_payment_texts_render_from_the_shared_templates(): void
    {
        (new MessageTemplateSeeder())->run();
        [$sale, $buyer, $landlord] = $this->makeSale();
        Notification::fake();

        app(SaleService::class)->recordPayment($sale->installments()->orderBy('installment_number')->first(), 2000);

        $buyerSms = '';
        $landlordSms = '';
        Notification::assertSentTo($buyer, BuyerInvoicePaymentConfirmation::class, function ($n) use ($buyer, &$buyerSms) {
            $buyerSms = $n->toCompanySms($buyer);

            return true;
        });
        Notification::assertSentTo($landlord, LandlordSalePaymentConfirmation::class, function ($n) use ($landlord, &$landlordSms) {
            $landlordSms = $n->toCompanySms($landlord);

            return true;
        });

        $this->assertStringContainsString('awamu ya 1 kati ya 4', $buyerSms);
        $this->assertStringContainsString('awamu 3 zimebaki', $buyerSms);
        $this->assertStringContainsString('Asha Buyer', $landlordSms);
        $this->assertStringContainsString('Tsh 6,000', $landlordSms);
        $this->assertStringNotContainsString('{', $buyerSms.$landlordSms);
    }
}
