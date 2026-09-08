<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductUnit;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The checkout is where money is decided, so these tests are less about the
 * happy path and more about what happens when the browser lies.
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $overrides = []): Product
    {
        // array_merge, not "+": the union operator keeps the left-hand key on a
        // collision, which would silently discard every override.
        return Product::factory()->create(array_merge([
            'name' => 'Alface Crespa',
            'price' => 10.00,
            'unit' => ProductUnit::Unit,
            'stock' => 20,
            'track_stock' => true,
            'is_active' => true,
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function customerPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Maria Aparecida Silva',
            'customer_email' => 'maria@example.com',
            'customer_phone' => '(11) 98888-7777',
            'zip_code' => '01001-000',
            'state' => 'SP',
            'city' => 'São Paulo',
            'district' => 'Centro',
            'street' => 'Praça da Sé',
            'number' => '120',
            'terms' => '1',
        ], $overrides);
    }

    private function fakeGateway(): void
    {
        Http::fake([
            '*/checkout/preferences' => Http::response([
                'id' => 'pref-123',
                'init_point' => 'https://mercadopago.com/checkout/pref-123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/pref-123',
            ]),
        ]);
    }

    #[Test]
    public function it_prices_the_order_from_the_database_and_ignores_anything_the_form_posts(): void
    {
        $this->fakeGateway();
        $product = $this->product(['price' => 10.00]);

        $this->post(route('checkout.start'), [
            'product_id' => $product->id,
            // 6 x R$ 10,00 = R$ 60,00: acima do pedido mínimo de R$ 50,00,
            // que é conferido em CreateOrder junto com o preço.
            'quantity' => 6,
            // A tampered form trying to set its own price.
            'price' => 0.01,
            'unit_price' => 0.01,
            'total' => 0.03,
        ])->assertRedirect(route('checkout.show'));

        $this->post(route('orders.store'), $this->customerPayload([
            'price' => 0.01,
            'total' => 0.03,
            'subtotal' => 0.03,
        ]))->assertRedirect();

        $order = Order::query()->with('items')->firstOrFail();

        $this->assertSame('60.00', $order->total);
        $this->assertSame('60.00', $order->subtotal);
        $this->assertSame('10.00', $order->items->first()->unit_price);
        $this->assertSame('6.000', $order->items->first()->quantity);
    }

    #[Test]
    public function the_checkout_page_renders_the_form_and_a_live_summary(): void
    {
        $product = $this->product(['price' => 10.00]);

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 6]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Alface Crespa')
            ->assertSee('Endereço de entrega')
            ->assertSee('R$ 60,00')
            ->assertSee('name="customer_name"', false)
            ->assertSee('name="zip_code"', false)
            // Never indexed, and never trusted with a price field.
            ->assertSee('noindex', false)
            ->assertDontSee('name="price"', false);
    }

    #[Test]
    public function validation_messages_are_shown_in_portuguese(): void
    {
        $product = $this->product();
        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5]);

        $this->post(route('orders.store'), [])
            ->assertSessionHasErrors('customer_name');

        $this->assertSame(
            'O campo nome completo é obrigatório.',
            session('errors')->first('customer_name'),
        );
    }

    #[Test]
    public function it_starts_every_order_pending_and_unpaid(): void
    {
        $this->fakeGateway();
        $product = $this->product();

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5]);
        $this->post(route('orders.store'), $this->customerPayload());

        $order = Order::query()->firstOrFail();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertNull($order->paid_at);
    }

    #[Test]
    public function it_refuses_a_quantity_larger_than_the_stock(): void
    {
        $product = $this->product(['stock' => 2]);

        $this->from(route('products.show', $product))
            ->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('error');

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function it_refuses_to_sell_an_inactive_product(): void
    {
        $product = $this->product(['is_active' => false]);

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5])
            ->assertSessionHasErrors('product_id');

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function it_sends_the_customer_back_to_the_catalog_when_the_basket_expired(): void
    {
        $this->post(route('orders.store'), $this->customerPayload())
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error');
    }

    #[Test]
    public function it_validates_the_customer_and_address_fields(): void
    {
        $product = $this->product();
        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5]);

        $this->post(route('orders.store'), [
            'customer_name' => 'Maria',          // no surname
            'customer_email' => 'not-an-email',
            'customer_phone' => '123',            // too short
            'zip_code' => '1',                    // not 8 digits
            'state' => 'ZZ',                      // not a Brazilian state
        ])->assertSessionHasErrors([
            'customer_name', 'customer_email', 'customer_phone',
            'zip_code', 'state', 'city', 'district', 'street', 'number', 'terms',
        ]);

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function it_stores_the_delivery_address_with_the_order(): void
    {
        $this->fakeGateway();
        $product = $this->product();

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5]);
        $this->post(route('orders.store'), $this->customerPayload());

        $order = Order::query()->with('address')->firstOrFail();

        $this->assertNotNull($order->address);
        $this->assertSame('01001-000', $order->address->zip_code);
        $this->assertSame('SP', $order->address->state);
        $this->assertSame('120', $order->address->number);
        // Accents must survive normalisation intact.
        $this->assertSame('São Paulo', $order->address->city);
        $this->assertSame('Praça da Sé', $order->address->street);
    }

    #[Test]
    public function it_repairs_malformed_encoding_instead_of_blanking_the_field(): void
    {
        $this->fakeGateway();
        $product = $this->product();

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5]);

        // A client that posts Latin-1 instead of UTF-8. Left unhandled, the
        // /u regex returns null and the address silently disappears.
        $this->post(route('orders.store'), $this->customerPayload([
            'city' => "S\xE3o Paulo",
            'street' => "Pra\xE7a da S\xE9",
        ]))->assertSessionHasNoErrors();

        $address = Order::query()->with('address')->firstOrFail()->address;

        $this->assertSame('São Paulo', $address->city);
        $this->assertSame('Praça da Sé', $address->street);
    }

    #[Test]
    public function it_snapshots_the_product_so_later_catalog_edits_do_not_rewrite_the_order(): void
    {
        $this->fakeGateway();
        $product = $this->product(['price' => 10.00]);

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 6]);
        $this->post(route('orders.store'), $this->customerPayload());

        $product->update(['name' => 'Alface Americana', 'price' => 99.00]);

        $item = Order::query()->with('items')->firstOrFail()->items->first();

        $this->assertSame('Alface Crespa', $item->product_name);
        $this->assertSame('10.00', $item->unit_price);
        $this->assertSame('60.00', $item->subtotal);
    }

    #[Test]
    public function it_hands_the_customer_to_the_gateway_checkout_url(): void
    {
        $this->fakeGateway();
        $product = $this->product();

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5]);

        // Test credentials, so the sandbox init point is the correct target.
        $this->post(route('orders.store'), $this->customerPayload())
            ->assertRedirect('https://sandbox.mercadopago.com/checkout/pref-123');

        $this->assertSame('pref-123', Order::query()->firstOrFail()->gateway_preference_id);
    }

    #[Test]
    public function it_tells_the_gateway_to_offer_only_pix_and_card(): void
    {
        $this->fakeGateway();
        $product = $this->product();

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5]);
        $this->post(route('orders.store'), $this->customerPayload());

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            if (! str_contains($request->url(), '/checkout/preferences')) {
                return false;
            }

            $excluded = array_column($request['payment_methods']['excluded_payment_types'] ?? [], 'id');

            // A loja aceita só Pix e cartão de crédito, e a lista do Checkout
            // Pro é por exclusão — então tudo o que não for esses dois precisa
            // estar aqui. Boleto e caixa eletrônico levam dias para compensar;
            // débito, pré-pago e saldo Mercado Pago simplesmente não são
            // oferecidos. A página promete dois meios, e o gateway obedece.
            foreach (['ticket', 'atm', 'debit_card', 'prepaid_card', 'account_money'] as $type) {
                if (! in_array($type, $excluded, true)) {
                    return false;
                }
            }

            // E o que sobra tem de continuar disponível: bank_transfer é o
            // payment_type do Pix, e sem credit_card não sobraria nada.
            return ! in_array('bank_transfer', $excluded, true)
                && ! in_array('credit_card', $excluded, true);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Pedido mínimo
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_refuses_an_order_below_the_minimum(): void
    {
        // 4 × R$ 10,00 = R$ 40,00, abaixo do mínimo de R$ 50,00.
        $product = $this->product(['price' => 10.00]);

        $this->from(route('products.show', $product))
            ->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 4])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('error');

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function the_refusal_says_how_much_is_missing(): void
    {
        $product = $this->product(['price' => 10.00]);

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 4]);

        // Dizer só "abaixo do mínimo" obrigaria o cliente a descobrir o número
        // sozinho; a mensagem carrega o valor que falta.
        $this->assertStringContainsString('R$ 10,00', (string) session('error'));
        $this->assertStringContainsString('R$ 50,00', (string) session('error'));
    }

    #[Test]
    public function an_order_exactly_at_the_minimum_goes_through(): void
    {
        $this->fakeGateway();
        $product = $this->product(['price' => 10.00]);

        // Exatamente R$ 50,00. A comparação é ">=", e o arredondamento do
        // subtotal não pode empurrar a fronteira para fora.
        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5])
            ->assertRedirect(route('checkout.show'));

        $this->post(route('orders.store'), $this->customerPayload());

        $this->assertSame('50.00', Order::query()->firstOrFail()->total);
    }

    #[Test]
    public function the_minimum_is_enforced_again_when_the_order_is_created(): void
    {
        $this->fakeGateway();
        $product = $this->product(['price' => 20.00, 'stock' => 50]);

        // 3 × R$ 20,00 = R$ 60,00: passa na página do produto.
        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 3])
            ->assertRedirect(route('checkout.show'));

        // O preço cai enquanto o cliente preenche o formulário, e o mesmo
        // carrinho passa a valer R$ 30,00. A cesta guarda produto e quantidade,
        // nunca o preço, então só CreateOrder enxerga o novo subtotal.
        $product->update(['price' => 10.00]);

        $this->post(route('orders.store'), $this->customerPayload())
            ->assertSessionHas('error');

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function the_checkout_page_bounces_back_when_stock_drops_below_the_minimum(): void
    {
        $product = $this->product(['price' => 10.00, 'stock' => 20]);

        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 8]);

        // A cesta é aparada pelo estoque a cada render. Com 3 unidades restando,
        // o pedido cai para R$ 30,00 e não há como concluí-lo.
        $product->update(['stock' => 3]);

        $this->get(route('checkout.show'))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('error');
    }

    #[Test]
    public function the_product_page_opens_at_the_smallest_quantity_that_reaches_the_minimum(): void
    {
        // R$ 4,50 o maço: são 12 maços (R$ 54,00) para passar dos R$ 50,00.
        $product = $this->product([
            'name' => 'Rúcula',
            'price' => 4.50,
            'unit' => ProductUnit::Bunch,
            'stock' => 40,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('value="12"', false)
            ->assertSee('Pedido mínimo');
    }

    #[Test]
    public function a_product_whose_whole_stock_cannot_reach_the_minimum_is_not_offered(): void
    {
        // 3 × R$ 4,50 = R$ 13,50: nem comprando tudo o pedido fecha.
        $product = $this->product(['price' => 4.50, 'stock' => 3]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Estoque insuficiente para o pedido mínimo')
            // Oferecer o botão só levaria a um erro garantido.
            ->assertDontSee('Fazer pedido');
    }

    #[Test]
    public function it_still_saves_the_order_when_the_gateway_is_unreachable(): void
    {
        Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);

        $product = $this->product();
        $this->post(route('checkout.start'), ['product_id' => $product->id, 'quantity' => 5]);

        $response = $this->post(route('orders.store'), $this->customerPayload());

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order))->assertSessionHas('warning');
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
    }
}
