/*
 * 文件作用：首页球员通道头图的交互增强。
 * 作者：崔迪_2412439
 */
(() => {
 const hero=document.querySelector('.tunnel-hero');
 if(!hero)return;
 const reduced=window.matchMedia('(prefers-reduced-motion: reduce)');
 if(!reduced.matches)hero.classList.add('is-ready');
 const header=document.getElementById('site-header');
 const updateHeader=()=>header.classList.toggle('is-scrolled',window.scrollY>24);
 updateHeader();
 window.addEventListener('scroll',updateHeader,{passive:true});
 hero.querySelector('[data-hero-explore]').addEventListener('click',event=>{
  if(event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
  const target=document.getElementById('matchday');
  if(!target)return;
  event.preventDefault();
  target.scrollIntoView({behavior:reduced.matches?'instant':'smooth',block:'start'});
  target.setAttribute('tabindex','-1');
  target.focus({preventScroll:true});
  history.replaceState(null,'','#matchday');
 });
})();
