<?php
/** Inspect a saved layout without running its PHP or changing application state. */
declare(strict_types=1);
$file=$argv[1]??'';
if(!is_file($file)) {fwrite(STDERR,"Missing layout input.\n");exit(2);}
$source=file_get_contents($file);
echo 'DATA_NAV='.(preg_match("~'/stats'\s*=>\s*'数据'~u",$source)?'present':'absent')."\n";
