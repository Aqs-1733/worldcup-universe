/* Pitch markings draw themselves stroke by stroke once they come into view, then stay complete.
   (They used to follow the scrollbar, which left a half-drawn line on screen whenever the reader stopped scrolling.)
   Without GSAP or with reduced motion everything stays fully drawn. */
(() => {
  const gsap = window.gsap, ScrollTrigger = window.ScrollTrigger, motion = window.WCUMotion || {};
  if (!gsap || !ScrollTrigger || motion.reduced) return;
  const footer = document.getElementById('site-footer');

  document.querySelectorAll('[data-pitch-draw]').forEach(svg => {
    const inFooter = footer?.contains(svg);
    const strokes = svg.querySelectorAll('[pathLength]'), spots = svg.querySelectorAll('.pitch-spot');
    gsap.set(strokes, { strokeDasharray: 1, strokeDashoffset: 1 });
    gsap.set(spots, { scale: 0 });
    gsap.timeline({ scrollTrigger: { trigger: inFooter ? footer : svg, start: 'top 92%', once: true } })
      .to(strokes, { strokeDashoffset: 0, duration: 0.9, stagger: 0.12, ease: 'power2.out' })
      .to(spots, { scale: 1, duration: 0.3, ease: 'back.out(3)' }, '-=0.3');
  });
})();
