/* Flag-bearing progressive select. Native form values and change events stay authoritative. */
(() => {
 document.querySelectorAll('select[data-team-select]').forEach((select,index)=>{
  const wrap=document.createElement('span');wrap.className='team-picker';select.before(wrap);wrap.append(select);
  const button=document.createElement('button');button.type='button';button.className='team-picker-trigger';button.setAttribute('role','combobox');button.setAttribute('aria-haspopup','listbox');button.setAttribute('aria-expanded','false');
  const label=select.closest('label');button.setAttribute('aria-label',label?.childNodes[0]?.textContent.trim()||'选择球队');
  const list=document.createElement('span');list.className='team-picker-list';list.id='team-options-'+index;list.setAttribute('role','listbox');list.setAttribute('aria-label',button.getAttribute('aria-label'));list.hidden=true;button.setAttribute('aria-controls',list.id);
  const items=[...select.options].map((option,i)=>{
   const item=document.createElement('button');item.type='button';item.className='team-picker-option';item.setAttribute('role','option');item.tabIndex=-1;item.dataset.index=String(i);item.disabled=option.disabled;
   for(const key of ['flag','awayFlag']){const src=option.dataset[key];if(src?.startsWith('/assets/media/flags/')){const img=document.createElement('img');img.src=src;img.alt='';img.width=25;img.height=18;img.className='team-flag';item.append(img);}}
   const text=document.createElement('span');text.textContent=option.textContent;item.append(text);list.append(item);item.addEventListener('click',()=>{select.selectedIndex=i;select.dispatchEvent(new Event('change',{bubbles:true}));close();button.focus();});return item;
  });
  const sync=()=>{button.replaceChildren(...[...items[select.selectedIndex]?.childNodes||[]].map(n=>n.cloneNode(true)));items.forEach((n,i)=>n.setAttribute('aria-selected',String(i===select.selectedIndex)));};
  const close=()=>{list.hidden=true;button.setAttribute('aria-expanded','false');wrap.classList.remove('is-open');};
  const open=()=>{document.dispatchEvent(new CustomEvent('close-team-pickers',{detail:wrap}));list.hidden=false;button.setAttribute('aria-expanded','true');wrap.classList.add('is-open');items[select.selectedIndex]?.focus();};
  button.addEventListener('click',()=>list.hidden?open():close());
  button.addEventListener('keydown',e=>{if(['ArrowDown','ArrowUp'].includes(e.key)){e.preventDefault();open();}});
  list.addEventListener('keydown',e=>{const i=items.indexOf(document.activeElement);let next=i;if(e.key==='Escape'){e.preventDefault();close();button.focus();return;}if(e.key==='Tab'){close();return;}if(e.key==='ArrowDown')next=(i+1)%items.length;else if(e.key==='ArrowUp')next=(i+items.length-1)%items.length;else if(e.key==='Home')next=0;else if(e.key==='End')next=items.length-1;else return;e.preventDefault();items[next].focus();});
  document.addEventListener('click',e=>{if(!wrap.contains(e.target))close();});document.addEventListener('close-team-pickers',e=>{if(e.detail!==wrap)close();});
  select.addEventListener('change',sync);select.addEventListener('invalid',()=>button.focus());select.form?.addEventListener('reset',()=>requestAnimationFrame(sync));
  select.tabIndex=-1;select.setAttribute('aria-hidden','true');select.classList.add('team-native');wrap.append(button,list);sync();
 });
})();
