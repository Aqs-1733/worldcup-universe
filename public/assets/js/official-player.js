/* Load only the server-validated official embed, never media streams or iframe HTML from input. */
(() => {
 const root=document.querySelector('[data-official-player]');if(!root)return;
 const screen=root.querySelector('[data-video-screen]'),status=root.querySelector('[data-video-status]'),help=root.querySelector('[data-video-help]');let timer=null;
 const load=()=>{
  window.clearTimeout(timer);const iframe=document.createElement('iframe');
  iframe.title='官方比赛播放器';iframe.allow='autoplay; encrypted-media; picture-in-picture; fullscreen';iframe.allowFullscreen=true;iframe.referrerPolicy='strict-origin-when-cross-origin';
  iframe.src=root.dataset.embed;screen.replaceChildren(iframe);root.setAttribute('data-player-state','loading');status.textContent='正在连接官方播放器…';
  iframe.addEventListener('load',()=>{window.clearTimeout(timer);root.dataset.playerState='loaded';status.textContent='官方播放器已载入。是否能够播放取决于官方账号、地区与播放权限。';});
  iframe.addEventListener('error',()=>{window.clearTimeout(timer);root.dataset.playerState='unavailable';status.textContent='官方播放器连接失败。你可以重新加载，或前往官方页面。';help.open=true;});
  timer=window.setTimeout(()=>{root.dataset.playerState='timeout';status.textContent='官方播放器响应较慢。可以重试，或直接前往官方页面。';help.open=true;},18000);
 };
 root.querySelector('[data-video-load]')?.addEventListener('click',load);
 root.querySelector('[data-video-retry]')?.addEventListener('click',load);
 window.addEventListener('pagehide',()=>window.clearTimeout(timer),{once:true});
})();
