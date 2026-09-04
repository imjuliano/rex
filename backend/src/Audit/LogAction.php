<?php
declare(strict_types=1);

namespace App\Audit;

enum LogAction: int {
    case PRODUCT_CREATED      = 10;
    case PRODUCT_UPDATED      = 11;
    case PRODUCT_DELETED      = 12;
    case PRODUCT_ACTIVATED    = 13;
    case PRODUCT_DEACTIVATED  = 14;

    case CAMPAIGN_CREATED     = 20;
    case CAMPAIGN_UPDATED     = 21;
    case CAMPAIGN_CLOSED      = 22;

    case SALE_CREATED         = 30;
    case SALE_CANCELED        = 31;
    case SALE_BATCH_CREATED   = 32;

    case USER_CREATED         = 40;
    case USER_UPDATED         = 41;
    case USER_DELETED         = 42;

    case AUTH_LOGIN_SUCCESS   = 50;
    case AUTH_LOGIN_FAILED    = 51;
    case AUTH_LOGOUT          = 52;
    case AUTH_TOKEN_REFRESHED = 53;
    case AUTH_REFRESH_REUSE_DETECTED = 54;

    public function label(): string {
        return match ($this) {
            self::PRODUCT_CREATED     => 'Produto criado',
            self::PRODUCT_UPDATED     => 'Produto atualizado',
            self::PRODUCT_DELETED     => 'Produto excluído',
            self::PRODUCT_ACTIVATED   => 'Produto ativado',
            self::PRODUCT_DEACTIVATED => 'Produto inativado',

            self::CAMPAIGN_CREATED    => 'Campanha criada',
            self::CAMPAIGN_UPDATED    => 'Campanha atualizada',
            self::CAMPAIGN_CLOSED     => 'Campanha encerrada',

            self::SALE_CREATED        => 'Venda criada',
            self::SALE_CANCELED       => 'Venda cancelada',
            self::SALE_BATCH_CREATED  => 'Importação de vendas',

            self::USER_CREATED        => 'Usuário criado',
            self::USER_UPDATED        => 'Usuário atualizado',
            self::USER_DELETED        => 'Usuário excluído',

            self::AUTH_LOGIN_SUCCESS  => 'Login bem-sucedido',
            self::AUTH_LOGIN_FAILED   => 'Login falho',
            self::AUTH_LOGOUT         => 'Logout',
            self::AUTH_TOKEN_REFRESHED => 'Sessão renovada',
            self::AUTH_REFRESH_REUSE_DETECTED => 'Reuso de refresh token detectado',
        };
    }
}
