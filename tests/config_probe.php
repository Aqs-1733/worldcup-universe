<?php
/** Deterministic read-only probe of the selected configuration fixture. */
declare(strict_types=1);
$file=$argv[1]??''; if(!is_file($file)) {fwrite(STDERR,"Configuration file missing.\n");exit(1);}
$text=(string)file_get_contents($file);
if(!preg_match("/'enableCsrfValidation'\\s*=>\\s*(true|false)/",$text,$csrf)||!preg_match("/'teams'\\s*=>\\s*'([^']+)'/",$text,$route)) { fwrite(STDERR,"Expected configuration keys missing.\n");exit(1); }
echo 'CSRF='.($csrf[1]==='true'?'on':'off').'; teams='.$route[1]."\n";
