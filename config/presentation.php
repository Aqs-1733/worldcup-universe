<?php
/** Editorial choices; all sports facts continue to come from the database. */
return [
 'home'=>[
  'image'=>'/assets/media/photos/tunnel-hero.png','imageAlt'=>'球员背影穿过暗色通道，走向灯光明亮的足球场',
  'sourceUrl'=>'https://beiradocampo.com.br/radar-transferencias-cebolinha-caio-henrique-bidon-2026',
  'sourceLabel'=>'图像来源 · Beira do Campo',
  'eyebrow'=>'2026 FIFA WORLD CUP','title'=>'走进世界杯',
  'description'=>'从球员通道出发，走进每一场比赛。',
  'primary'=>['label'=>'探索赛事','url'=>'/worldcup'],'secondary'=>['label'=>'球队与球员','url'=>'/teams'],
  'imagePosition'=>'53% 50%','mobileImagePosition'=>'53% 50%',
  'shadeOpacity'=>0.65,'revealDuration'=>550,
 ],
 'cinematic'=>[
  // Three pinned scenes on the home page. Drop an .mp4 at the path below to replace the stadium still with a looping video.
  'eyebrow'=>'FIFA WORLD CUP 2026™ · CANADA · MEXICO · USA',
  'titleLines'=>['ONLY ONE','MORE STEP'],
  'subtitle'=>'走出通道，就是世界杯。',
  'stadiumKicker'=>'THE STAGE IS SET',
  'stadiumTitle'=>'三国同办，史上最大的一届世界杯。',
  'stadiumImage'=>'/assets/media/stadium-hero-v2.png','stadiumAlt'=>'灯光下座无虚席的足球场',
  'finalImage'=>'/assets/media/photos/display/metlife.jpg',
  'video'=>'/assets/media/videos/hero-loop.mp4',
  'scrollLength'=>420,
 ],
 'featuredPlayerSlugs'=>['mominullptr-1047-ronaldo-cristiano-ronaldo','mominullptr-946-lionel-andr-es-messi','mominullptr-1180-luka-modric'],
 'motion'=>['revealDuration'=>650,'spotlightDuration'=>420],
];
