/* The highlighted journey is constrained to the verified edges provided by Yii2. */
(() => {
 const root=document.querySelector('[data-bracket-route]');if(!root)return;
 const {matches,rounds,edges}=JSON.parse(root.querySelector('[data-route-data]').textContent);
 const picker=root.querySelector('[data-route-team]'),track=root.querySelector('[data-route-track]'),scroller=root.querySelector('.edition-route-scroll');
 const templates=new Map([...root.querySelectorAll('[data-route-template]')].map(node=>[Number(node.dataset.routeTemplate),node]));
 const edgeFor=(from,to)=>edges.find(edge=>Number(edge.from_match_id)===from&&Number(edge.to_match_id)===to);
 const render=()=>{
  const team=Number(picker.value),journey=matches.filter(game=>game.home===team||game.away===team);
  track.replaceChildren();let previous=null;
  const bronze=journey.find(game=>game.stage==='季军赛');
  const displayedRounds=bronze?rounds.map(round=>round==='决赛'?'季军赛':round):rounds;
  displayedRounds.forEach(round=>{
   const column=document.createElement('div');column.className='edition-route-round';
   const label=document.createElement('span');label.className='edition-round-label';label.textContent=round;column.append(label);
   const game=journey.find(item=>item.stage===round);
   if(game){
    column.append(templates.get(game.id).content.cloneNode(true));
    column.querySelectorAll('[data-route-side]').forEach(side=>side.classList.toggle('is-route-team',Number(side.dataset.routeSide)===team));
    const edge=previous&&edgeFor(previous.id,game.id);
    if(edge){column.classList.add('has-verified-link');column.dataset.fromMatch=previous.id;column.dataset.toMatch=game.id;column.dataset.outcome=edge.outcome;}
    previous=game;
   }else{const empty=document.createElement('div');empty.className='edition-route-empty';empty.textContent=journey.length?'未进入本轮':'没有本轮比赛';column.append(empty);}
   track.append(column);
  });
  root.querySelector('[data-route-status]').textContent=`${picker.selectedOptions[0].textContent} · ${journey.length} 场淘汰赛${bronze?'（包含季军赛）':''}`;
  scroller.scrollLeft=0;
 };
 picker.addEventListener('change',render);
 root.querySelectorAll('[data-route-scroll]').forEach(button=>button.addEventListener('click',()=>scroller.scrollBy({left:Number(button.dataset.routeScroll)*300,behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'})));
 render();
})();
