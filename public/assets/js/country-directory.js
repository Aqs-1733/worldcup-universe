/*
 * 文件作用：国家目录的前端筛选与搜索。
 * 作者：崔迪_2412439
 */
(() => {const root=document.querySelector('[data-country-directory]');if(!root)return;const input=root.querySelector('[data-country-search]'),items=[...root.querySelectorAll('.country-tile')],counter=root.querySelector('[data-country-counter]');let continent='';const apply=()=>{const term=input.value.trim().toLocaleLowerCase();let count=0;items.forEach(item=>{item.hidden=Boolean((continent&&item.dataset.region!==continent)||(term&&!item.dataset.search.toLocaleLowerCase().includes(term)));if(!item.hidden)count++;});counter.textContent=`${count} 个国家`;root.querySelector('[data-country-empty]').hidden=count>0;};input.addEventListener('input',apply);root.querySelectorAll('[data-country-region]').forEach(button=>button.addEventListener('click',()=>{continent=button.dataset.countryRegion;root.querySelectorAll('[data-country-region]').forEach(x=>x.setAttribute('aria-pressed',String(x===button)));apply();}));})();
