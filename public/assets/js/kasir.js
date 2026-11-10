/**
 * RestoKita - Kasir POS Interactive JavaScript
 * Menangani keranjang belanja interaktif dan sinkronisasi kalkulasi realtime:
 * - Subtotal
 * - Diskon bertingkat (0%, 10%, 15%, 20%)
 * - Dasar Pengenaan Pajak (DPP)
 * - PPN 10%
 * - Total Bayar & Hitung Kembalian
 */

document.addEventListener('DOMContentLoaded', function () {
    // State Keranjang Belanja di Memory
    let cart = [];

    // Konstanta Pajak dan Diskon (Sama persis dengan TransactionCalculator di PHP)
    const TAX_RATE = 0.10;

    // DOM Elements
    const cartItemsContainer = document.getElementById('cart-items');
    const emptyCartNotice = document.getElementById('empty-cart-notice');
    const cartSummaryContainer = document.getElementById('cart-summary');
    const cartInputPayload = document.getElementById('cart-input-payload');
    const btnSubmitOrder = document.getElementById('btn-submit-order');
    const btnClearCart = document.getElementById('btn-clear-cart');

    // Ringkasan Kalkulasi DOM
    const subtotalDisplay = document.getElementById('summary-subtotal');
    const discountRateDisplay = document.getElementById('summary-discount-rate');
    const discountDisplay = document.getElementById('summary-discount');
    const taxBaseDisplay = document.getElementById('summary-tax-base');
    const taxDisplay = document.getElementById('summary-tax');
    const totalDisplay = document.getElementById('summary-total');

    // Input Bayar & Kembalian
    const inputCash = document.getElementById('input-cash');
    const changeDisplay = document.getElementById('summary-change');
    const changeWrapper = document.getElementById('change-wrapper');
    const quickCashBtns = document.querySelectorAll('.btn-quick-cash');

    // Menu Filter & Search DOM
    const menuSearchInput = document.getElementById('menu-search');
    const categoryPills = document.querySelectorAll('.btn-cat-pill');
    const menuItems = document.querySelectorAll('.menu-grid-item');

    /**
     * Memformat angka menjadi format mata uang Rupiah.
     */
    function formatRupiah(number) {
        return 'Rp ' + Math.round(number).toLocaleString('id-ID');
    }

    /**
     * Menghitung persentase diskon bertingkat.
     */
    function getDiscountRate(subtotal) {
        if (subtotal >= 200000) return 0.20;
        if (subtotal >= 100000) return 0.15;
        if (subtotal >= 50000)  return 0.10;
        return 0.00;
    }

    /**
     * Memperbarui kalkulasi dan merender tampilan keranjang.
     */
    function renderCart() {
        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '';
            emptyCartNotice.classList.remove('d-none');
            cartSummaryContainer.classList.add('d-none');
            btnSubmitOrder.disabled = true;
            cartInputPayload.value = '';
            if (inputCash) inputCash.value = '';
            updateChange(0);
            return;
        }

        emptyCartNotice.classList.add('d-none');
        cartSummaryContainer.classList.remove('d-none');
        btnSubmitOrder.disabled = false;

        // Render baris item belanja
        let html = '';
        let subtotal = 0;

        cart.forEach((item, index) => {
            const itemSubtotal = item.harga * item.qty;
            subtotal += itemSubtotal;

            html += `
                <div class="cart-item-row d-flex align-items-center justify-content-between gap-2">
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-dark text-truncate small">${escapeHtml(item.nama)}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            ${formatRupiah(item.harga)} &times; ${item.qty} = <span class="fw-bold text-dark">${formatRupiah(itemSubtotal)}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary cart-qty-btn" data-action="decrease" data-id="${item.id}">
                            <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" class="form-control form-control-sm text-center cart-qty-input px-1 py-0" 
                               data-id="${item.id}" value="${item.qty}" min="1" max="${item.stok}" style="width: 44px; height: 28px; font-size: 0.8rem;">
                        <button type="button" class="btn btn-sm btn-outline-secondary cart-qty-btn" data-action="increase" data-id="${item.id}" ${item.qty >= item.stok ? 'disabled' : ''}>
                            <i class="bi bi-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm text-danger ms-1" data-action="remove" data-id="${item.id}" title="Hapus">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        cartItemsContainer.innerHTML = html;

        // Lakukan kalkulasi inti (sama seperti TransactionCalculator)
        const discountRate = getDiscountRate(subtotal);
        const discountAmount = Math.round(subtotal * discountRate);
        const taxBase = subtotal - discountAmount;
        const taxAmount = Math.round(taxBase * TAX_RATE);
        const grandTotal = taxBase + taxAmount;

        // Tampilkan ke ringkasan biaya
        subtotalDisplay.textContent = formatRupiah(subtotal);
        discountRateDisplay.textContent = `${Math.round(discountRate * 100)}%`;
        discountDisplay.textContent = `- ${formatRupiah(discountAmount)}`;
        taxBaseDisplay.textContent = formatRupiah(taxBase);
        taxDisplay.textContent = formatRupiah(taxAmount);
        totalDisplay.textContent = formatRupiah(grandTotal);

        // Update hidden JSON payload yang dikirim ke server saat form submit
        cartInputPayload.value = JSON.stringify(cart.map(i => ({
            id_produk: i.id,
            qty: i.qty
        })));

        // Update kalkulasi kembalian jika user sudah input nominal uang
        updateChange(grandTotal);
    }

    /**
     * Menghitung nilai kembalian uang kasir.
     */
    function updateChange(grandTotal) {
        if (!inputCash) return;

        const cashReceived = parseFloat(inputCash.value) || 0;
        const change = cashReceived - grandTotal;

        if (cashReceived === 0) {
            changeDisplay.textContent = 'Rp 0';
            changeWrapper.classList.remove('text-danger', 'text-success');
            changeWrapper.classList.add('text-muted');
        } else if (change < 0) {
            changeDisplay.textContent = `Kurang ${formatRupiah(Math.abs(change))}`;
            changeWrapper.classList.remove('text-success', 'text-muted');
            changeWrapper.classList.add('text-danger');
        } else {
            changeDisplay.textContent = formatRupiah(change);
            changeWrapper.classList.remove('text-danger', 'text-muted');
            changeWrapper.classList.add('text-success');
        }
    }

    /**
     * Menambahkan item ke keranjang belanja.
     */
    function addToCart(product) {
        const existing = cart.find(i => i.id === product.id);

        if (existing) {
            if (existing.qty < product.stok) {
                existing.qty++;
            } else {
                alert(`Maksimal stok tercapai untuk ${product.nama} (${product.stok} porsi).`);
                return;
            }
        } else {
            if (product.stok <= 0) {
                alert(`Maaf, stok ${product.nama} sedang habis.`);
                return;
            }
            cart.push({
                id: product.id,
                nama: product.nama,
                harga: product.harga,
                stok: product.stok,
                qty: 1
            });
        }

        renderCart();
    }

    /**
     * Update quantity item dalam keranjang.
     */
    function updateQty(id, newQty) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        if (newQty <= 0) {
            removeFromCart(id);
            return;
        }

        if (newQty > item.stok) {
            alert(`Jumlah melebihi stok yang tersedia (${item.stok} porsi).`);
            item.qty = item.stok;
        } else {
            item.qty = newQty;
        }

        renderCart();
    }

    /**
     * Menghapus item dari keranjang.
     */
    function removeFromCart(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    /**
     * Mengosongkan seluruh isi keranjang.
     */
    if (btnClearCart) {
        btnClearCart.addEventListener('click', function () {
            if (cart.length > 0 && confirm('Kosongkan semua pesanan saat ini?')) {
                cart = [];
                renderCart();
            }
        });
    }

    // Event Listener Klik Tombol Tambah Menu
    document.querySelectorAll('.btn-add-to-cart').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
            const id = parseInt(this.getAttribute('data-id'));
            const nama = this.getAttribute('data-nama');
            const harga = parseFloat(this.getAttribute('data-harga'));
            const stok = parseInt(this.getAttribute('data-stok'));

            addToCart({ id, nama, harga, stok });
        });
    });

    // Event Delegation untuk interaksi di dalam panel keranjang (Qty + / -, input, hapus)
    cartItemsContainer.addEventListener('click', function (e) {
        const target = e.target.closest('button');
        if (!target) return;

        const action = target.getAttribute('data-action');
        const id = parseInt(target.getAttribute('data-id'));
        const item = cart.find(i => i.id === id);
        if (!item) return;

        if (action === 'increase') {
            updateQty(id, item.qty + 1);
        } else if (action === 'decrease') {
            updateQty(id, item.qty - 1);
        } else if (action === 'remove') {
            removeFromCart(id);
        }
    });

    cartItemsContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('cart-qty-input')) {
            const id = parseInt(e.target.getAttribute('data-id'));
            const val = parseInt(e.target.value) || 1;
            updateQty(id, val);
        }
    });

    // Realtime input uang cash
    if (inputCash) {
        inputCash.addEventListener('input', function () {
            const subtotal = cart.reduce((sum, i) => sum + (i.harga * i.qty), 0);
            const discountRate = getDiscountRate(subtotal);
            const discountAmount = Math.round(subtotal * discountRate);
            const taxBase = subtotal - discountAmount;
            const grandTotal = taxBase + Math.round(taxBase * TAX_RATE);
            updateChange(grandTotal);
        });
    }

    // Tombol Uang Cepat (Quick Cash Buttons)
    quickCashBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const val = this.getAttribute('data-val');
            const subtotal = cart.reduce((sum, i) => sum + (i.harga * i.qty), 0);
            const discountRate = getDiscountRate(subtotal);
            const discountAmount = Math.round(subtotal * discountRate);
            const taxBase = subtotal - discountAmount;
            const grandTotal = taxBase + Math.round(taxBase * TAX_RATE);

            if (val === 'pas') {
                inputCash.value = grandTotal;
            } else {
                inputCash.value = val;
            }
            updateChange(grandTotal);
        });
    });

    // Filter Kategori Tombol Pill
    categoryPills.forEach(pill => {
        pill.addEventListener('click', function () {
            categoryPills.forEach(p => p.classList.remove('active', 'btn-resto'));
            categoryPills.forEach(p => p.classList.add('btn-outline-secondary'));

            this.classList.remove('btn-outline-secondary');
            this.classList.add('active', 'btn-resto');

            const selectedCat = this.getAttribute('data-category');
            filterMenuGrid(selectedCat, menuSearchInput.value.toLowerCase());
        });
    });

    // Pencarian Menu Realtime
    if (menuSearchInput) {
        menuSearchInput.addEventListener('input', function () {
            const activePill = document.querySelector('.btn-cat-pill.active');
            const selectedCat = activePill ? activePill.getAttribute('data-category') : 'all';
            filterMenuGrid(selectedCat, this.value.toLowerCase());
        });
    }

    /**
     * Memfilter kartu menu berdasarkan kategori dan teks pencarian.
     */
    function filterMenuGrid(category, searchKeyword) {
        let visibleCount = 0;
        menuItems.forEach(item => {
            const itemCat = item.getAttribute('data-category');
            const itemName = item.getAttribute('data-nama').toLowerCase();

            const matchCat = (category === 'all' || itemCat === category);
            const matchSearch = itemName.includes(searchKeyword);

            if (matchCat && matchSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        const noResultEl = document.getElementById('no-menu-results');
        if (noResultEl) {
            if (visibleCount === 0) {
                noResultEl.classList.remove('d-none');
            } else {
                noResultEl.classList.add('d-none');
            }
        }
    }

    /**
     * Fungsi sanitasi HTML sederhana untuk mencegah injection XSS pada DOM rendering.
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
