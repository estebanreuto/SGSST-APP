<?php

function perfil5_decode_list(?string $json): array
{
    $data = json_decode((string)$json, true);
    return is_array($data) ? array_values(array_filter($data, fn($item) => trim((string)$item) !== '')) : [];
}

function perfil5_centros(PDO $conn, int $empresa_id): array
{
    $stmt = $conn->prepare("SELECT * FROM estandar5_centros_medicos WHERE empresa_id = ? AND estado = 'activo' ORDER BY nombre ASC");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function perfil5_procesos(PDO $conn, int $empresa_id): array
{
    $stmt = $conn->prepare("SELECT nombre FROM estandar5_procesos_perfil WHERE empresa_id = ? ORDER BY nombre ASC");
    $stmt->execute([$empresa_id]);
    return array_map(fn($row) => $row['nombre'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function perfil5_herramientas(): array
{
    return [
        'administrativo' => [
            'titulo' => 'Administrativo',
            'icon' => 'fa-laptop',
            'items' => ['Computador', 'Teléfono', 'Impresora', 'Escáner', 'Video beam', 'Diadema'],
        ],
        'menores' => [
            'titulo' => 'Herramientas menores',
            'icon' => 'fa-toolbox',
            'items' => ['Martillo', 'Alicate', 'Destornillador', 'Llave', 'Cincel', 'Segueta', 'Machete', 'Pala'],
        ],
        'electricas' => [
            'titulo' => 'Herramientas eléctricas',
            'icon' => 'fa-plug-circle-bolt',
            'items' => ['Taladro', 'Pulidora', 'Sierra circular', 'Rotomartillo', 'Esmeril', 'Hidrolavadora'],
        ],
    ];
}

function perfil5_tareas_alto_riesgo(): array
{
    return [
        'Trabajo en alturas',
        'Trabajo en espacios confinados',
        'Trabajo con energías peligrosas',
        'Trabajo en caliente',
        'Izaje de cargas',
        'Excavaciones',
        'Manejo de sustancias químicas peligrosas',
        'Conducción de vehículos o maquinaria',
    ];
}
