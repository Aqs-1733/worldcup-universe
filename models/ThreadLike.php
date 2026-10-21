<?php
/** Composite-key like, unique per member/topic. */
declare(strict_types=1);
namespace app\models;
class ThreadLike extends Record { public static function tableName(): string { return '{{%thread_likes}}'; } }
