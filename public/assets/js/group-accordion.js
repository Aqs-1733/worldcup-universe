/* Semantic details remain usable without JS; controls operate only their own group set. */
(() => {
 document.querySelectorAll('[data-accordion-set]').forEach(section=>{
  section.querySelectorAll('[data-expand-groups]').forEach(button=>button.addEventListener('click',()=>section.querySelectorAll('details[data-group]').forEach(group=>group.open=button.dataset.expandGroups==='true')));
 });
})();
