<?php

namespace Lyhty\Geometry;

use Illuminate\Support\ServiceProvider;

class GeometryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('lyhty-geometry', function () {
            return new Factory(new GeosWrapper);
        });
    }
}
