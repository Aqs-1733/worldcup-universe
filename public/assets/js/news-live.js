/* News worker polls publishers; this page checks for newly stored articles without blocking navigation. */
(() => {
 const bar=document.querySelector('[data-news-live]');if(!bar)return;
 let latest=null;
 async function check(){if(document.hidden)return;try{const r=await fetch('/news/status',{credentials:'same-origin',cache:'no-store'});if(!r.ok)return;const data=await r.json();if(latest!==null&&data.latest>latest)bar.querySelector('[data-news-new]').hidden=false;latest??=data.latest;bar.querySelector('[data-news-updated]').textContent=data.updated||'—';}catch{}}
 bar.querySelector('[data-news-reload]')?.addEventListener('click',()=>location.reload());check();setInterval(check,60000);
})();
