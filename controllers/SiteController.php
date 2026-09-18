<?php

declare(strict_types=1);

namespace app\controllers;

use App\Controllers\PageController as LegacyPageController;

final class SiteController extends LegacyBridgeController
{
    private function page(): LegacyPageController
    {
        return new LegacyPageController();
    }

    public function actionHome(): string { return $this->legacy($this->page(), 'home'); }
    public function actionTeams(): string { return $this->legacy($this->page(), 'teams'); }
    public function actionTeam(string $id): string { return $this->legacy($this->page(), 'team', $id); }
    public function actionPlayers(): string { return $this->legacy($this->page(), 'players'); }
    public function actionPlayer(string $id): string { return $this->legacy($this->page(), 'player', $id); }
    public function actionWorldcup(): string { return $this->legacy($this->page(), 'worldcup'); }
    public function actionMatch(int $id): string { return $this->legacy($this->page(), 'match', (string) $id); }
    public function actionNews(): string { return $this->legacy($this->page(), 'news'); }
    public function actionNewsShow(int $id): string { return $this->legacy($this->page(), 'newsShow', (string) $id); }
    public function actionCountries(): string { return $this->legacy($this->page(), 'countries'); }
    public function actionSearch(): string { return $this->legacy($this->page(), 'search'); }
    public function actionFanSpace(): string { return $this->legacy($this->page(), $_SERVER['REQUEST_METHOD'] === 'POST' ? 'saveFanSpace' : 'fanSpace'); }
    public function actionOnboarding(): string { return $this->legacy($this->page(), $_SERVER['REQUEST_METHOD'] === 'POST' ? 'saveOnboarding' : 'onboarding'); }
    public function actionCommentary(): string { return $this->legacy($this->page(), $_SERVER['REQUEST_METHOD'] === 'POST' ? 'commentaryPost' : 'commentary'); }
    public function actionVision(): string { return $this->legacy($this->page(), $_SERVER['REQUEST_METHOD'] === 'POST' ? 'visionPost' : 'vision'); }
    public function actionRefreshNews(): string { return $this->legacy($this->page(), 'refreshNews'); }
    public function actionRefreshNewsApi(): string { return $this->legacy($this->page(), 'refreshNewsApi'); }
}
