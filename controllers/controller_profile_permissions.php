<?php

function getProfilePermissions(string $profile): array
{
    $permissions = [
        'GERENTE' => ['GERENTE'],
        'PORTEIRO' => ['PORTEIRO'],
        'OUTRO' => ['GERENTE', 'PORTEIRO', 'OUTRO'],
    ];

    return $permissions[strtoupper(trim($profile))] ?? [];
}
