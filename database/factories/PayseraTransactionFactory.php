<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Starlabs\LaravelPaysera\Enums\PaymentStatus;
use Starlabs\LaravelPaysera\Models\PayseraTransaction;

class PayseraTransactionFactory extends Factory
{
    protected $model = PayseraTransaction::class;

    public function definition(): array
    {
        return [
            'order_id' => 'ORD-'.$this->faker->unique()->randomNumber(5),
            'order_description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, 5, 500),
            'currency' => 'EUR',
            'status' => PaymentStatus::REGISTERED,
            'paysera_transaction_id' => $this->faker->randomNumber(8),
            'payer_email' => $this->faker->safeEmail(),
            'payment_method' => $this->faker->randomElement(['visa', 'mastercard', 'seb', 'swedbank']),
            'country' => $this->faker->randomElement(['LT', 'EE', 'LV', 'PL', 'DE']),
            'language' => $this->faker->randomElement(['LIT', 'ENG', 'RUS']),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => PaymentStatus::COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => PaymentStatus::CANCELLED]);
    }

    public function refused(): static
    {
        return $this->state(['status' => PaymentStatus::REFUSED]);
    }

    public function testMode(): static
    {
        return $this->state(['country' => 'LT', 'language' => 'LIT']);
    }
}
