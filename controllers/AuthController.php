<?php

declare(strict_types=1);

namespace app\controllers;

use App\Controllers\AuthController as LegacyAuthController;

final class AuthController extends LegacyBridgeController
{
    private function auth(): LegacyAuthController
    {
        return new LegacyAuthController();
    }

    public function actionLogin(): string { return $this->legacy($this->auth(), $_SERVER['REQUEST_METHOD'] === 'POST' ? 'loginPost' : 'login'); }
    public function actionRegister(): string { return $this->legacy($this->auth(), $_SERVER['REQUEST_METHOD'] === 'POST' ? 'registerPost' : 'register'); }
    public function actionLogout(): string { return $this->legacy($this->auth(), 'logout'); }
}
