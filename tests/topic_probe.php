<?php
/** Read-only detection of the actual topic reply component in a preserved view. */
declare(strict_types=1);
$file=$argv[1]??'';
if(!is_file($file)){fwrite(STDERR,"Topic fixture missing.\n");exit(1);}
$source=file_get_contents($file);
$component=str_contains($source,'topic-replies-panel')?'tabler-reply-panel':'legacy-empty-banner';
$numeric=str_contains($source,'引用回复 ID（可选）')?'visible':'hidden';
echo "REPLIES=$component; MANUAL_QUOTE_ID=$numeric\n";
