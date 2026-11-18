<?php

namespace Tests\Feature;

use App\Models\CustomerCredit;
use App\Models\CustomerPayment;
use App\Models\DailyCut;
use App\Models\Product;
use App\Models\User;
use App\Services\CustomerCreditService;
use App\Services\DailyCutService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_manual_credit_records_money_without_sales_or_stock_movement(): void
    {
        $this->freezeTime();
        $employee = $this->employee();
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($employee)
            ->post(route('pos.abonos.store'), $this->createPayload());

        $response->assertRedirect(route('pos.abonos.index'));

        $credit = CustomerCredit::query()->sole();
        $this->assertSame('Juan Pérez', $credit->customer_name);
        $this->assertSame(15000, $credit->total_cents);
        $this->assertSame(10000, $credit->balance_cents);

        $payment = CustomerPayment::query()->sole();
        $this->assertSame($credit->id, $payment->customer_credit_id);
        $this->assertSame('50.00', $payment->amount);
        $this->assertSame('cash', $payment->method);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('cash_movements', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_amounts_reject_negative_or_more_than_two_decimals(): void
    {
        $service = app(CustomerCreditService::class);

        foreach (['10.999', '1.001', '-5.00', 'abc', ''] as $invalid) {
            try {
                $service->toCents($invalid);
                $this->fail("El monto [{$invalid}] debió ser rechazado.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('amount', $exception->errors());
            }
        }

        $this->assertSame(1099, $service->toCents('10.99'));
        $this->assertSame(1090, $service->toCents('10.9'));
        $this->assertSame('10.99', CustomerCreditService::decimal(1099));

        $this->actingAs($this->employee())
            ->post(route('pos.abonos.store'), $this->createPayload(['total_amount' => '10.999']))
            ->assertSessionHasErrors('total_amount');
    }

    public function test_payment_cannot_exceed_balance_and_settles_exactly(): void
    {
        $this->freezeTime();
        $employee = $this->employee();
        $credit = $this->credit(['total_cents' => 10000, 'balance_cents' => 10000]);
        $service = app(CustomerCreditService::class);

        try {
            $service->pay($credit, $employee, '100.01', 'cash', (string) Str::uuid());
            $this->fail('Un abono mayor al saldo debió ser rechazado.');
        } catch (ValidationException $exception) {
            $this->assertSame(['El abono no puede superar el saldo pendiente.'], $exception->errors()['amount']);
        }

        $this->assertSame(10000, $credit->fresh()->balance_cents);
        $this->assertDatabaseCount('customer_payments', 0);

        $payment = $service->pay($credit, $employee, '100.00', 'transfer', (string) Str::uuid());

        $this->assertSame('100.00', $payment->amount);
        $this->assertSame(0, $credit->fresh()->balance_cents);
        $this->assertTrue($credit->fresh()->isSettled());
    }

    public function test_idempotency_keys_prevent_duplicate_charges(): void
    {
        $this->freezeTime();
        $employee = $this->employee();
        $service = app(CustomerCreditService::class);
        $key = (string) Str::uuid();
        $payload = $this->createPayload(['total_amount' => '100.00', 'initial_payment' => '20.00']);

        $first = $service->create($employee, $payload, $key);
        $second = $service->create($employee, $payload, $key);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('customer_credits', 1);
        $this->assertDatabaseCount('customer_payments', 1);
        $this->assertSame(8000, $first->fresh()->balance_cents);

        $payKey = (string) Str::uuid();
        $payment = $service->pay($first, $employee, '10.00', 'cash', $payKey);
        $repeated = $service->pay($first, $employee, '10.00', 'cash', $payKey);

        $this->assertSame($payment->id, $repeated->id);
        $this->assertDatabaseCount('customer_payments', 2);
        $this->assertSame(7000, $first->fresh()->balance_cents);
    }

    public function test_only_cash_payments_count_toward_the_cash_box(): void
    {
        $this->freezeTime();
        $employee = $this->employee();
        $credit = $this->credit(['total_cents' => 10000, 'balance_cents' => 10000]);
        $service = app(CustomerCreditService::class);

        $service->pay($credit, $employee, '25.00', 'cash', (string) Str::uuid());
        $service->pay($credit, $employee, '50.00', 'card', (string) Str::uuid());
        $service->pay($credit, $employee, '15.00', 'transfer', (string) Str::uuid());

        $totals = app(DailyCutService::class)->build(today(), $employee);

        $this->assertSame(25.0, $totals['customer_payments_cash']);
        $this->assertSame(50.0, $totals['customer_payments_card']);
        $this->assertSame(15.0, $totals['customer_payments_transfer']);
        $this->assertSame(65.0, $totals['customer_payments_non_cash']);
        $this->assertSame(25.0, $totals['cash_box_panel']['customer_payments']);
        $this->assertSame(25.0, $totals['computer_panel']['customer_payments']);
    }

    public function test_cut_page_lists_credit_payments_broken_down_by_method(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create()->assignRole('admin');
        $credit = $this->credit(['total_cents' => 10000, 'balance_cents' => 10000]);
        $service = app(CustomerCreditService::class);

        $service->pay($credit, $admin, '50.00', 'card', (string) Str::uuid());
        $service->pay($credit, $admin, '15.00', 'transfer', (string) Str::uuid());

        $this->actingAs($admin)
            ->get(route('pos.cuts.index'))
            ->assertOk()
            ->assertSee('Abonos con tarjeta de débito/crédito')
            ->assertSee('Abonos por transferencia')
            ->assertSee('$50.00')
            ->assertSee('$15.00');
    }

    public function test_closed_day_blocks_new_credits(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create()->assignRole('admin');

        DailyCut::query()->create([
            'cut_key' => today()->toDateString().'|global',
            'cut_date' => today(),
            'scope' => DailyCut::SCOPE_GLOBAL,
            'version' => 1,
            'totals' => [],
            'generated_by' => $admin->id,
            'generated_at' => now(),
            'closed_at' => now(),
        ]);

        try {
            app(CustomerCreditService::class)->create($this->employee(), $this->createPayload(), (string) Str::uuid());
            $this->fail('Un día cerrado no debe aceptar cuentas nuevas.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('day', $exception->errors());
        }

        $this->assertDatabaseCount('customer_credits', 0);
    }

    public function test_employee_with_credit_payments_cannot_be_deleted(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create()->assignRole('admin');
        $employee = $this->employee();
        $credit = $this->credit();

        $payment = CustomerPayment::query()->create([
            'user_id' => $employee->id,
            'amount' => 25,
            'method' => 'cash',
            'reason' => 'Abono a cartera manual',
            'customer_name' => $credit->customer_name,
            'customer_credit_id' => $credit->id,
            'happened_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $employee))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $employee->id]);
        $this->assertDatabaseHas('customer_payments', ['id' => $payment->id]);
    }

    public function test_employee_can_register_a_payment_through_the_route(): void
    {
        $this->freezeTime();
        $employee = $this->employee();
        $credit = $this->credit(['total_cents' => 5000, 'balance_cents' => 5000]);

        $this->actingAs($employee)
            ->post(route('pos.abonos.payments.store', $credit), [
                'amount' => '20.00',
                'method' => 'cash',
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertRedirect(route('pos.abonos.index'));

        $this->assertSame(3000, $credit->fresh()->balance_cents);
        $this->assertDatabaseHas('customer_payments', [
            'customer_credit_id' => $credit->id,
            'amount' => 20,
            'method' => 'cash',
        ]);
    }

    public function test_admin_and_employee_can_open_the_manual_credit_page(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $employee = $this->employee();

        $this->actingAs($admin)->get(route('pos.abonos.index'))->assertOk();
        $this->actingAs($employee)->get(route('pos.abonos.index'))->assertOk();
    }

    /**
     * @return array<string, string>
     */
    private function createPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Juan Pérez',
            'customer_phone' => '5512345678',
            'description' => '1x Llanta trasera',
            'total_amount' => '150.00',
            'initial_payment' => '50.00',
            'method' => 'cash',
            'idempotency_key' => (string) Str::uuid(),
        ], $overrides);
    }

    private function employee(): User
    {
        return User::factory()->create()->assignRole('empleado');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function credit(array $attributes = []): CustomerCredit
    {
        return CustomerCredit::factory()->create($attributes);
    }
}
