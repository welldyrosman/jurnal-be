<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('yearmonth', function ($attribute, $value) {
            return preg_match('/^(19|20)\d{2}(0[1-9]|1[0-2])$/', $value);
        }, 'Format :attribute harus YYYYMM dan bulan valid.');
        Validator::extend('yearmonth_after_or_equal', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();

            $otherField = $parameters[0] ?? null;
            if (!$otherField || !isset($data[$otherField])) {
                return true;
            }

            return (int) $value >= (int) $data[$otherField];
        }, ':attribute harus lebih besar atau sama dengan :other.');
    }
}
