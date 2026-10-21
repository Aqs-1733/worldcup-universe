/* Native, keyboard-accessible photo dialog; no autoplay or third-party lightbox. */
(() => {
 const dialog=document.querySelector('[data-photo-dialog]');if(!dialog||!dialog.showModal)return;
 let trigger=null;
 document.querySelectorAll('[data-gallery-image]').forEach(button=>button.addEventListener('click',()=>{
  trigger=button;const img=dialog.querySelector('img');img.src=button.dataset.galleryImage;img.alt=button.dataset.photoTitle;
  dialog.querySelector('h2').textContent=button.dataset.photoTitle;dialog.querySelector('a').href=button.dataset.photoSource;dialog.showModal();
 }));
 dialog.querySelector('[data-photo-close]').addEventListener('click',()=>dialog.close());
 dialog.addEventListener('click',e=>{if(e.target===dialog){const box=dialog.getBoundingClientRect();if(e.clientX<box.left||e.clientX>box.right||e.clientY<box.top||e.clientY>box.bottom)dialog.close();}});
 dialog.addEventListener('close',()=>trigger?.focus());
})();
