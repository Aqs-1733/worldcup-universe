const root = document.documentElement;
const toggle = document.querySelector('#themeToggle');
const toast = document.querySelector('#toast');
const modal = document.querySelector('#matchModal');
const modalTitle = document.querySelector('#modalTitle');
let toastTimer;

const requestedTheme = new URLSearchParams(window.location.search).get('theme');
if (requestedTheme === 'light' || requestedTheme === 'dark') {
  root.dataset.theme = requestedTheme;
}

function showToast(message) {
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 2200);
}

toggle.addEventListener('click', () => {
  const next = root.dataset.theme === 'light' ? 'dark' : 'light';
  root.dataset.theme = next;
  showToast(next === 'light' ? '已切换浅色原型状态' : '已切换深色原型状态');
});

document.querySelectorAll('[data-toast]').forEach((element) => {
  element.addEventListener('click', () => showToast(element.dataset.toast));
});

document.querySelectorAll('[data-scroll]').forEach((element) => {
  element.addEventListener('click', () => document.querySelector(element.dataset.scroll)?.scrollIntoView({ behavior: 'smooth' }));
});

document.querySelectorAll('[data-match]').forEach((element) => {
  element.addEventListener('click', () => {
    modalTitle.textContent = element.dataset.match;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  });
});

document.querySelector('#modalClose').addEventListener('click', () => {
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
});
