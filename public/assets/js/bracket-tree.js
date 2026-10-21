/* Progressive winner-path tree; native details provide the no-JS and keyboard baseline. */
(() => {
 const root=document.querySelector('[data-bracket-tree]');if(!root)return;
 const branches=[...root.querySelectorAll('[data-match]')],picker=root.querySelector('[data-bracket-team]'),status=root.querySelector('[data-tree-status]');
 const reset=()=>{branches.forEach(branch=>{branch.open=branch.parentElement.classList.contains('championship-tree');branch.classList.remove('is-path','is-muted');});};
 picker?.addEventListener('change',()=>{
  reset();const id=picker.value;if(!id){status.textContent='点击对阵展开前一轮';return;}
  let matches=0;
  branches.forEach(branch=>{const selected=[branch.dataset.home,branch.dataset.away].includes(id);branch.classList.toggle('is-path',selected);if(selected){matches++;let ancestor=branch;while(ancestor&&root.contains(ancestor)){if(ancestor.matches('details'))ancestor.open=true;ancestor=ancestor.parentElement;}}});
  status.textContent=`${picker.selectedOptions[0].textContent} · ${matches} 场淘汰赛`;
 });
 root.querySelector('[data-tree-reset]')?.addEventListener('click',()=>{picker.value='';picker.dispatchEvent(new Event('change',{bubbles:true}));});
})();
