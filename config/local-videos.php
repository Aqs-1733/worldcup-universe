<?php
/**
 * Video shelf for /watch (personal / coursework use).
 * Playback order per entry: local file in public/assets/media/videos/ → Bilibili BV id → YouTube id.
 * Entries with 'commons' are openly licensed clips: run `php scripts/fetch-open-videos.php` to download them.
 * Any extra .mp4/.webm dropped in that folder is listed automatically; a same-name .jpg becomes its poster.
 * 'hero-loop.mp4' in the same folder replaces the home page stadium still with a muted loop.
 */
return [
 [
  'slug'=>'fan-fest-2026','type'=>'moment',
  'title'=>'2026 世界杯球迷节 · 墨西哥城','subtitle'=>'第 24 场 乌兹别克斯坦 对 哥伦比亚 · 2026.06.17',
  'file'=>'fan-fest-2026.webm','commons'=>'File:2026 FIFA World Cup Match 24, Uzbekistan v Colombia - FIFA Fan Fest 2026.webm','commons_height'=>1080,
  'credit'=>'ProtoplasmaKid · CC BY 4.0 · Wikimedia Commons','source_url'=>'https://commons.wikimedia.org/wiki/File:2026_FIFA_World_Cup_Match_24,_Uzbekistan_v_Colombia_-_FIFA_Fan_Fest_2026.webm',
 ],
 [
  'slug'=>'final-highlights','type'=>'highlights','match_id'=>104,
  'title'=>'决赛集锦 · 西班牙 1–0 阿根廷','subtitle'=>'2026.07.19 · 纽约／新泽西体育场',
  'file'=>'final-highlights.mp4',
  'bilibili'=>null,
  // FIFA official upload. Embedded playback depends on YouTube trusting your network; otherwise use "open on YouTube".
  'youtube'=>'6HaHNYjnghE',
  'poster'=>'/assets/media/photos/display/metlife.jpg',
 ],
 [
  'slug'=>'japan-germany-2022','type'=>'moment',
  'title'=>'日本队进球庆祝 · 对德国','subtitle'=>'2022 卡塔尔世界杯 · 记者现场拍摄',
  'file'=>'japan-germany-2022.webm','commons'=>'File:Japanese players celebrating goals against Germany at the FIFA World Cup 2022.webm','commons_height'=>1080,
  'credit'=>'Mehdi Mortazavian / Mehr News · CC BY 4.0 · Wikimedia Commons','source_url'=>'https://commons.wikimedia.org/wiki/File:Japanese_players_celebrating_goals_against_Germany_at_the_FIFA_World_Cup_2022.webm',
 ],
 [
  'slug'=>'iss-world-cup-2026','type'=>'moment',
  'title'=>'太空中的世界杯 · 国际空间站','subtitle'=>'NASA 宇航员为 2026 世界杯庆祝 · 2026.06.20',
  'file'=>'iss-world-cup-2026.webm','commons'=>'File:Astronauts on the Space Station Celebrate the 2026 FIFA World Cup.webm','commons_height'=>480,
  'credit'=>'NASA · 公有领域 · Wikimedia Commons','source_url'=>'https://commons.wikimedia.org/wiki/File:Astronauts_on_the_Space_Station_Celebrate_the_2026_FIFA_World_Cup.webm',
 ],
 [
  'slug'=>'iran-circle-2022','type'=>'moment',
  'title'=>'伊朗队赛后围圈 · 对英格兰','subtitle'=>'2022 卡塔尔世界杯 · 记者现场拍摄',
  'file'=>'iran-circle-2022.webm','commons'=>'File:Iranian players forming a circle after defeat against England at the FIFA World Cup 2022.webm','commons_height'=>480,
  'credit'=>'Mehdi Mortazavian / Mehr News · CC BY 4.0 · Wikimedia Commons','source_url'=>'https://commons.wikimedia.org/wiki/File:Iranian_players_forming_a_circle_after_defeat_against_England_at_the_FIFA_World_Cup_2022.webm',
 ],
];
