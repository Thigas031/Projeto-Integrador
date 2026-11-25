<?php

function getProfilePermissions(string $profile): array
{
    $permissions = [
        'GERENTE' => ['GERENTE'],
        'PORTEIRO' => ['PORTEIRO'],
    ];

    return $permissions[strtoupper(trim($profile))] ?? [];
}
