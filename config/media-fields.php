<?php
/**
 * WorldCup Universe: admin fields that accept a browser upload in addition to a typed URL/path.
 * table => [field => allowed kinds]. 'coursework' stores privately under data/<scope>/uploads.
 * news_articles.image is virtual: it is written into raw_payload_json.image, which the news cards read.
 */
return [
    'media_assets'=>['path'=>['image','video']],
    'team_members'=>['photo_url'=>['image']],
    'players'=>['photo_url'=>['image']],
    'teams'=>['flag_url'=>['image']],
    'news_articles'=>['image'=>['image']],
    'coursework_files'=>['storage_path'=>['coursework']],
];
