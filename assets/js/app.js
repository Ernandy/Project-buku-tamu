// Filter pilihan pejabat berdasarkan seksi yang dipilih
document.addEventListener('DOMContentLoaded', () => {
  const seksi = document.getElementById('seksi_id');
  const pejabat = document.getElementById('pejabat_id');
  if (!seksi || !pejabat) return;

  const semua = Array.from(pejabat.options).filter(o => o.value !== '');

  function filter() {
    pejabat.value = '';
    semua.forEach(o => { o.hidden = o.dataset.seksi !== seksi.value; o.disabled = o.hidden; });
  }
  seksi.addEventListener('change', filter);
  filter();
});
