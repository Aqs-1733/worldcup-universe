<?php
/** Optional player action demos; buttons also work with touch and keyboard input. */
$sceneId='player-action-'.preg_replace('/[^a-z0-9-]/i','',$instance??'profile');
?>
<div class="player-playground" data-player-playground hidden>
 <div class="player-action-tools">
  <p><span aria-hidden="true">⚽</span><span data-action-hint>把足球移近球员，试试不同动作</span></p>
  <div class="player-action-buttons" role="group" aria-label="体验球员动作">
   <button type="button" data-player-action="header" aria-pressed="false">头球破门</button>
   <button type="button" data-player-action="handball" aria-pressed="false">手球犯规</button>
   <button type="button" data-player-action="kick" aria-pressed="false">起脚射门</button>
  </div>
 </div>
 <section class="player-action-replay" data-action-replay aria-label="球员动作演示" popover="manual" hidden>
  <header class="player-action-heading"><div><span class="player-action-kicker">球场瞬间 · 互动演示</span><h3 data-action-title>头球破门</h3><p data-action-player></p></div><button type="button" data-action-close aria-label="关闭动作演示">×</button></header>
  <svg class="player-action-pitch" viewBox="0 0 640 400" fill="none" aria-hidden="true">
   <defs>
    <pattern id="<?= e($sceneId) ?>-net" width="17" height="17" patternUnits="userSpaceOnUse"><path d="M17 0H0V17" stroke="#a9c4b9" stroke-width="1"/></pattern>
    <linearGradient id="<?= e($sceneId) ?>-grass" x1="320" y1="165" x2="320" y2="400" gradientUnits="userSpaceOnUse"><stop stop-color="#edf5f3"/><stop offset="1" stop-color="#dcece6"/></linearGradient>
   </defs>
   <rect width="640" height="400" rx="24" fill="#f1f7f6"/>
   <circle cx="110" cy="98" r="58" fill="#e8f0ef"/><circle cx="110" cy="98" r="38" stroke="#dae8e4"/>
   <path d="M0 266 640 200V400H0Z" fill="url(#<?= e($sceneId) ?>-grass)"/>
   <path d="m0 353 640-68M355 400 426 251 640 230M462 400 510 286 640 273" stroke="#fff" stroke-width="3" opacity=".85"/>
   <ellipse cx="250" cy="353" rx="84" ry="13" fill="#86a899" opacity=".18" class="pa-shadow"/>
   <ellipse cx="541" cy="327" rx="77" ry="10" fill="#86a899" opacity=".13"/>
   <g class="pa-net">
    <path d="m472 158 42-28h101v156l-24 32H472Z" fill="url(#<?= e($sceneId) ?>-net)" opacity=".8"/>
    <path d="m472 158 42-28h101v156l-24 32M591 158l24-28M591 318V158H472v160h119Z" stroke="#9bb8ad" stroke-width="3" stroke-linejoin="round"/>
    <path d="M472 318V158h119v160" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
   </g>
   <g class="pa-player">
    <g class="pa-leg-back"><path d="m230 266-9 41-12 35" stroke="#d9a47e" stroke-width="18" stroke-linecap="round"/><path d="m221 307-12 35" stroke="var(--action-sock)" stroke-width="17"/><path d="m200 340 15 1 2 10h-31q-3-5 14-11Z" fill="#334454"/></g>
    <g class="pa-leg-front"><path d="m259 266 20 38 17 37" stroke="#e4b58f" stroke-width="19" stroke-linecap="round"/><path d="m279 307 17 34" stroke="var(--action-sock)" stroke-width="18"/><path d="m288 338 14-1 14 12q2 5-5 5h-26Z" fill="#334454"/></g>
    <path d="m224 241 43 1 11 35-24 6-9-20-9 21-25-8Z" fill="var(--action-shorts)"/>
    <g class="pa-upper">
     <g class="pa-arm-back"><path d="m226 179-23 30-20 10" stroke="#d9a47e" stroke-width="14" stroke-linecap="round"/><path d="m227 170-14 7-9 17 20 10 13-22Z" fill="var(--action-kit)"/></g>
     <g class="pa-arm-front"><path d="m264 178 23 21 16 25" stroke="#e4b58f" stroke-width="14" stroke-linecap="round"/><path d="m263 168 14 6 13 16-17 14-17-22Z" fill="var(--action-kit)"/></g>
     <path d="M242 146h18v26h-18Z" fill="#d9a47e"/>
     <path d="m229 165 12-5q10 12 21 0l11 8-5 77q-22 10-46-1Z" fill="var(--action-kit)"/>
     <path d="m241 160 10 9 11-9" stroke="#ffffffa8" stroke-width="3"/>
     <path d="m228 194 40 0" stroke="#ffffff24" stroke-width="3"/>
     <text x="247" y="228" text-anchor="middle" fill="#fff" font-family="Arial,sans-serif" font-size="35" font-weight="800" data-action-number>7</text>
     <g class="pa-head"><path d="M232 127q-2-29 21-29 23 0 20 30l6 8-7 4q-3 17-19 17-17-1-21-30Z" fill="#e4b58f"/><path d="M232 129q-10-34 15-35 31-3 27 29l-9-3-2-12q-18 8-28 1l3 20Z" fill="#35404b"/><path d="M263 129h3" stroke="#35404b" stroke-width="3" stroke-linecap="round"/><path d="m260 145 7-1" stroke="#af735c" stroke-width="2" stroke-linecap="round"/></g>
    </g>
   </g>
   <g class="pa-ball"><circle r="13" fill="#fff" stroke="#425466" stroke-width="1.5"/><path d="m0-6 6 4-2 7h-8l-2-7Z" fill="#425466"/><path d="m0-6 1-7M6-2l7-3M4 5l4 6M-4 5l-5 5M-6-2l-6-4" stroke="#425466" stroke-width="1.5"/></g>
   <g class="pa-contact"><circle cx="307" cy="211" r="24" stroke="#d45261" stroke-width="2"/><circle cx="307" cy="211" r="34" stroke="#d45261" stroke-width="1" stroke-dasharray="4 5"/></g>
   <g class="pa-whistle"><rect x="337" y="88" width="99" height="42" rx="21" fill="#fff"/><path d="m360 101 16 0v13h-7a7 7 0 1 1 0-13m7 0h7v6h-7m-12 2h1" stroke="#bc4356" stroke-width="2" stroke-linejoin="round"/><path d="m391 103 5-4m-4 11h7m-8 6 5 4" stroke="#bc4356" stroke-width="2" stroke-linecap="round"/><circle cx="362" cy="110" r="3" stroke="#bc4356" stroke-width="2"/></g>
   <g class="pa-goal-flash"><path d="m501 95 8 7-8 7m42-22v20m34-12-8 7 8 7" stroke="#c45162" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></g>
  </svg>
  <div class="player-action-outcome"><span data-action-indicator aria-hidden="true">↗</span><div><b data-action-result>准备迎球</b><p data-action-caption>把这一刻交给球员。</p></div></div>
 </section>
 <span class="player-action-status sr-only" role="status" aria-live="polite" data-action-status></span>
</div>
