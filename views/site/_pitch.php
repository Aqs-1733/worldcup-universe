<?php
/** Pitch markings drawn by scroll (pitch-lines.js): 'halfway' and 'box' are home section dividers, 'full' sits behind the footer.
    Every stroke uses pathLength="1" so one dash animation fits any length; 'full' is a 105 × 68 m pitch at 10 units per metre. */
$type??='halfway';
?>
<?php if($type==='full'): ?><svg class="pitch-lines pitch-full" data-pitch-draw viewBox="0 0 1050 680" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">
 <path pathLength="1" d="M25 25H1025V655H25Z"/><path pathLength="1" d="M525 25V655"/><circle pathLength="1" cx="525" cy="340" r="91.5"/>
 <path pathLength="1" d="M25 138.4H190V541.6H25"/><path pathLength="1" d="M25 248.4H80V431.6H25"/><path pathLength="1" d="M190 266.9A91.5 91.5 0 0 1 190 413.1"/>
 <path pathLength="1" d="M1025 138.4H860V541.6H1025"/><path pathLength="1" d="M1025 248.4H970V431.6H1025"/><path pathLength="1" d="M860 266.9A91.5 91.5 0 0 0 860 413.1"/>
 <path pathLength="1" d="M25 35A10 10 0 0 0 35 25M1015 25A10 10 0 0 0 1025 35M1025 645A10 10 0 0 0 1015 655M35 655A10 10 0 0 0 25 645"/>
 <circle class="pitch-spot" cx="525" cy="340" r="4"/><circle class="pitch-spot" cx="135" cy="340" r="4"/><circle class="pitch-spot" cx="915" cy="340" r="4"/>
</svg>
<?php elseif($type==='box'): ?><svg class="pitch-lines pitch-divider" data-pitch-draw viewBox="0 0 1200 160" aria-hidden="true" focusable="false">
 <path pathLength="1" d="M0 18H1200"/><path pathLength="1" d="M400 18V118H800V18"/><path pathLength="1" d="M520 18V48H680V18"/><path pathLength="1" d="M543.7 118A62 62 0 0 0 656.3 118"/>
 <circle class="pitch-spot" cx="600" cy="92" r="3.5"/>
</svg>
<?php else: ?><svg class="pitch-lines pitch-divider" data-pitch-draw viewBox="0 0 1200 160" aria-hidden="true" focusable="false">
 <path pathLength="1" d="M0 80H1200"/><circle pathLength="1" cx="600" cy="80" r="62"/>
 <circle class="pitch-spot" cx="600" cy="80" r="4"/>
</svg>
<?php endif ?>
