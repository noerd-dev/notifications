<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

uses(Tests\TestCase::class);

/**
 * Laravel Boost renders a package's guideline through Blade and silently DROPS
 * a guideline that fails to render.
 */
it('renders the boost guideline through blade', function (): void {
    $rendered = Blade::render(File::get(dirname(__DIR__, 2) . '/resources/boost/guidelines/core.blade.php'));

    expect($rendered)->toContain('## Notifications Module')
        ->not->toContain('@verbatim');
});
