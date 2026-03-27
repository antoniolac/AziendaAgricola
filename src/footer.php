</main>
<footer class="site-footer">
    <p>🌿 Azienda Agricola &mdash; Gestionale Interno &mdash; <?= date('Y') ?></p>
</footer>
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* MODAL */
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = document.getElementById(btn.getAttribute('data-modal-open'));
            if (overlay) overlay.classList.add('open');
        });
    });
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay')?.classList.remove('open');
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    /* CONFIRM */
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', e => {
            if (!confirm(btn.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    /* VENDITE — righe dinamiche + totale */
    const righeContainer = document.getElementById('righe-vendita');
    const btnAddRow      = document.getElementById('btn-add-row');
    const grandTotalEl   = document.getElementById('grand-total');

    if (!righeContainer) return;

    const fmt = n => '€ ' + n.toFixed(2).replace('.', ',');

    function updateRowTotal(row) {
        const qty   = parseFloat(row.querySelector('.qty')?.value)   || 0;
        const price = parseFloat(row.querySelector('.price')?.value) || 0;
        row.querySelector('.row-total').textContent = fmt(qty * price);
        updateGrandTotal();
    }

    function updateGrandTotal() {
        let sum = 0;
        righeContainer.querySelectorAll('.sale-row').forEach(row => {
            sum += (parseFloat(row.querySelector('.qty')?.value)   || 0)
                 * (parseFloat(row.querySelector('.price')?.value) || 0);
        });
        if (grandTotalEl) grandTotalEl.textContent = fmt(sum);
    }

    function attachRowEvents(row) {
        row.querySelector('select[name="prodotto_id[]"]')?.addEventListener('change', function () {
            const price = parseFloat(this.options[this.selectedIndex].getAttribute('data-price')) || 0;
            row.querySelector('.price').value = price.toFixed(2);
            updateRowTotal(row);
        });
        row.querySelector('.qty')?.addEventListener('input',   () => updateRowTotal(row));
        row.querySelector('.price')?.addEventListener('input', () => updateRowTotal(row));
        row.querySelector('.btn-remove-row')?.addEventListener('click', () => {
            row.remove();
            updateGrandTotal();
        });
    }

    righeContainer.querySelectorAll('.sale-row').forEach(attachRowEvents);

    btnAddRow?.addEventListener('click', () => {
        const first = righeContainer.querySelector('.sale-row');
        if (!first) return;
        const clone = first.cloneNode(true);
        clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        clone.querySelectorAll('input[type="number"]').forEach(i => {
            i.value = i.classList.contains('qty') ? '1' : '0';
        });
        clone.querySelector('.row-total').textContent = '€ 0,00';
        if (!clone.querySelector('.btn-remove-row')) {
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn btn--danger btn--sm btn-remove-row';
            del.textContent = '✕';
            del.style.alignSelf = 'flex-end';
            clone.appendChild(del);
        }
        righeContainer.appendChild(clone);
        attachRowEvents(clone);
    });
});
</script>
</body>
</html>