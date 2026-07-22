<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\ModuleServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    // Last, and one entry for all of them: it discovers every module and
    // registers that module's own provider, so a module can build on what the
    // application has registered and this list never has to name one.
    ModuleServiceProvider::class,
];
