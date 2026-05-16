<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Str;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $configuration = $panel
            ->default()
            ->id('admin')
            ->login()
            ->authGuard('admin')
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->when(! app()->environment('testing'), fn (Panel $panel): Panel => $panel
                ->viteTheme('resources/css/filament/admin/theme.css'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\DashboardStatsOverview::class,
                \App\Filament\Widgets\OrdersTrendChart::class,
                \App\Filament\Widgets\RevenueTrendChart::class,
                \App\Filament\Widgets\OrdersByStatusChart::class,
                \App\Filament\Widgets\RecentOrdersWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        return $this->configureEnvironmentDomain($configuration);
    }

    private function configureEnvironmentDomain(Panel $panel): Panel
    {
        if($this->app->environment(['local', 'testing']))
            return $panel->path('admin');

        $domain = config('app.domain');
        return $panel
            ->path('/')
            ->domain($domain);
    }
}
