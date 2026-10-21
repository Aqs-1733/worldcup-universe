<?php
/** Publish a complete, hash-verified official collection with a single DB transaction. */
declare(strict_types=1);
namespace app\commands;
use app\services\OfficialFifaSyncService;
use yii\console\Controller;
final class FifaController extends Controller
{
    public function actionImport(string $folder): int
    {
        $report=(new OfficialFifaSyncService())->import($folder);
        $this->stdout('PASS FIFA import: '.json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
        return 0;
    }
}
