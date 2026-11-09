(function () {
  'use strict';
  var box = document.getElementById('cr-sync');
  if (!box) return;
  var api = box.dataset.api, ver = parseInt(box.dataset.version, 10);
  function tick() {
    fetch(api, { cache: 'no-store', credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.enviada) { location.reload(); return; }
        var c = document.getElementById('cr-count');
        if (c && d.listos !== undefined) c.textContent = d.listos + ' de ' + d.activos + ' conectados listos (mayoría necesaria: ' + d.necesarios + ')';
        var n = document.getElementById('cr-news');
        if (n && d.version !== ver) { n.hidden = false; n.querySelector('b').textContent = d.editor || 'Un compañero'; }
      })
      .catch(function () {})
      .then(function () { setTimeout(tick, 8000); });
  }
  setTimeout(tick, 8000);
})();
