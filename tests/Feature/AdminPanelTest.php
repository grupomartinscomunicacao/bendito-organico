<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductUnit;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function operator(): User
    {
        return User::factory()->create(['role' => UserRole::Operator]);
    }

    private function orderWithEverything(): Order
    {
        $order = Order::factory()->paid()->create(['subtotal' => 50, 'total' => 50]);
        $product = Product::factory()->create(['price' => 25, 'stock' => 10]);

        OrderItem::factory()->forProduct($product, 2)->create(['order_id' => $order->id]);
        Address::factory()->create(['order_id' => $order->id]);
        Payment::factory()->create(['order_id' => $order->id, 'amount' => 50]);

        return $order;
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function every_admin_route_is_closed_to_guests(): void
    {
        $order = $this->orderWithEverything();
        $product = Product::factory()->create();

        $routes = [
            route('admin.dashboard'),
            route('admin.products.index'),
            route('admin.products.create'),
            route('admin.products.edit', $product),
            route('admin.orders.index'),
            route('admin.orders.show', $order),
            route('admin.orders.print', $order),
            route('admin.payments.index'),
            route('admin.users.index'),
            route('admin.settings'),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
    }

    #[Test]
    public function an_operator_can_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'operador@bendito.test', 'password' => 'senha-secreta-123']);

        $this->post(route('admin.login.store'), [
            'email' => 'operador@bendito.test',
            'password' => 'senha-secreta-123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
    }

    #[Test]
    public function a_deactivated_account_cannot_sign_in(): void
    {
        User::factory()->inactive()->create([
            'email' => 'desativado@bendito.test',
            'password' => 'senha-secreta-123',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'desativado@bendito.test',
            'password' => 'senha-secreta-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function a_wrong_password_does_not_reveal_whether_the_account_exists(): void
    {
        User::factory()->create(['email' => 'existe@bendito.test', 'password' => 'senha-secreta-123']);

        $existing = $this->post(route('admin.login.store'), [
            'email' => 'existe@bendito.test',
            'password' => 'senha-errada',
        ]);

        $missing = $this->post(route('admin.login.store'), [
            'email' => 'nao-existe@bendito.test',
            'password' => 'senha-errada',
        ]);

        $this->assertSame(
            $existing->getSession()->get('errors')->first('email'),
            $missing->getSession()->get('errors')->first('email'),
        );
    }

    #[Test]
    public function repeated_failed_logins_are_throttled(): void
    {
        User::factory()->create(['email' => 'alvo@bendito.test', 'password' => 'senha-secreta-123']);

        foreach (range(1, 5) as $ignored) {
            $this->post(route('admin.login.store'), [
                'email' => 'alvo@bendito.test',
                'password' => 'errada',
            ]);
        }

        $response = $this->post(route('admin.login.store'), [
            'email' => 'alvo@bendito.test',
            'password' => 'senha-secreta-123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard & pages
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_dashboard_summarises_orders_and_revenue(): void
    {
        $this->orderWithEverything();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Faturamento total')
            ->assertSee('R$ 50,00');
    }

    #[Test]
    public function the_settings_page_never_prints_a_credential(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.settings'));

        $response->assertOk()->assertSee('Configurado');
        $response->assertDontSee(config('mercadopago.access_token'));
        $response->assertDontSee(config('mercadopago.webhook_secret'));
    }

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function an_operator_can_create_a_product_with_a_photo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->operator())
            ->post(route('admin.products.store'), [
                'name' => 'Alface Crespa',
                'short_description' => 'Colhida no dia.',
                'description' => 'Descrição completa do produto.',
                'price' => '5,90',
                'unit' => ProductUnit::Unit->value,
                'stock' => '48',
                'track_stock' => '1',
                'is_active' => '1',
                'image' => UploadedFile::fake()->image('alface.jpg', 800, 800),
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->firstOrFail();

        $this->assertSame('Alface Crespa', $product->name);
        $this->assertSame('alface-crespa', $product->slug);
        // "5,90" must be read as five reais and ninety centavos, not 590.
        $this->assertSame('5.90', $product->price);
        $this->assertNotNull($product->image);

        Storage::disk('public')->assertExists($product->image);
    }

    #[Test]
    public function it_rejects_a_non_image_upload(): void
    {
        Storage::fake('public');

        $this->actingAs($this->operator())
            ->post(route('admin.products.store'), [
                'name' => 'Arquivo Suspeito',
                'price' => '10,00',
                'unit' => ProductUnit::Unit->value,
                'stock' => '1',
                'image' => UploadedFile::fake()->create('exploit.php', 32, 'application/x-php'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Product::query()->count());
    }

    #[Test]
    public function an_operator_can_edit_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'Rúcula', 'price' => 4.50]);

        $this->actingAs($this->operator())
            ->put(route('admin.products.update', $product), [
                'name' => 'Rúcula Selvagem',
                'price' => '6,50',
                'unit' => $product->unit->value,
                'stock' => '30',
                'track_stock' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('Rúcula Selvagem', $product->name);
        $this->assertSame('6.50', $product->price);
    }

    #[Test]
    public function deleting_a_product_is_a_soft_delete_that_keeps_order_history(): void
    {
        $order = $this->orderWithEverything();
        $product = $order->items()->first()->product;

        $this->actingAs($this->operator())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted($product);

        // The order line survives with its snapshot intact.
        $this->assertSame(1, $order->items()->count());
        $this->assertNotNull($order->items()->first()->product_name);
    }

    #[Test]
    public function a_product_can_be_hidden_from_the_storefront_without_being_deleted(): void
    {
        $product = Product::factory()->create(['is_active' => true, 'stock' => 5]);

        $this->actingAs($this->operator())
            ->patch(route('admin.products.toggle', $product))
            ->assertRedirect();

        $this->assertFalse($product->refresh()->is_active);

        // Assert on the link, not the name: the success flash from the toggle
        // legitimately repeats the product name on the next page.
        $this->get(route('products.index'))
            ->assertOk()
            ->assertDontSee(route('products.show', $product));
    }

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_order_list_can_be_filtered(): void
    {
        $paid = Order::factory()->paid()->create(['customer_name' => 'Cliente Pago']);
        $pending = Order::factory()->create(['customer_name' => 'Cliente Pendente']);
        OrderItem::factory()->create(['order_id' => $paid->id]);
        OrderItem::factory()->create(['order_id' => $pending->id]);

        $this->actingAs($this->operator())
            ->get(route('admin.orders.index', ['pagamento' => PaymentStatus::Approved->value]))
            ->assertOk()
            ->assertSee('Cliente Pago')
            ->assertDontSee('Cliente Pendente');
    }

    #[Test]
    public function an_operator_can_advance_an_order_through_the_workflow(): void
    {
        $order = $this->orderWithEverything(); // paid → Confirmed

        $this->actingAs($this->operator())
            ->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Preparing->value])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(OrderStatus::Preparing, $order->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_status_jump_the_workflow_does_not_allow(): void
    {
        $order = $this->orderWithEverything(); // Confirmed

        $this->actingAs($this->operator())
            ->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Delivered->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
    }

    #[Test]
    public function cancelling_a_paid_order_puts_the_stock_back(): void
    {
        $order = $this->orderWithEverything();
        $product = $order->items()->first()->product;

        $this->assertSame('10.000', $product->stock);

        $this->actingAs($this->operator())
            ->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Cancelled->value]);

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame('12.000', $product->refresh()->stock);
    }

    #[Test]
    public function the_printable_card_renders_without_any_admin_chrome(): void
    {
        $order = $this->orderWithEverything();

        $response = $this->actingAs($this->operator())->get(route('admin.orders.print', $order));

        $response->assertOk()
            ->assertSee($order->public_number)
            ->assertSee($order->customer_name)
            ->assertSee('Itens para separação')
            ->assertSee('Obrigado por escolher')
            // The official logo is on the card…
            ->assertSee('images/brand/logo-light.png')
            // …and none of the panel chrome is.
            ->assertDontSee('admin-sidebar__nav', false)
            ->assertDontSee('admin-topbar', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function an_operator_cannot_reach_user_management(): void
    {
        $this->actingAs($this->operator())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_can_manage_users(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Nova Operadora',
                'email' => 'nova@bendito.test',
                'password' => 'senha-forte-123',
                'password_confirmation' => 'senha-forte-123',
                'role' => UserRole::Operator->value,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'nova@bendito.test', 'role' => 'operator']);
    }

    #[Test]
    public function an_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertNotSoftDeleted($admin);
    }

    #[Test]
    public function an_admin_cannot_demote_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => UserRole::Operator->value,
            'is_active' => '0',
        ])->assertRedirect(route('admin.users.index'));

        $admin->refresh();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
    }

    #[Test]
    public function an_operator_cannot_resync_a_payment(): void
    {
        $order = $this->orderWithEverything();
        $payment = $order->payments()->first();

        $this->actingAs($this->operator())
            ->post(route('admin.payments.sync', $payment))
            ->assertForbidden();
    }
}
