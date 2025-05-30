<?php

namespace App\Providers;

use Native\Laravel\Facades\Window;
use Native\Laravel\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->title('E-book Launcher')
            ->width(700)
            ->height(600)
            ->minWidth(600)
            ->minHeight(500)
            ->maximizable(false)
            ->resizable(true)
            ->hideMenu()
            ->alwaysOnTop(false)
            ->position(300, 200)
            ->devToolsOpen(false);
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'display_errors' => 'Off',
            'max_execution_time' => '300',
        ];
    }
}
