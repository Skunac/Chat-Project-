<?php

namespace App\Dto;

class GoogleCallbackParamsDto
{
    public function __construct(
        public string $code,
        // Optional parameters that might be present
        public ?string $scope = null,
        public ?string $authuser = null,
        public ?string $prompt = null,
    ) {
    }
}