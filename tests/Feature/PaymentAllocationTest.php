<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsAcademy;
use Tests\TestCase;

class PaymentAllocationTest extends TestCase
{
    use BuildsAcademy, RefreshDatabase;

    protected function actingUser(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    public function test_payment_allocates_to_the_oldest_unpaid_installment_first(): void
    {
        $this->actingUser();

        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 9000]);
        $this->makeInstallments($student, [3000, 3000, 3000]);

        $this->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 4000,
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $installments = $student->installments()->orderBy('number')->get();

        $this->assertEqualsWithDelta(3000, (float) $installments[0]->paid_amount, 0.01);
        $this->assertEqualsWithDelta(1000, (float) $installments[1]->paid_amount, 0.01);
        $this->assertEqualsWithDelta(0, (float) $installments[2]->paid_amount, 0.01);

        $this->assertSame('paid', $installments[0]->refresh()->status);
        $this->assertSame('partial', $installments[1]->refresh()->status);
        $this->assertSame('pending', $installments[2]->refresh()->status);
    }

    public function test_partial_payment_spans_multiple_installments(): void
    {
        $this->actingUser();

        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 9000]);
        $this->makeInstallments($student, [3000, 3000, 3000]);

        $this->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 7000,
            'paid_on' => now()->toDateString(),
            'method' => 'jazzcash',
        ])->assertSessionHasNoErrors();

        $installments = $student->installments()->orderBy('number')->get();

        $this->assertEqualsWithDelta(3000, (float) $installments[0]->paid_amount, 0.01);
        $this->assertEqualsWithDelta(3000, (float) $installments[1]->paid_amount, 0.01);
        $this->assertEqualsWithDelta(1000, (float) $installments[2]->paid_amount, 0.01);

        $payment = Payment::latest('id')->first();
        $this->assertSame(3, $payment->allocations()->count());
        $this->assertEqualsWithDelta(7000, $payment->allocations()->sum('amount'), 0.01);
    }

    public function test_overpayment_is_blocked(): void
    {
        $this->actingUser();

        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 9000]);
        $this->makeInstallments($student, [9000]);

        $response = $this->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 9500,
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertStringContainsString(
            'Overpayment blocked',
            session('errors')->first('amount')
        );

        $this->assertSame(0, Payment::count());
        $this->assertEqualsWithDelta(0, (float) $student->installments()->sum('paid_amount'), 0.01);
    }

    public function test_payment_cannot_exceed_the_remaining_balance_after_earlier_payments(): void
    {
        $this->actingUser();

        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 5000]);
        $this->makeInstallments($student, [5000]);

        $this->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 3000,
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 2500,
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertEqualsWithDelta(3000, $student->payments()->sum('amount'), 0.01);
    }

    public function test_receipt_numbers_are_unique_and_sequential(): void
    {
        $this->actingUser();

        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 9000]);
        $this->makeInstallments($student, [4500, 4500]);

        foreach ([1000, 2000] as $amount) {
            $this->post(route('payments.store'), [
                'student_id' => $student->id,
                'amount' => $amount,
                'paid_on' => now()->toDateString(),
                'method' => 'easypaisa',
            ])->assertSessionHasNoErrors();
        }

        $receipts = Payment::orderBy('id')->pluck('receipt_no')->all();

        $this->assertCount(2, $receipts);
        $this->assertMatchesRegularExpression('/^RCPT-\d{4}-\d{4}$/', $receipts[0]);
        $this->assertMatchesRegularExpression('/^RCPT-\d{4}-\d{4}$/', $receipts[1]);
        $this->assertNotSame($receipts[0], $receipts[1]);

        $this->assertSame(2, Payment::distinct()->count('receipt_no'));
    }

    public function test_deleting_a_payment_recalculates_installments(): void
    {
        $this->actingUser();

        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 6000]);
        $this->makeInstallments($student, [3000, 3000]);

        $this->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 4000,
            'paid_on' => now()->toDateString(),
            'method' => 'bank',
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(4000, $student->installments()->sum('paid_amount'), 0.01);

        $payment = Payment::latest('id')->firstOrFail();

        $this->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.index'));

        $this->assertEqualsWithDelta(0, $student->installments()->sum('paid_amount'), 0.01);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
        $this->assertSame('pending', $student->installments()->orderBy('number')->first()->refresh()->status);
    }

    public function test_receipt_page_and_pdf_are_reachable_after_payment(): void
    {
        $this->actingUser();

        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 3000]);
        $this->makeInstallments($student, [3000]);

        $this->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 3000,
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $payment = Payment::latest('id')->firstOrFail();

        $this->get(route('payments.receipt', $payment))->assertOk();
        $this->get(route('payments.pdf', $payment))->assertOk();
    }
}
