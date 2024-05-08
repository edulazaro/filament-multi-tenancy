<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tag;
use Filament\Facades\Filament;
 
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
// ...
 
class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next): Response
    {
        // Can also be moved to each model.
        // This is to prevent data leaking when doing dashboard reports
        // and other more complicated queries that touch databases
        Category::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );
        Order::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );
        Tag::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );
        Product::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );
 
        return $next($request);
    }
}