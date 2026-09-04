<?php
declare(strict_types=1);

namespace App\User;

enum UserRole: string {
    case ADMIN = 'admin';
    case SELLER = 'seller';
}
