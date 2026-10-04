<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Student;

class WhatsAppLinkBuilder
{
    public const DEFAULT_TEMPLATE_EN = "Dear {name}, this is a gentle reminder from {academy} regarding your fee of {currency} {amount_due} for {batch}, which was due on {due_date}. Kindly arrange the payment at your earliest convenience. Thank you!";
    public const DEFAULT_TEMPLATE_URDU = "Assalam-o-Alaikum {name}, {academy} ki taraf se reminder hai ke aap ki {batch} ki fee {currency} {amount_due} ki due date {due_date} thi. Baraye meherbani jald az jald payment clear kar dein. Shukriya!";

    /**
     * Build the wa.me URL for a student.
     */
    public static function build(Student $student, ?string $template = null, ?float $customAmount = null, ?string $customDueDate = null): string
    {
        $phone = PhoneFormatter::normalize($student->phone);
        if (empty($phone)) {
            return '#';
        }

        $academyName = academy_name();
        $batchName = $student->batch ? $student->batch->name : 'Course';
        $amountDue = $customAmount !== null ? $customAmount : ($student->overdue_amount > 0 ? $student->overdue_amount : $student->remaining_balance);
        $dueDate = $customDueDate ?? ($student->next_due_date ?? now()->format('Y-m-d'));

        if (!$template) {
            $template = Setting::get(
                'whatsapp_template',
                self::DEFAULT_TEMPLATE_EN
            );
        }

        $formattedAmount = number_format($amountDue, 0);

        $replacements = [
            '{name}' => $student->name,
            '{batch}' => $batchName,
            '{currency}' => currency(),
            '{amount_due}' => $formattedAmount,
            '{due_date}' => $dueDate,
            '{academy}' => $academyName,
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $template);

        return "https://wa.me/{$phone}?text=" . rawurlencode($message);
    }
}
