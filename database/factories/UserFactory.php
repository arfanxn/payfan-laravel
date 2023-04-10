<?php

namespace Database\Factories;

use App\Helpers\StrHelper;
use Faker\Factory as WithFaker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */

    public function definition()
    {
        $images =  \Illuminate\Support\Facades\Storage::allFiles("public/images/user/profile_pict");
        $image = $images[rand(0, count($images) - 1)];
        $useProfilePict = rand(0, 1);
        $ifNotUseProfilePict = "#"  . StrHelper::random(6, "1234567890ABCDEF");

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' =>  bcrypt(StrHelper::random(50)),
            // '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            "profile_pict" => $useProfilePict ? $image : $ifNotUseProfilePict,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
