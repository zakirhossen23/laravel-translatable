<?php

namespace Zakirhossen23\Translatable\Exception;

class LocalesNotDefinedException extends \Exception
{
    public static function make(): self
    {
        return new self('Please make sure you have run `php artisan vendor:publish --provider="Zakirhossen23\Translatable\TranslatableServiceProvider"` and that the locales configuration is defined.');
    }
}
