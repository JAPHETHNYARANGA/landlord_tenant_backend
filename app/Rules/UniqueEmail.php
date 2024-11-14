<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Tenant;
use App\Models\Landlord;
use App\Models\Admin;

class UniqueEmail implements ValidationRule
{
    protected $ignoreId;
    protected $models;

    /**
     * Create a new rule instance.
     *
     * @param  array  $models  Array of models to check for uniqueness
     * @param  int|null  $ignoreId  ID to ignore for update validation
     */
    public function __construct(array $models, $ignoreId = null)
    {
        $this->models = $models;
        $this->ignoreId = $ignoreId;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute  The name of the field being validated
     * @param  mixed  $value  The value of the field being validated
     * @param  Closure  $fail  Callback to invoke if validation fails
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->models as $model) {
            // Check if the email exists in the model's table
            $query = $model::where($attribute, $value);

            // If an ID is provided, exclude the record with that ID
            if ($this->ignoreId) {
                $query->where('id', '!=', $this->ignoreId);
            }

            if ($query->exists()) {
                $fail('The email address is already taken.');
                return;
            }
        }
    }
}
