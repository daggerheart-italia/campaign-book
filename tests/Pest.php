<?php

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

// Il server dei browser test gira nello stesso processo e riusa l'app: senza
// questo reset sessione e utente autenticato "trapelano" da un browser all'altro.
pest()->beforeEach(function () {
    config(['session.driver' => 'file']);

    Event::listen(RequestHandled::class, function () {
        Auth::forgetGuards();
        app('session')->forgetDrivers();
        app()->forgetInstance('session.store');
    });
})->in('Browser');

// Il sync delta fa polling ogni 5s: le asserzioni devono attendere più di un ciclo
pest()->browser()->timeout(15_000);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of expectations methods that you can use
| to assert different values.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every test file. Here you can also expose helpers
| as global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
