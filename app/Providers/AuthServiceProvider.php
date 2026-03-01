<?php

namespace App\Providers;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Payment;
use App\Policies\ColocationPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Colocation::class => ColocationPolicy::class,
        Expense::class => ExpensePolicy::class,
        Payment::class => PaymentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
