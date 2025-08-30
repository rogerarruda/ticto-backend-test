<?php

namespace App\Services\ViaCep;

use Illuminate\Support\Facades\Http;

class ViaCepService
{
    public function consultarCep(string $cep): array
    {
        $cep = preg_replace('/\D+/', '', $cep);

        return Http::get("https://viacep.com.br/ws/{$cep}/json/")
            ->throwIf(fn ($response) => $response->failed())
            ->json();
    }
}
