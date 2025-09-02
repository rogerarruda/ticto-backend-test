<?php

namespace App\Services\ViaCep;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\ViaCep\ViaCepService
 * @method static array consultarCep(string $cep)
 */
class ViaCep extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ViaCepService::class;
    }
}
