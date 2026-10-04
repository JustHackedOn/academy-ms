<?php

namespace App\Policies;

use App\Models\Student;

class StudentPolicy
{
    use StaffPolicyRules;
}
