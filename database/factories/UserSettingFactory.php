<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "two_factor_auth" => rand(0, 1),
            "security_question" => $this->withFaker()->sentence(),
            "security_answer" => $this->withFaker()->sentence(),
            "payment_notification" => true,
            "request_notification" => rand(0, 1),
            "receive_notification" => rand(0, 1),
        ];
    }
}
