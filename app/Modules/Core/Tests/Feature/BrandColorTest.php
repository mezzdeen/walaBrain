<?php

use App\Modules\Core\Support\BrandColor;

/*
|--------------------------------------------------------------------------
| Brand colour
|--------------------------------------------------------------------------
|
| The colour maths behind an organization's branding. Contrast is the part
| worth guarding: get it wrong and a light brand colour leaves every button's
| label unreadable, which no test of the save path would catch.
|
*/

test('a dark brand colour is paired with white text', function (string $color) {
    expect(BrandColor::foregroundFor($color))->toBe('#ffffff');
})->with(['#000000', '#dc2626', '#2563eb', '#7c3aed', '#0891b2']);

test('a light brand colour is paired with dark text', function (string $color) {
    expect(BrandColor::foregroundFor($color))->toBe('#0a0a0a');
})->with(['#ffffff', '#ffff00', '#a3e635', '#fde047']);

test('the three digit form agrees with the six digit one', function () {
    expect(BrandColor::foregroundFor('#f00'))
        ->toBe(BrandColor::foregroundFor('#ff0000'));
});

// Anything that reaches here unparseable has already passed validation, so the
// safer guess is the one that keeps text readable on a dark surface.
test('an unparseable colour falls back to white text', function () {
    expect(BrandColor::foregroundFor('not-a-colour'))->toBe('#ffffff');
});

test('no stylesheet is produced without a colour', function (?string $color) {
    expect(BrandColor::css($color))->toBe('');
})->with([null, '']);

test('the stylesheet overrides every branded token in both modes', function () {
    $css = BrandColor::css('#dc2626');

    expect($css)
        ->toContain('html:root, html.dark')
        ->toContain('--primary: #dc2626')
        ->toContain('--primary-foreground: #ffffff')
        ->toContain('--ring: #dc2626')
        ->toContain('--sidebar-primary: #dc2626')
        ->toContain('--sidebar-primary-foreground: #ffffff');
});
