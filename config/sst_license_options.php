<?php

const SST_LICENSE_TYPES = [
    'Tecnico' => 'Técnico',
    'Tecnologo' => 'Tecnólogo',
    'Profesional' => 'Profesional',
    'Especialista' => 'Especialista SST',
];

const SST_LICENSE_ISSUERS = [
    'Secretaria de Salud Departamental de Amazonas',
    'Secretaria de Salud Departamental de Antioquia',
    'Secretaria de Salud Departamental de Arauca',
    'Secretaria de Salud Departamental de Atlantico',
    'Secretaria de Salud Departamental de Bolivar',
    'Secretaria de Salud Departamental de Boyaca',
    'Secretaria de Salud Departamental de Caldas',
    'Secretaria de Salud Departamental de Caqueta',
    'Secretaria de Salud Departamental de Casanare',
    'Secretaria de Salud Departamental de Cauca',
    'Secretaria de Salud Departamental de Cesar',
    'Secretaria de Salud Departamental de Choco',
    'Secretaria de Salud Departamental de Cordoba',
    'Secretaria de Salud Departamental de Cundinamarca',
    'Secretaria de Salud Departamental de Guainia',
    'Secretaria de Salud Departamental de Guaviare',
    'Secretaria de Salud Departamental de Huila',
    'Secretaria de Salud Departamental de La Guajira',
    'Secretaria de Salud Departamental de Magdalena',
    'Secretaria de Salud Departamental de Meta',
    'Secretaria de Salud Departamental de Narino',
    'Secretaria de Salud Departamental de Norte de Santander',
    'Secretaria de Salud Departamental de Putumayo',
    'Secretaria de Salud Departamental de Quindio',
    'Secretaria de Salud Departamental de Risaralda',
    'Secretaria de Salud Departamental de San Andres, Providencia y Santa Catalina',
    'Secretaria de Salud Departamental de Santander',
    'Secretaria de Salud Departamental de Sucre',
    'Secretaria de Salud Departamental de Tolima',
    'Secretaria de Salud Departamental de Valle del Cauca',
    'Secretaria de Salud Departamental de Vaupes',
    'Secretaria de Salud Departamental de Vichada',
    'Secretaria Distrital de Salud de Bogota D.C.',
    'Secretaria Distrital de Salud de Barranquilla',
    'Secretaria Distrital de Salud de Buenaventura',
    'Secretaria Distrital de Salud de Cartagena',
    'Secretaria Distrital de Salud de Santa Marta',
];

function sst_license_is_expired(?string $issuedAt, ?DateTimeImmutable $today = null): bool
{
    if (!$issuedAt) {
        return false;
    }

    try {
        $issued = new DateTimeImmutable($issuedAt);
    } catch (Exception $e) {
        return true;
    }

    $today = $today ?: new DateTimeImmutable('today');
    return $issued->modify('+10 years') <= $today;
}

function sst_license_is_future_date(?string $issuedAt, ?DateTimeImmutable $today = null): bool
{
    if (!$issuedAt) {
        return false;
    }

    try {
        $issued = new DateTimeImmutable($issuedAt);
    } catch (Exception $e) {
        return true;
    }

    $today = $today ?: new DateTimeImmutable('today');
    return $issued > $today;
}
