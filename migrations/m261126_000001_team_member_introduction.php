<?php
/**
 * 文件作用：数据库迁移：为团队成员增加独立的自我介绍字段。
 */
declare(strict_types=1);
use yii\db\Migration;

final class m261126_000001_team_member_introduction extends Migration
{
    public function up()
    {
        $this->addColumn('{{%team_members}}', 'self_introduction', $this->text()->comment('显示在团队页的成员卡片中，最多 500 字'));
    }

    public function down()
    {
        $this->dropColumn('{{%team_members}}', 'self_introduction');
    }
}
