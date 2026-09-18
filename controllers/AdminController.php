<?php

declare(strict_types=1);

namespace app\controllers;

use App\Controllers\AdminController as LegacyAdminController;

final class AdminController extends LegacyBridgeController
{
    private function admin(): LegacyAdminController
    {
        return new LegacyAdminController();
    }

    public function actionDashboard(): string { return $this->legacy($this->admin(), 'dashboard'); }
    public function actionTable(string $table): string { return $this->legacy($this->admin(), 'table', $table); }
    public function actionSave(string $table): string { return $this->legacy($this->admin(), 'save', $table); }
    public function actionDelete(string $table): string { return $this->legacy($this->admin(), 'delete', $table); }
}
