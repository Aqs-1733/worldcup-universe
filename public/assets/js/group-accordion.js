/*
 * 文件作用：小组赛分组的折叠/展开控制。
 * 作者：梁靖泽_2412852
 */
(() => {
 document.querySelectorAll('[data-accordion-set]').forEach(section=>{
  section.querySelectorAll('[data-expand-groups]').forEach(button=>button.addEventListener('click',()=>section.querySelectorAll('details[data-group]').forEach(group=>group.open=button.dataset.expandGroups==='true')));
 });
})();
