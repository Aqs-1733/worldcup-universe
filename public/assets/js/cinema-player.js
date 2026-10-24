/* Custom controls for local files; click-to-load iframes for Bilibili/YouTube. */
(() => {
  const root = document.querySelector('[data-cinema]');
  if (!root) return;
  const status = document.querySelector('[data-cp-status]');
  const fmt = s => { if (!Number.isFinite(s)) return '0:00'; const m = Math.floor(s / 60), r = Math.floor(s % 60); return `${m}:${String(r).padStart(2, '0')}`; };

  const embedButton = root.querySelector('[data-cp-embed]');
  embedButton?.addEventListener('click', () => {
    const iframe = document.createElement('iframe');
    iframe.src = root.dataset.embed; iframe.title = '视频播放器'; iframe.allowFullscreen = true;
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
    root.replaceChildren(iframe); root.classList.add('is-embedded');
  });

  const video = root.querySelector('[data-cp-video]');
  if (!video) return;
  const seek = root.querySelector('[data-cp-seek]'), time = root.querySelector('[data-cp-time]'), mute = root.querySelector('[data-cp-mute]');
  let seeking = false, idle = 0;
  const toggle = () => (video.paused ? video.play().catch(() => { if (status) status.textContent = '浏览器阻止了播放，请再点一次。'; }) : video.pause());
  root.querySelectorAll('[data-cp-toggle]').forEach(b => b.addEventListener('click', toggle));
  video.addEventListener('click', toggle);
  video.addEventListener('play', () => root.classList.add('is-playing'));
  video.addEventListener('pause', () => root.classList.remove('is-playing'));
  video.addEventListener('timeupdate', () => {
    if (!seeking && video.duration) seek.value = String(Math.round((video.currentTime / video.duration) * 1000));
    time.textContent = `${fmt(video.currentTime)} / ${fmt(video.duration)}`;
    seek.style.setProperty('--fill', `${(seek.value / 10).toFixed(1)}%`);
  });
  video.addEventListener('loadedmetadata', () => { time.textContent = `0:00 / ${fmt(video.duration)}`; });
  video.addEventListener('error', () => { root.classList.add('has-error'); if (status) status.textContent = '视频文件无法解码：请确认是 H.264/AAC 编码的 MP4（或 WebM）。'; });
  seek.addEventListener('input', () => { seeking = true; seek.style.setProperty('--fill', `${seek.value / 10}%`); if (video.duration) time.textContent = `${fmt((seek.value / 1000) * video.duration)} / ${fmt(video.duration)}`; });
  seek.addEventListener('change', () => { if (video.duration) video.currentTime = (seek.value / 1000) * video.duration; seeking = false; });
  mute.addEventListener('click', () => { video.muted = !video.muted; mute.textContent = video.muted ? '🔇' : '🔊'; });
  root.querySelector('[data-cp-full]').addEventListener('click', () => (document.fullscreenElement ? document.exitFullscreen() : root.requestFullscreen?.()));
  // Controls fade out while playing and the pointer rests.
  const wake = () => { root.classList.add('is-awake'); clearTimeout(idle); idle = setTimeout(() => root.classList.remove('is-awake'), 2200); };
  root.addEventListener('pointermove', wake);
  root.tabIndex = 0;
  root.addEventListener('keydown', e => {
    if (e.target === seek) return;
    if (e.key === ' ' || e.key === 'k') { e.preventDefault(); toggle(); }
    else if (e.key === 'ArrowRight') video.currentTime = Math.min(video.duration || 0, video.currentTime + 5);
    else if (e.key === 'ArrowLeft') video.currentTime = Math.max(0, video.currentTime - 5);
    else if (e.key === 'f') root.querySelector('[data-cp-full]').click();
    else if (e.key === 'm') mute.click();
    wake();
  });
})();
