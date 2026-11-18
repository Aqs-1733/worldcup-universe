/* Controls for local video files shown in the /watch shelf. */
(() => {
  const root = document.querySelector('[data-cinema]');
  if (!root) return;
  const status = document.querySelector('[data-cp-status]');
  const video = root.querySelector('[data-cp-video]');
  if (!video) return;
  const seek = root.querySelector('[data-cp-seek]');
  const time = root.querySelector('[data-cp-time]');
  const mute = root.querySelector('[data-cp-mute]');
  const full = root.querySelector('[data-cp-full]');
  const fmt = seconds => {
    if (!Number.isFinite(seconds)) return '0:00';
    const minutes = Math.floor(seconds / 60);
    return `${minutes}:${String(Math.floor(seconds % 60)).padStart(2, '0')}`;
  };
  let seeking = false;
  let idle = 0;
  const toggle = () => (video.paused ? video.play().catch(() => {
    if (status) status.textContent = '浏览器阻止了播放，请再点一次。';
  }) : video.pause());
  root.querySelectorAll('[data-cp-toggle]').forEach(button => button.addEventListener('click', toggle));
  video.addEventListener('click', toggle);
  video.addEventListener('play', () => root.classList.add('is-playing'));
  video.addEventListener('pause', () => root.classList.remove('is-playing'));
  video.addEventListener('timeupdate', () => {
    if (!seeking && video.duration) seek.value = String(Math.round((video.currentTime / video.duration) * 1000));
    time.textContent = `${fmt(video.currentTime)} / ${fmt(video.duration)}`;
    seek.style.setProperty('--fill', `${(seek.value / 10).toFixed(1)}%`);
  });
  video.addEventListener('loadedmetadata', () => { time.textContent = `0:00 / ${fmt(video.duration)}`; });
  video.addEventListener('error', () => { root.classList.add('has-error'); if (status) status.textContent = '视频文件无法解码。'; });
  seek.addEventListener('input', () => {
    seeking = true;
    seek.style.setProperty('--fill', `${seek.value / 10}%`);
    if (video.duration) time.textContent = `${fmt((seek.value / 1000) * video.duration)} / ${fmt(video.duration)}`;
  });
  seek.addEventListener('change', () => { if (video.duration) video.currentTime = (seek.value / 1000) * video.duration; seeking = false; });
  mute.addEventListener('click', () => { video.muted = !video.muted; mute.textContent = video.muted ? '🔇' : '🔊'; });
  full.addEventListener('click', () => (document.fullscreenElement ? document.exitFullscreen() : root.requestFullscreen?.()));
  const wake = () => { root.classList.add('is-awake'); clearTimeout(idle); idle = setTimeout(() => root.classList.remove('is-awake'), 2200); };
  root.addEventListener('pointermove', wake);
  root.tabIndex = 0;
  root.addEventListener('keydown', event => {
    if (event.target === seek) return;
    if (event.key === ' ' || event.key === 'k') { event.preventDefault(); toggle(); }
    else if (event.key === 'ArrowRight') video.currentTime = Math.min(video.duration || 0, video.currentTime + 5);
    else if (event.key === 'ArrowLeft') video.currentTime = Math.max(0, video.currentTime - 5);
    else if (event.key === 'f') full.click();
    else if (event.key === 'm') mute.click();
    wake();
  });
})();
