<?php

function getProfilePermissions(string $profile): array
{
    $permissions = [
        'GERENTE' => ['GERENTE'],
        'PORTEIRO' => ['PORTEIRO'],
        'OUTRO' => ['GERENTE', 'PORTEIRO'],
    ];

    return $permissions[strtoupper(trim($profile))] ?? [];
}
