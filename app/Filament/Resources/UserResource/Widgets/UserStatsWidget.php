<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Gesamte Benutzer', User::count())
                ->description('Alle registrierten Benutzer')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Aktive Benutzer', User::where('is_active', true)->count())
                ->description('Benutzer mit aktivem Status')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Administratoren', User::where('is_admin', true)->count())
                ->description('Benutzer mit Admin-Rechten')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),

            Stat::make('Verifizierte E-Mails', User::whereNotNull('email_verified_at')->count())
                ->description('Benutzer mit verifizierter E-Mail')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('info'),
        ];
    }
}
