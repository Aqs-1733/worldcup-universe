/* Database details share the selected stable map key; stale fetches never replace a later selection. */
(() => {
 const root=document.querySelector('[data-globe-3d]'),panel=document.querySelector('[data-country-panel]');if(!root||!panel)return;
 const picker=panel.querySelector('[data-globe-country-picker]'),content=panel.querySelector('[data-country-panel-content]');let request=null;
 const element=(tag,text,className)=>{const node=document.createElement(tag);if(text!==undefined)node.textContent=text;if(className)node.className=className;return node;};
 const link=(href,text,className)=>{const node=element('a',text,className);node.href=href;return node;};
 const render=data=>{
  content.replaceChildren();content.dataset.geoKey=data.geo_key;picker.value=data.country_code||'';
  const head=element('div',undefined,'edition-country-head');head.append(element('span','NATIONAL TEAM / 2026','edition-kicker'),element('h2',data.name));
  if(data.capital)head.append(element('p',`${data.capital} · ${data.region}`));content.append(head);
  if(!data.teams.length){content.append(element('p',data.message,'edition-country-message'));return;}
  data.teams.forEach(team=>{const teamLink=link(team.url,undefined,'edition-country-team');const flag=element('img');flag.src=team.flag;flag.alt='';teamLink.append(flag,element('b',team.name),element('span',`小组 ${team.group} ↗`));content.append(teamLink);});
  if(data.players.length){content.append(element('h3','场上人物'));const players=element('div',undefined,'edition-country-players');data.players.forEach(player=>{const item=link(player.url,undefined,'edition-country-player');const portrait=element('div',undefined,'edition-country-portrait'),initial=element('span',player.name.trim().slice(0,1));initial.setAttribute('aria-hidden','true');portrait.append(initial);if(player.photo){const photo=element('img');photo.src=player.photo;photo.alt='';photo.loading='lazy';photo.addEventListener('load',()=>{initial.hidden=true;},{once:true});photo.addEventListener('error',()=>photo.remove(),{once:true});portrait.append(photo);}item.append(portrait,element('span',player.name));players.append(item);});content.append(players);}
  if(data.matches.length){content.append(element('h3','赛事档案'));const games=element('div',undefined,'edition-country-games');data.matches.forEach(game=>{const item=link(game.url,undefined,'edition-country-game');item.append(element('small',`${game.stage} · ${game.date.slice(5,10)}`),element('span',`${game.home} ${game.home_score??'—'} : ${game.away_score??'—'} ${game.away}`));games.append(item);});content.append(games);}
  if(data.url)content.append(link(data.url,'完整国家资料 ↗','edition-country-more'));
 };
 const load=async key=>{
  request?.abort();const currentRequest=new AbortController();request=currentRequest;content.setAttribute('aria-busy','true');
  content.replaceChildren(element('p','正在加载国家档案…','edition-country-message'));
  try{const response=await fetch(`/api/v1/globe/${encodeURIComponent(key)}`,{signal:currentRequest.signal});if(!response.ok)throw new Error('country');const data=(await response.json()).data;if(request!==currentRequest||currentRequest.signal.aborted)return;render(data);}
  catch(error){if(error.name==='AbortError')return;content.replaceChildren(element('p','资料暂时无法加载，请稍后重试。','edition-country-message'));const retry=element('button','重新加载');retry.type='button';retry.addEventListener('click',()=>load(key),{once:true});content.append(retry);}
  finally{if(request===currentRequest&&!currentRequest.signal.aborted)content.removeAttribute('aria-busy');}
 };
 root.addEventListener('globe:select',event=>{if(!event.detail?.key)return;load(event.detail.key);});
 picker.addEventListener('change',()=>{const option=picker.selectedOptions[0];if(!option.value)return;root.dispatchEvent(new CustomEvent('globe:focus',{detail:{key:option.dataset.geoKey,lat:Number(option.dataset.lat),lng:Number(option.dataset.lng),name:option.textContent,countryKey:`country-${option.value}`}}));});
 root.addEventListener('globe:reset',()=>{request?.abort();picker.value='';content.removeAttribute('aria-busy');content.replaceChildren(element('span','EXPLORE THE WORLD','edition-kicker'),element('h2','一个世界。\n不同的热爱。'),element('p','拖动地球，点击一个国家；或者从上方选择国家队。','edition-country-message'));});
})();
