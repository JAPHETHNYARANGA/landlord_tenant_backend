<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Tenant;
use App\Models\Landlord;
use App\Models\Admin;
use App\Models\ServiceProvider;

class UniquePhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Tenant::where('phone_number', $value)->exists()
            || Landlord::where('phone_number', $value)->exists()
            || Admin::where('phone_number', $value)->exists()
            || ServiceProvider::where('phone_number', $value)->exists();

        if ($exists) {
            $fail('The phone number has already been taken.');
        }
    }
}
