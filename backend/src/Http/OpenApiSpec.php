<?php
declare(strict_types=1);

namespace App\Http;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'REX — Vendeu, Ganhou',
    description: 'API da plataforma de incentivo de vendas REX.',
    version: '1.0.0',
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Docker local',
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Autenticação, renovação de sessão e logout.',
)]
#[OA\Tag(
    name: 'Products',
    description: 'Catálogo de SKUs e pontos por unidade.',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT de acesso retornado em /auth/login e /auth/refresh.',
)]
#[OA\SecurityScheme(
    securityScheme: 'cookieAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'refresh_token',
    description: 'Cookie HttpOnly de refresh token. Exigido apenas por /auth/refresh e /auth/logout.',
)]
final class OpenApiSpec {}
