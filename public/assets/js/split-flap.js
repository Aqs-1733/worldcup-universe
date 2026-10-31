/* Split-flap scores: each digit sits on a flip tile and, when the score scrolls into view, rattles through a few
   numbers like an odometer before landing on the real value. Dashes and colons stay as they are; reduced motion
   leaves scores untouched. */
(() => {
  if (matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
  const scores = document.querySelectorAll('.match-tile .score-team > b, .big-score, .fixture-score');
  if (!scores.length) return;

  // Only direct text nodes are split, so penalty notes (<small>) and separators (<i>) keep their markup.
  const tilesOf = score => {
    const tiles = [];
    [...score.childNodes].forEach(node => {
      if (node.nodeType !== Node.TEXT_NODE || !/\d/.test(node.textContent)) return;
      const parts = document.createDocumentFragment();
      [...node.textContent].forEach(ch => {
        if (!/\d/.test(ch)) { parts.append(ch); return; }
        const tile = document.createElement('span');
        tile.className = 'flap'; tile.textContent = ch; tile.dataset.digit = ch;
        parts.append(tile); tiles.push(tile);
      });
      node.replaceWith(parts);
    });
    return tiles;
  };
  const roll = (tile, delay) => {
    const target = Number(tile.dataset.digit), steps = 5 + Math.floor(Math.random() * 4);
    let n = 0;
    const tick = () => {
      n += 1;
      tile.textContent = String((target - (steps - n) + 10) % 10);
      tile.classList.remove('is-flip'); void tile.offsetWidth; tile.classList.add('is-flip');
      if (n < steps) setTimeout(tick, 60 + n * 12);
    };
    setTimeout(tick, delay);
  };

  const seen = new IntersectionObserver(entries => entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    seen.unobserve(entry.target);
    entry.target._flaps.forEach((tile, i) => roll(tile, 120 + i * 140));
  }), { threshold: 0.6 });
  scores.forEach(score => { score._flaps = tilesOf(score); if (score._flaps.length) seen.observe(score); });
})();
