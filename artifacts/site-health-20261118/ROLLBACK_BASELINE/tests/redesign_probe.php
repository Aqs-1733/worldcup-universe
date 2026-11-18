<?php
/** Read-only component regression probe for preserved and modified forum fixtures. */
declare(strict_types=1);
$path=$argv[1]??'';
if(!is_file($path)){fwrite(STDERR,"Fixture missing.\n");exit(1);}
$source=file_get_contents($path);
$component=str_contains($source,'community-topic-list')?'tabler-topics':'legacy-card';
$badge=str_contains($source,'示例帖')?'present':'absent';
echo "FORUM_COMPONENT=$component; DEMO_BADGE=$badge\n";
