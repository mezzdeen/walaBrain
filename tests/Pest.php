<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
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

/*
|--------------------------------------------------------------------------
| Modules
|--------------------------------------------------------------------------
|
| Pest boots exactly one Pest.php, this one, so a module's own bindings and
| helpers have to be pulled in from here. Found by scanning rather than listed,
| the same way the application finds each module's service provider: a module
| with tests brings its own file, and removing the module removes it with no
| edit here.
|
*/

foreach (glob(__DIR__.'/../app/Modules/*/Tests/Pest.php') ?: [] as $module) {
    require_once $module;
}

/*
|--------------------------------------------------------------------------
| Browser Testing
|--------------------------------------------------------------------------
|
| Browser tests boot the compiled front-end in a real browser, so they wait
| a little longer than the default to let Inertia hydrate and the full page
| reloads that follow a locale change settle.
|
*/

pest()->browser()->timeout(10000);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
