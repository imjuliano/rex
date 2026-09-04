<?php
declare(strict_types=1);

namespace App\Audit\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'AuditLog',
    description: 'Registro de auditoria criptografado.',
    required: ['id', 'action', 'actor_id', 'actor_role', 'actor_email', 'entity_id', 'payload', 'diff', 'ip_address', 'user_agent', 'correlation_id', 'occurred_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'action', type: 'object', properties: [
            new OA\Property(property: 'value', type: 'integer'),
            new OA\Property(property: 'label', type: 'string'),
        ]),
        new OA\Property(property: 'actor_id', type: 'integer', nullable: true),
        new OA\Property(property: 'actor_role', type: 'string', nullable: true),
        new OA\Property(property: 'actor_email', type: 'string', nullable: true),
        new OA\Property(property: 'entity_id', type: 'string'),
        new OA\Property(property: 'payload', type: 'object', nullable: true, additionalProperties: true),
        new OA\Property(property: 'diff', type: 'object', nullable: true, additionalProperties: true),
        new OA\Property(property: 'ip_address', type: 'string', nullable: true),
        new OA\Property(property: 'user_agent', type: 'string', nullable: true),
        new OA\Property(property: 'correlation_id', type: 'string', nullable: true),
        new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time'),
    ],
)]
final class AuditLogSchema {}
