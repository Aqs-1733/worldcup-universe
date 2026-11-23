<?php
/**
 * 文件作用：控制台命令 fifa/import：校验官方数据快照的哈希后，在一个事务内导入球队、球员、比赛等数据。
 * 作者：梁靖泽_2412852
 */
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
