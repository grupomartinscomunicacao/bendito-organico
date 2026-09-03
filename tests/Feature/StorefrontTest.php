<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Renders every public page. Because strict mode forbids lazy loading in
 * non-production environments, an N+1 in any of these views fails the test.
 */
class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_home_page_renders_with_products_from_the_database(): void
    {
        $product = Product::factory()->create(['name' => 'Alface Crespa', 'is_active' => true, 'stock' => 10]);
        Product::factory()->count(3)->create(['is_active' => true, 'stock' => 5]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Alface Crespa')
            ->assertSee('Orgânicos frescos', false)
            // The official logo, in its knocked-out form for the dark navbar.
            ->assertSee('images/brand/logo-light.png')
            ->assertSee(route('products.show', $product));
    }

    #[Test]
    public function the_home_page_survives_an_empty_catalog(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('próxima colheita', false);
    }

    #[Test]
    public function the_catalog_lists_only_active_products(): void
    {
        Product::factory()->create(['name' => 'Rúcula Fresca', 'is_active' => true]);
        Product::factory()->create(['name' => 'Item Oculto', 'is_active' => false]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Rúcula Fresca')
            ->assertDontSee('Item Oculto');
    }

    #[Test]
    public function the_catalog_can_be_searched(): void
    {
        Product::factory()->create(['name' => 'Tomate Italiano', 'is_active' => true]);
        Product::factory()->create(['name' => 'Beterraba', 'is_active' => true]);

        $this->get(route('products.index', ['q' => 'tomate']))
            ->assertOk()
            ->assertSee('Tomate Italiano')
            ->assertDontSee('Beterraba');
    }

    #[Test]
    public function a_product_page_renders_with_seo_tags(): void
    {
        $product = Product::factory()->create([
            'name' => 'Couve Manteiga',
            'slug' => 'couve-manteiga',
            'short_description' => 'Folha larga e tenra.',
            'is_active' => true,
            'stock' => 8,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Couve Manteiga')
            ->assertSee('Folha larga e tenra.')
            ->assertSee('og:title', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('Fazer pedido');
    }

    #[Test]
    public function a_product_url_uses_its_slug(): void
    {
        $product = Product::factory()->create(['name' => 'Alface Orgânica', 'slug' => 'alface-organica']);

        $this->assertStringEndsWith('/produtos/alface-organica', route('products.show', $product));
    }

    #[Test]
    public function an_inactive_product_page_returns_404(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->get(route('products.show', $product))->assertNotFound();
    }

    #[Test]
    public function the_institutional_pages_render(): void
    {
        $this->get(route('about'))->assertOk()->assertSee('Sobre nós');
        $this->get(route('contact'))->assertOk()->assertSee('Fale com a gente');
    }

    #[Test]
    public function the_contact_form_rejects_a_submission_that_trips_the_honeypot(): void
    {
        $this->post(route('contact.send'), [
            'name' => 'Bot Silva',
            'email' => 'bot@example.com',
            'message' => 'Mensagem automática de spam para o formulário.',
            'website' => 'http://spam.example.com',
        ])->assertSessionHasErrors('website');
    }

    #[Test]
    public function a_customer_can_open_their_order_by_its_public_number(): void
    {
        $order = Order::factory()->create();
        $product = Product::factory()->create(['name' => 'Rúcula Orgânica']);
        OrderItem::factory()->forProduct($product, 2)->create(['order_id' => $order->id]);
        Address::factory()->create(['order_id' => $order->id]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->customer_name)
            ->assertSee('Rúcula Orgânica');
    }

    /**
     * The number is what makes the URL unguessable; printing it on the page
     * only gave the customer something to keep track of.
     *
     * Text, not markup: the number still lives in the URL, and therefore in
     * the canonical tag and the form actions — nowhere anyone reads.
     */
    #[Test]
    public function the_order_page_does_not_show_the_order_number_or_the_tracking_timeline(): void
    {
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        OrderItem::factory()->forProduct($product, 2)->create(['order_id' => $order->id]);
        Address::factory()->create(['order_id' => $order->id]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSeeText($order->public_number)
            ->assertDontSeeText('Acompanhe seu pedido');
    }

    #[Test]
    public function the_order_lookup_asks_for_a_phone_number(): void
    {
        $this->get(route('orders.lookup'))
            ->assertOk()
            ->assertSee('Digite o telefone')
            ->assertSee('name="telefone"', false);
    }

    #[Test]
    public function the_order_lookup_finds_every_order_placed_with_a_phone_however_it_was_typed(): void
    {
        $product = Product::factory()->create();

        $mine = Order::factory()->count(2)->create(['customer_phone' => '77999999999']);
        $someone_else = Order::factory()->create(['customer_phone' => '11988887777']);

        foreach ([...$mine, $someone_else] as $order) {
            OrderItem::factory()->forProduct($product, 1)->create(['order_id' => $order->id]);
        }

        // The masked form the customer actually types.
        $this->post(route('orders.lookup.search'), ['telefone' => '(77) 99999-9999'])
            ->assertRedirect(route('orders.lookup'));

        $response = $this->get(route('orders.lookup'))->assertOk();

        foreach ($mine as $order) {
            $response->assertSee(route('orders.show', $order));
        }

        $response->assertDontSee(route('orders.show', $someone_else));
    }

    #[Test]
    public function the_order_lookup_says_so_when_a_phone_has_no_orders(): void
    {
        Order::factory()->create(['customer_phone' => '11988887777']);

        $this->post(route('orders.lookup.search'), ['telefone' => '77 90000-0000']);

        $this->get(route('orders.lookup'))
            ->assertOk()
            ->assertSee('Nenhum pedido encontrado');
    }

    #[Test]
    public function the_order_lookup_rejects_a_phone_that_is_not_a_phone(): void
    {
        $this->from(route('orders.lookup'))
            ->post(route('orders.lookup.search'), ['telefone' => '123'])
            ->assertRedirect(route('orders.lookup'))
            ->assertSessionHasErrors('telefone');
    }

    #[Test]
    public function an_unknown_url_renders_the_branded_404_page(): void
    {
        $this->get('/uma-pagina-que-nao-existe')
            ->assertNotFound()
            ->assertSee('Página não encontrada');
    }
}
