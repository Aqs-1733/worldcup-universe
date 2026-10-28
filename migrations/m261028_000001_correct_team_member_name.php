<?php
/** Correct the homepage team member name on existing installations. */
declare(strict_types=1);
use yii\db\Migration;

final class m261028_000001_correct_team_member_name extends Migration
{
    public function safeUp()
    {
        $this->update('{{%team_members}}', ['name'=>'蒋林瀞'], ['student_no'=>'2413507','name'=>'蒋林瀚']);
    }

    public function safeDown()
    {
        $this->update('{{%team_members}}', ['name'=>'蒋林瀚'], ['student_no'=>'2413507','name'=>'蒋林瀞']);
    }
}
