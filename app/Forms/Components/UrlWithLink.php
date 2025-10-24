<?php

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;

class UrlWithLink extends TextInput
{
    protected string $view = 'forms.components.url-with-link';

    protected function setUp(): void
    {
        parent::setUp();

        $this->url();
    }
}
