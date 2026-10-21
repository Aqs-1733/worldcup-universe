/* Football pointer on player showcase; touch and reduced-motion behaviour remain usable. */
(() => {
  if (!matchMedia('(hover:hover) and (pointer:fine)').matches) return;
  const reduced = matchMedia('(prefers-reduced-motion:reduce)').matches;
  document.querySelectorAll('.spotlight,.athlete-stage').forEach(stage => {
    const ball = document.createElement('span');ball.className='football-pointer';ball.setAttribute('aria-hidden','true');stage.append(ball);
    let raf=0, point=null;
    const update=()=>{raf=0;if(!point)return;const box=stage.getBoundingClientRect();const x=point.x-box.left,y=point.y-box.top;ball.style.transform=`translate3d(${x-18}px,${y-18}px,0) rotate(${x*.3}deg)`;if(!reduced){stage.style.setProperty('--player-drift',`${((x/box.width)-.5)*10}px`);}};
    stage.addEventListener('pointerenter',()=>stage.classList.add('football-active'));
    stage.addEventListener('pointermove',event=>{point={x:event.clientX,y:event.clientY};ball.hidden=Boolean(event.target.closest('a,button,input,select'));if(!raf)raf=requestAnimationFrame(update);});
    stage.addEventListener('pointerleave',()=>{stage.classList.remove('football-active');stage.style.removeProperty('--player-drift');point=null;if(raf)cancelAnimationFrame(raf);raf=0;});
    stage.addEventListener('click',event=>{if(event.target.closest('a,button'))return;ball.classList.remove('ball-kick');requestAnimationFrame(()=>ball.classList.add('ball-kick'));});
  });
})();
