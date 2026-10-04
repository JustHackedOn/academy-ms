<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Expense;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;
use App\Services\FeePlanService;
use App\Services\PaymentAllocationService;
use App\Services\PhoneFormatter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        // 1. Academy Settings
        Setting::set('academy_name', 'FutureTech Academy Lahore');
        Setting::set('currency', 'PKR');
        Setting::set('whatsapp_template', "Assalam-o-Alaikum {name},\nThis is a gentle reminder from {academy} that your installment of {currency} {amount_due} for {batch} was due on {due_date}. Please clear your dues at the earliest. JazakAllah!");

        // 2. Batches
        $batch1 = Batch::updateOrCreate(
            ['code' => 'FSWD-01'],
            [
                'name' => 'Full-Stack Web Development',
                'default_fee' => 40000,
                'duration_months' => 4,
                'start_date' => now()->subMonths(2)->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->subMonths(2)->startOfMonth()->addMonths(4)->format('Y-m-d'),
                'status' => 'running',
                'notes' => 'Evening batch (6 PM to 8 PM). Laravel + Vue/React focus.',
            ]
        );

        $batch2 = Batch::updateOrCreate(
            ['code' => 'UIUX-02'],
            [
                'name' => 'UI/UX & Product Design',
                'default_fee' => 32000,
                'duration_months' => 4,
                'start_date' => now()->subMonths(1)->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->subMonths(1)->startOfMonth()->addMonths(4)->format('Y-m-d'),
                'status' => 'running',
                'notes' => 'Weekend batch (Figma, Design Systems, Bento UI).',
            ]
        );

        $batch3 = Batch::updateOrCreate(
            ['code' => 'DSPY-03'],
            [
                'name' => 'Python & Data Science Bootcamp',
                'default_fee' => 45000,
                'duration_months' => 4,
                'start_date' => now()->addDays(15)->format('Y-m-d'),
                'end_date' => now()->addDays(15)->addMonths(4)->format('Y-m-d'),
                'status' => 'upcoming',
                'notes' => 'Admissions in progress. Early bird discount active.',
            ]
        );

        // 3. Students Data List (~30 students)
        $studentsList = [
            // Batch 1 Students (Started 2 months ago)
            ['batch' => $batch1, 'name' => 'Hamza Tariq', 'phone' => '03001234567', 'type' => 'installments', 'fee' => 40000, 'disc' => 0, 'paid_months' => 2, 'join_offset' => -60],
            ['batch' => $batch1, 'name' => 'Ayesha Siddiqui', 'phone' => '03219876543', 'type' => 'full', 'fee' => 40000, 'disc' => 5000, 'paid_months' => 1, 'join_offset' => -60],
            ['batch' => $batch1, 'name' => 'Bilal Ahmed', 'phone' => '03335551234', 'type' => 'installments', 'fee' => 40000, 'disc' => 0, 'paid_months' => 3, 'join_offset' => -60],
            ['batch' => $batch1, 'name' => 'Fatima Noor', 'phone' => '03456789012', 'type' => 'installments', 'fee' => 40000, 'disc' => 4000, 'paid_months' => 1, 'join_offset' => -55], // Overdue
            ['batch' => $batch1, 'name' => 'Zainab Bibi', 'phone' => '03014443322', 'type' => 'installments', 'fee' => 40000, 'disc' => 0, 'paid_months' => 0, 'join_offset' => -50], // Defaulter
            ['batch' => $batch1, 'name' => 'Muhammad Usman', 'phone' => '03127778899', 'type' => 'full', 'fee' => 40000, 'disc' => 2000, 'paid_months' => 1, 'join_offset' => -45],
            ['batch' => $batch1, 'name' => 'Saad Malik', 'phone' => '03223334455', 'type' => 'installments', 'fee' => 40000, 'disc' => 0, 'paid_months' => 2, 'join_offset' => -40],
            ['batch' => $batch1, 'name' => 'Omer Farooq', 'phone' => '03348889900', 'type' => 'installments', 'fee' => 40000, 'disc' => 8000, 'paid_months' => 1, 'join_offset' => -35],
            ['batch' => $batch1, 'name' => 'Khadija Rehman', 'phone' => '03021112233', 'type' => 'installments', 'fee' => 40000, 'disc' => 0, 'paid_months' => 2, 'join_offset' => -30],
            ['batch' => $batch1, 'name' => 'Hassan Raza', 'phone' => '03156667788', 'type' => 'installments', 'fee' => 40000, 'disc' => 0, 'paid_months' => 0, 'join_offset' => -30], // Defaulter

            // Batch 2 Students (Started 1 month ago)
            ['batch' => $batch2, 'name' => 'Ali Imran', 'phone' => '03009998877', 'type' => 'installments', 'fee' => 32000, 'disc' => 0, 'paid_months' => 1, 'join_offset' => -30],
            ['batch' => $batch2, 'name' => 'Mariam Khan', 'phone' => '03214445566', 'type' => 'full', 'fee' => 32000, 'disc' => 4000, 'paid_months' => 1, 'join_offset' => -30],
            ['batch' => $batch2, 'name' => 'Zubair Chaudhry', 'phone' => '03332221100', 'type' => 'installments', 'fee' => 32000, 'disc' => 2000, 'paid_months' => 1, 'join_offset' => -25],
            ['batch' => $batch2, 'name' => 'Sana Javed', 'phone' => '03451113355', 'type' => 'installments', 'fee' => 32000, 'disc' => 0, 'paid_months' => 0, 'join_offset' => -25], // Overdue
            ['batch' => $batch2, 'name' => 'Abdullah Shah', 'phone' => '03037776655', 'type' => 'installments', 'fee' => 32000, 'disc' => 0, 'paid_months' => 1, 'join_offset' => -20],
            ['batch' => $batch2, 'name' => 'Nimra Aslam', 'phone' => '03138884422', 'type' => 'installments', 'fee' => 32000, 'disc' => 2000, 'paid_months' => 1, 'join_offset' => -18],
            ['batch' => $batch2, 'name' => 'Daniyal Qureshi', 'phone' => '03245556677', 'type' => 'installments', 'fee' => 32000, 'disc' => 0, 'paid_months' => 0, 'join_offset' => -15],
            ['batch' => $batch2, 'name' => 'Hira Naveed', 'phone' => '03359990011', 'type' => 'full', 'fee' => 32000, 'disc' => 3000, 'paid_months' => 1, 'join_offset' => -14],
            ['batch' => $batch2, 'name' => 'Arslan Butt', 'phone' => '03043332211', 'type' => 'installments', 'fee' => 32000, 'disc' => 0, 'paid_months' => 1, 'join_offset' => -10],
            ['batch' => $batch2, 'name' => 'Rabia Basri', 'phone' => '03164448899', 'type' => 'installments', 'fee' => 32000, 'disc' => 0, 'paid_months' => 1, 'join_offset' => -8],

            // Batch 3 Students (Upcoming / newly enrolled)
            ['batch' => $batch3, 'name' => 'Talha Mehmood', 'phone' => '03058887766', 'type' => 'installments', 'fee' => 45000, 'disc' => 5000, 'paid_months' => 1, 'join_offset' => -5],
            ['batch' => $batch3, 'name' => 'Waqas Bhatti', 'phone' => '03201119988', 'type' => 'installments', 'fee' => 45000, 'disc' => 0, 'paid_months' => 0, 'join_offset' => -4],
            ['batch' => $batch3, 'name' => 'Anam Zahra', 'phone' => '03312224466', 'type' => 'full', 'fee' => 45000, 'disc' => 5000, 'paid_months' => 1, 'join_offset' => -3],
            ['batch' => $batch3, 'name' => 'Noman Ali', 'phone' => '03463335577', 'type' => 'installments', 'fee' => 45000, 'disc' => 0, 'paid_months' => 0, 'join_offset' => -2],
            ['batch' => $batch3, 'name' => 'Iqra Mumtaz', 'phone' => '03067779911', 'type' => 'installments', 'fee' => 45000, 'disc' => 3000, 'paid_months' => 0, 'join_offset' => -1],
            ['batch' => $batch3, 'name' => 'Shahzaib Akram', 'phone' => '03175553311', 'type' => 'installments', 'fee' => 45000, 'disc' => 0, 'paid_months' => 0, 'join_offset' => 0],
            ['batch' => $batch3, 'name' => 'Laiba Yousaf', 'phone' => '03238882200', 'type' => 'installments', 'fee' => 45000, 'disc' => 0, 'paid_months' => 0, 'join_offset' => 0],
            ['batch' => $batch3, 'name' => 'Fahad Mustafa', 'phone' => '03361114477', 'type' => 'installments', 'fee' => 45000, 'disc' => 5000, 'paid_months' => 1, 'join_offset' => 0],
        ];

        $paymentMethods = ['cash', 'jazzcash', 'easypaisa', 'bank'];

        foreach ($studentsList as $data) {
            $joinDate = now()->addDays($data['join_offset'])->format('Y-m-d');
            $netFee = $data['fee'] - $data['disc'];

            $student = Student::create([
                'batch_id' => $data['batch']->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => strtolower(str_replace(' ', '.', $data['name'])) . '@gmail.com',
                'fee_type' => $data['type'],
                'total_fee' => $data['fee'],
                'discount' => $data['disc'],
                'net_fee' => $netFee,
                'join_date' => $joinDate,
                'status' => 'active',
                'notes' => 'Enrolled via social media ad campaign.',
            ]);

            // Generate Installments
            $count = $data['type'] === 'full' ? 1 : $data['batch']->duration_months;
            $schedule = FeePlanService::calculateSchedule($netFee, $data['type'], $joinDate, $count);
            FeePlanService::createInstallments($student, $schedule);

            // Record initial payments based on 'paid_months'
            $installments = $student->installments()->orderBy('number')->get();
            for ($p = 0; $p < $data['paid_months']; $p++) {
                if (isset($installments[$p])) {
                    $inst = $installments[$p];
                    $method = $paymentMethods[array_rand($paymentMethods)];
                    $paidDate = Carbon::parse($inst->due_date)->subDays(rand(1, 5))->format('Y-m-d');

                    PaymentAllocationService::recordPayment(
                        student: $student,
                        amount: (float)$inst->amount,
                        paidOn: $paidDate,
                        method: $method,
                        reference: strtoupper($method) . '-' . rand(10000, 99999),
                        note: 'Monthly installment clearance',
                        specificInstallmentId: $inst->id
                    );
                }
            }

            // Add student notes for overdue students
            if ($student->fresh()->fee_status === 'overdue') {
                StudentNote::create([
                    'student_id' => $student->id,
                    'user_id' => $admin?->id,
                    'type' => 'call',
                    'body' => 'Called regarding overdue installment. Student promised to pay by coming Monday.',
                    'promise_date' => now()->addDays(3)->format('Y-m-d'),
                ]);
            }
        }

        // 4. Seed Realistic Expenses
        $expenses = [
            ['title' => 'Office High-Speed Internet', 'category' => 'Utilities', 'amount' => 12500, 'offset' => -25],
            ['title' => 'Zoom Pro & Cloud Recording', 'category' => 'Software', 'amount' => 8400, 'offset' => -20],
            ['title' => 'Meta/Facebook Ad Spend', 'category' => 'Marketing', 'amount' => 35000, 'offset' => -15],
            ['title' => 'Electricity Bill', 'category' => 'Utilities', 'amount' => 22000, 'offset' => -10],
            ['title' => 'Lead Instructor Honorarium', 'category' => 'Salaries', 'amount' => 80000, 'offset' => -5],
            ['title' => 'Printing & Course Stationery', 'category' => 'Miscellaneous', 'amount' => 4500, 'offset' => -2],
        ];

        foreach ($expenses as $exp) {
            Expense::create([
                'title' => $exp['title'],
                'category' => $exp['category'],
                'amount' => $exp['amount'],
                'spent_on' => now()->addDays($exp['offset'])->format('Y-m-d'),
                'note' => 'Approved monthly operation expense',
                'user_id' => $admin?->id,
            ]);
        }
    }
}
