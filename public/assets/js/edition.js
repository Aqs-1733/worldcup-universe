/* Player chapters: manual tabs plus a gentle auto-advance (paused on hover/focus) and GSAP transitions when available. */
(() => {
 const gsap=window.gsap,reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
 const interval=7000;
 document.querySelectorAll('[data-player-spotlight]').forEach(root => {
  const tabs=[...root.querySelectorAll('[data-player-tab]')],panels=[...root.querySelectorAll('[data-player-panel]')];
  let current=0,timer=null;
  root.style.setProperty('--spot-interval',interval+'ms');
  const animateIn=panel=>{
   if(!gsap||reduced)return;
   const copy=panel.querySelectorAll('.edition-player-nationality,.spotlight-copy h2>span,.edition-player-original,.edition-player-club,.edition-player-facts>div,.edition-player-link');
   gsap.fromTo(copy,{y:40,autoAlpha:0},{y:0,autoAlpha:1,duration:.8,stagger:.06,ease:'power3.out'});
   gsap.fromTo(panel.querySelector('.spotlight-photo'),{x:80,autoAlpha:0,scale:1.04},{x:0,autoAlpha:1,scale:1,duration:1.1,ease:'expo.out'});
   gsap.fromTo(panel.querySelector('.edition-shirt-number'),{y:120,autoAlpha:0},{y:0,autoAlpha:1,duration:1.2,ease:'expo.out'});
   gsap.fromTo(panel.querySelector('.edition-player-watermark'),{xPercent:-8,autoAlpha:0},{xPercent:0,autoAlpha:1,duration:1.4,ease:'power2.out'});
   panel.querySelectorAll('.edition-player-facts dd').forEach(dd=>{const to=parseInt(dd.textContent,10);if(!Number.isFinite(to))return;const o={v:0};gsap.to(o,{v:to,duration:1.1,ease:'power2.out',onUpdate:()=>{dd.textContent=Math.round(o.v);}});});
  };
  const activate=(index,focus=false)=>{
   if(index===current&&!focus&&panels[index].classList.contains('is-active'))return;
   current=index;
   tabs.forEach((tab,i)=>{tab.setAttribute('aria-selected',String(i===index));tab.tabIndex=i===index?0:-1;});
   panels.forEach((panel,i)=>{panel.hidden=i!==index;panel.classList.toggle('is-active',i===index);});
   animateIn(panels[index]);
   root.dispatchEvent(new CustomEvent('player:change'));
   if(focus)tabs[index].focus();
   schedule();
  };
  const schedule=()=>{clearTimeout(timer);if(reduced||tabs.length<2||root.classList.contains('is-paused')||root.matches(':hover')||root.contains(document.activeElement))return;timer=setTimeout(()=>activate((current+1)%tabs.length),interval);};
  tabs.forEach((tab,index)=>{
   tab.addEventListener('click',()=>activate(index));
   tab.addEventListener('keydown',event=>{
    if(!['ArrowRight','ArrowLeft','Home','End'].includes(event.key))return;
    event.preventDefault();
    const next=event.key==='Home'?0:event.key==='End'?tabs.length-1:(index+(event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length;
    activate(next,true);
   });
  });
  const pause=()=>{root.classList.add('is-paused');clearTimeout(timer);};
  const resume=()=>{if(root.matches(':hover')||root.contains(document.activeElement))return;root.classList.remove('is-paused');schedule();};
  root.addEventListener('pointerenter',pause);root.addEventListener('pointerleave',resume);
  root.addEventListener('focusin',pause);root.addEventListener('focusout',e=>{if(!root.contains(e.relatedTarget))resume();});
  // Start the timer only once the spotlight is on screen.
  new IntersectionObserver(([entry])=>entry.isIntersecting?schedule():clearTimeout(timer),{threshold:.35}).observe(root);
  // Pointer parallax on the portrait.
  if(gsap&&!reduced&&window.matchMedia('(hover: hover)').matches){
   root.addEventListener('pointermove',e=>{const r=root.getBoundingClientRect(),nx=(e.clientX-r.left)/r.width-.5,ny=(e.clientY-r.top)/r.height-.5;const panel=panels[current];gsap.to(panel.querySelector('.spotlight-photo'),{x:nx*24,y:ny*10,duration:.8,ease:'power3.out',overwrite:'auto'});gsap.to(panel.querySelector('.edition-shirt-number'),{x:nx*-40,duration:1,ease:'power3.out',overwrite:'auto'});});
  }
 });
})();
