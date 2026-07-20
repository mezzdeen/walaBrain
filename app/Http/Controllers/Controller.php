<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Extends the framework controller rather than standing alone so that
 * `authorizeResource()` works: it registers `can:` middleware through
 * `$this->middleware()`, and the router only collects that middleware from
 * controllers exposing `getMiddleware()`. Both live on the base class.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
