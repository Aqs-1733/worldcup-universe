<?php
/**
 * Video shelf for /watch (personal / coursework use).
 * Playback order per entry: local file in public/assets/media/videos/ → Bilibili BV id → YouTube id.
 * Any extra .mp4/.webm dropped in that folder is listed automatically; a same-name .jpg becomes its poster.
 * 'hero-loop.mp4' in the same folder replaces the home page stadium still with a muted loop.
 */
return [
 [
  'slug'=>'final-highlights','type'=>'highlights','match_id'=>104,
  'title'=>'决赛集锦 · 西班牙 1–0 阿根廷','subtitle'=>'2026.07.19 · 纽约／新泽西体育场',
  'file'=>'final-highlights.mp4',
  'bilibili'=>null,
  // FIFA official upload; the owner may block off-site embedding, in which case the player offers "open on YouTube".
  'youtube'=>'6HaHNYjnghE',
  'poster'=>'/assets/media/photos/display/metlife.jpg',
 ],
];
