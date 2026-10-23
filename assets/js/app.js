(function () {
  // Menú móvil
  var b = document.querySelector('[data-burger]'), n = document.querySelector('[data-nav]');
  if (b && n) b.addEventListener('click', function () {
    var open = n.classList.toggle('open'); b.setAttribute('aria-expanded', open);
  });

  // Cuenta regresiva: <span data-countdown="segundos" data-fin-text="...">
  document.querySelectorAll('[data-countdown]').forEach(function (el) {
    var resto = parseInt(el.dataset.countdown, 10);
    function pinta() {
      if (resto <= 0) { el.textContent = el.dataset.finText || '00:00'; el.classList.add('time-up'); return; }
      var m = Math.floor(resto / 60), s = resto % 60;
      el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      el.classList.toggle('time-low', resto <= 30);
      resto--; setTimeout(pinta, 1000);
    }
    pinta();
  });

  // Marcador en vivo
  var tb = document.querySelector('[data-live-scoreboard]');
  if (tb) {
    var api = tb.dataset.api;
    var cargar = function () {
      fetch(api, { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
        if (!d.visible) { tb.innerHTML = '<tr><td colspan="9" class="muted center">El marcador está oculto temporalmente por el facilitador.</td></tr>'; return; }
        var html = '';
        d.equipos.forEach(function (e) {
          html += '<tr class="' + (e.pos <= 3 ? 'top top-' + e.pos : '') + '"><td class="pos">' + e.medalla + '</td>' +
            '<td><span class="team"><i class="dot" style="--c:' + e.color + '"></i>' + e.nombre +
            (e.empate ? ' <small class="tag">empate</small>' : '') + '</span></td>';
          d.retos.forEach(function (r) { html += '<td class="num">' + (e.puntos[r] || 0) + '</td>'; });
          html += '<td class="num total">' + e.total + '</td></tr>';
        });
        tb.innerHTML = html;
        var up = document.querySelector('[data-updated]'); if (up) up.textContent = d.hora;
      }).catch(function () {});
    };
    setInterval(cargar, 5000);
  }

  // Confirmaciones
  document.querySelectorAll('[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) { if (!confirm(f.dataset.confirm)) ev.preventDefault(); });
  });
})();
