<?php

namespace App\Policies;

use App\Models\Expense;

class ExpensePolicy
{
    use StaffPolicyRules;
}
