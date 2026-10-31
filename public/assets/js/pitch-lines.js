/* Pitch markings draw themselves stroke by stroke with the scroll; the footer pitch completes as the page ends.
   Without GSAP or with reduced motion everything stays fully drawn. */
(() => {
  const gsap = window.gsap, ScrollTrigger = window.ScrollTrigger, motion = window.WCUMotion || {};
  if (!gsap || !ScrollTrigger || motion.reduced) return;
  const footer = document.getElementById('site-footer');
  const footerRange = { trigger: footer, start: 'top bottom', end: 'bottom bottom', scrub: 0.6 };

  document.querySelectorAll('[data-pitch-draw]').forEach(svg => {
    const inFooter = footer?.contains(svg);
    const strokes = svg.querySelectorAll('[pathLength]'), spots = svg.querySelectorAll('.pitch-spot');
    gsap.set(strokes, { strokeDasharray: 1, strokeDashoffset: 1 });
    gsap.timeline({ scrollTrigger: inFooter ? footerRange : { trigger: svg, start: 'top 95%', end: 'bottom 45%', scrub: 0.6 } })
      .to(strokes, { strokeDashoffset: 0, duration: 1, stagger: 0.12, ease: 'none' })
      .fromTo(spots, { scale: 0 }, { scale: 1, duration: 0.3, ease: 'back.out(3)' }, '-=0.3');
  });
})();
