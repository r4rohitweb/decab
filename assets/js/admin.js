document.addEventListener('submit', event => {
  if (event.target.matches('form[data-confirm]') && !window.confirm('Permanently delete this record?')) event.preventDefault();
});
