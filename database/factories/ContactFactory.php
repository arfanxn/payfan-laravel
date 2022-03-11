<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = rand(1, 3);
        switch ($status) {
            case 1:
                $status = Contact::STATUS_ADDED;
                break;
            case 2:
                $status = Contact::STATUS_FAVORITED;
                break;
            default:
                $status = Contact::STATUS_BLOCKED;
                break;
        }
        return [
            "status" => $status,
            "total_transaction" => rand(1, 99),
            'last_transaction' => now()->subDay(rand(1, 30))->toDateTimeString(),
            'added_at' => now()->subDays(rand(31, 365))->toDateTimeString()
        ];
    }
}
