// Simple acordeón JS para abrir/cerrar contenido

document.addEventListener('DOMContentLoaded', function () {
  const acordeonItems = document.querySelectorAll('.c-acordeon__item');
  acordeonItems.forEach(function (item) {
    const btn = item.querySelector('.c-acordeon__title');
    const content = item.querySelector('.c-acordeon__content');
    if (btn && content) {
      btn.addEventListener('click', function () {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', !expanded);
        if (expanded) {
          content.hidden = true;
        } else {
          content.hidden = false;
        }
      });
    }
  });
});
