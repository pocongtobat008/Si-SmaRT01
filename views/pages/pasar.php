<!-- Halaman Kelola UMKM/Pasar -->
<div id="page-pasar" class="page-content hidden">
    <div class="sub-nav-tabs" style="margin-bottom: 24px; display: flex; gap: 8px;">
        <button class="sub-nav-tab active" onclick="switchPasarTab('tab-produk-warga', this)"><i data-lucide="shopping-bag"></i> Produk Warga</button>
        <button class="sub-nav-tab" onclick="switchPasarTab('tab-slider-pasar', this)"><i data-lucide="image"></i> Slider Promosi</button>
    </div>

    <!-- TAB PRODUK WARGA -->
    <div id="tab-produk-warga" class="pasar-content-tab">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-bold text-[var(--text-color)]">Kelola UMKM Warga</h2>
            <p class="text-sm text-[var(--text-secondary-color)] mt-1">Daftar produk dan jasa jualan warga</p>
        </div>
        <button onclick="openPasarModal()" class="px-6 py-3 bg-[var(--accent-color)] text-white font-bold rounded-xl hover:shadow-lg hover:shadow-emerald-500/30 transition-all flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i> Tambah Produk Baru
        </button>
    </div>

    <div class="glass-card p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="pasar-table">
                <thead>
                    <tr class="border-b border-[var(--border-color)]">
                        <th class="p-4 font-semibold text-[var(--text-secondary-color)]">Foto</th>
                        <th class="p-4 font-semibold text-[var(--text-secondary-color)]">Nama Produk</th>
                        <th class="p-4 font-semibold text-[var(--text-secondary-color)]">Harga</th>
                        <th class="p-4 font-semibold text-[var(--text-secondary-color)]">Penjual & WA</th>
                        <th class="p-4 font-semibold text-[var(--text-secondary-color)]">Status</th>
                        <th class="p-4 font-semibold text-[var(--text-secondary-color)] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="pasar-tbody" class="divide-y divide-[var(--border-color)]">
                    <!-- Data akan dimuat dengan JS -->
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <!-- TAB SLIDER PROMOSI -->
    <div id="tab-slider-pasar" class="pasar-content-tab hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-[var(--text-color)]">Slider Promo Pasar</h2>
                <p class="text-sm text-[var(--text-secondary-color)] mt-1">Atur banner promosi berjalan di header pasar</p>
            </div>
            <button onclick="openPasarSliderModal()" class="px-6 py-3 bg-[var(--accent-color)] text-white font-bold rounded-xl hover:shadow-lg hover:shadow-emerald-500/30 transition-all flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5"></i> Tambah Banner
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="pasar-slider-container">
            <!-- Slider Cards diisi via JS -->
        </div>
    </div>
</div>

<!-- Modal Form Pasar -->
<div id="pasar-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] hidden flex items-center justify-center p-4">
    <div class="bg-[var(--secondary-bg)] w-full max-w-2xl rounded-3xl shadow-2xl border border-[var(--border-color)] flex flex-col overflow-hidden max-h-[90vh]">
        <div class="p-6 border-b border-[var(--border-color)] flex justify-between items-center">
            <h3 class="text-xl font-bold text-[var(--text-color)]" id="pasar-modal-title">Tambah Produk</h3>
            <button onclick="closePasarModal()" class="text-[var(--text-secondary-color)] hover:text-red-500 transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <form id="pasar-form" onsubmit="savePasar(event)">
                <input type="hidden" id="pasar_id" name="id" value="0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-semibold text-[var(--text-secondary-color)]">Nama Produk / Jasa *</label>
                        <input type="text" id="pasar_nama" name="nama_produk" required class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] focus:border-[var(--accent-color)] outline-none transition-all text-[var(--text-color)]">
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-semibold text-[var(--text-secondary-color)]">Deskripsi Singkat</label>
                        <textarea id="pasar_deskripsi" name="deskripsi" rows="3" class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] focus:border-[var(--accent-color)] outline-none transition-all text-[var(--text-color)]"></textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-[var(--text-secondary-color)]">Harga (Rp) *</label>
                        <input type="number" id="pasar_harga" name="harga" required class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] focus:border-[var(--accent-color)] outline-none transition-all text-[var(--text-color)]">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-[var(--text-secondary-color)]">Kategori</label>
                        <select id="pasar_kategori" name="kategori" class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] focus:border-[var(--accent-color)] outline-none transition-all text-[var(--text-color)] select-custom">
                            <option value="Makanan">Makanan & Minuman</option>
                            <option value="Jasa">Jasa & Layanan</option>
                            <option value="Barang">Barang & Retail</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-[var(--text-secondary-color)]">Nama Penjual / Toko *</label>
                        <input type="text" id="pasar_penjual" name="penjual_nama" required class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] focus:border-[var(--accent-color)] outline-none transition-all text-[var(--text-color)]">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-[var(--text-secondary-color)]">Nomor WhatsApp *</label>
                        <input type="text" id="pasar_wa" name="no_wa" placeholder="0812..." required class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] focus:border-[var(--accent-color)] outline-none transition-all text-[var(--text-color)]">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-[var(--text-secondary-color)]">Status</label>
                        <select id="pasar_status" name="status" class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] focus:border-[var(--accent-color)] outline-none transition-all text-[var(--text-color)] select-custom">
                            <option value="Tersedia">Tersedia</option>
                            <option value="Habis">Habis</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-[var(--text-secondary-color)]">URL Foto Link (Opsional)</label>
                        <input type="text" id="pasar_foto" name="foto" placeholder="https://..." class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] focus:border-[var(--accent-color)] outline-none transition-all text-[var(--text-color)]">
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" onclick="closePasarModal()" class="px-6 py-3 rounded-xl font-semibold bg-[var(--hover-bg)] text-[var(--text-color)] hover:bg-[var(--border-color)] transition-colors">Batal</button>
                    <button type="submit" class="px-6 py-3 rounded-xl font-bold bg-[var(--accent-color)] text-white shadow-lg shadow-emerald-500/30 hover:bg-emerald-600 transition-colors">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Form Slider -->
<div id="pasar-slider-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] hidden flex items-center justify-center p-4">
    <div class="bg-[var(--secondary-bg)] w-full max-w-xl rounded-3xl shadow-2xl border border-[var(--border-color)] flex flex-col overflow-hidden max-h-[90vh]">
        <div class="p-6 border-b border-[var(--border-color)] flex justify-between items-center">
            <h3 class="text-xl font-bold text-[var(--text-color)]" id="slider-modal-title">Tambah Banner Promo</h3>
            <button onclick="closePasarSliderModal()" class="text-[var(--text-secondary-color)] hover:text-red-500 transition-colors"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <form id="pasar-slider-form" onsubmit="savePasarSlider(event)">
                <input type="hidden" id="sl_id" name="id" value="0">
                <div class="space-y-4">
                    <div><label class="text-sm font-semibold">Judul Banner *</label><input type="text" id="sl_title" name="title" required class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] text-[var(--text-color)] mt-1"></div>
                    <div><label class="text-sm font-semibold">Sub-Judul</label><input type="text" id="sl_subtitle" name="subtitle" class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] text-[var(--text-color)] mt-1"></div>
                    <div class="flex gap-4">
                        <div class="flex-1"><label class="text-sm font-semibold">Teks Badge</label><input type="text" id="sl_badge" name="badge_text" placeholder="Promo Spesial" class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] text-[var(--text-color)] mt-1"></div>
                        <div class="flex-1"><label class="text-sm font-semibold">Ikon Badge</label><input type="text" id="sl_icon" name="badge_icon" placeholder="fa-fire" class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] text-[var(--text-color)] mt-1"></div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="text-sm font-semibold">Tema Warna</label>
                            <select id="sl_theme" name="theme_color" class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] text-[var(--text-color)] select-custom mt-1">
                                <option value="emerald">Hijau (Emerald)</option><option value="blue">Biru (Blue)</option>
                                <option value="orange">Oranye (Orange)</option><option value="purple">Ungu (Purple)</option>
                            </select>
                        </div>
                        <div class="flex-1"><label class="text-sm font-semibold">Urutan</label><input type="number" id="sl_urutan" name="urutan" value="1" class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] text-[var(--text-color)] mt-1"></div>
                    </div>
                    <div><label class="text-sm font-semibold">URL Gambar *</label><input type="text" id="sl_image" name="image" placeholder="https://..." required class="w-full px-4 py-3 rounded-xl bg-[var(--primary-bg)] border border-[var(--border-color)] text-[var(--text-color)] mt-1"></div>
                </div>
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" onclick="closePasarSliderModal()" class="px-6 py-3 rounded-xl bg-[var(--hover-bg)] text-[var(--text-color)] hover:bg-[var(--border-color)] transition-colors">Batal</button>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-[var(--accent-color)] text-white font-bold hover:bg-emerald-600 transition-colors">Simpan Banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function initPasar() { loadPasarData(); loadPasarSliders(); }
    
    function switchPasarTab(tabId, btn) {
        document.querySelectorAll('.pasar-content-tab').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('#page-pasar .sub-nav-tab').forEach(el => el.classList.remove('active'));
        document.getElementById(tabId).classList.remove('hidden');
        btn.classList.add('active');
    }

    function loadPasarData() {
        const tbody = document.getElementById('pasar-tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4">Memuat data...</td></tr>';
        fetch('views/pages/get_produk.php').then(r => r.json()).then(res => {
            if(res.status === 'success') {
                if(res.data.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="text-center p-8 text-[var(--text-secondary-color)]">Belum ada produk yang ditambahkan.</td></tr>'; return; }
                let html = '';
                res.data.forEach(p => {
                    const badge = p.status === 'Tersedia' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700';
                    const foto = p.foto ? `<img src="${p.foto}" class="w-12 h-12 rounded-xl object-cover shadow-sm">` : `<div class="w-12 h-12 rounded-xl bg-[var(--hover-bg)] flex items-center justify-center text-[var(--text-secondary-color)]"><i data-lucide="image"></i></div>`;
                    html += `<tr class="hover:bg-[var(--hover-bg)] transition-colors border-b border-[var(--border-color)] last:border-0"><td class="p-4">${foto}</td><td class="p-4"><div class="font-bold text-[var(--text-color)]">${p.nama_produk}</div><div class="text-xs text-[var(--text-secondary-color)]">${p.kategori}</div></td><td class="p-4 font-bold text-[var(--accent-color)]">Rp ${parseInt(p.harga).toLocaleString('id-ID')}</td><td class="p-4"><div class="font-medium text-[var(--text-color)]">${p.penjual_nama}</div><div class="text-xs text-[var(--text-secondary-color)]">${p.no_wa}</div></td><td class="p-4"><span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-lg ${badge}">${p.status}</span></td><td class="p-4 text-right whitespace-nowrap"><button onclick='editPasar(${JSON.stringify(p).replace(/'/g, "&#39;")})' class="p-2.5 text-blue-500 hover:bg-blue-50 hover:shadow shadow-blue-500/20 rounded-xl transition-all mr-2"><i data-lucide="edit-2" class="w-4 h-4"></i></button><button onclick="deletePasar(${p.id})" class="p-2.5 text-red-500 hover:bg-red-50 hover:shadow shadow-red-500/20 rounded-xl transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button></td></tr>`;
                }); tbody.innerHTML = html; lucide.createIcons();
            }
        });
    }
    function openPasarModal() { document.getElementById('pasar-form').reset(); document.getElementById('pasar_id').value = 0; document.getElementById('pasar-modal-title').innerText = 'Tambah Produk'; document.getElementById('pasar-modal').classList.remove('hidden'); }
    function closePasarModal() { document.getElementById('pasar-modal').classList.add('hidden'); }
    function editPasar(p) { document.getElementById('pasar_id').value = p.id; document.getElementById('pasar_nama').value = p.nama_produk; document.getElementById('pasar_deskripsi').value = p.deskripsi; document.getElementById('pasar_harga').value = p.harga; document.getElementById('pasar_kategori').value = p.kategori; document.getElementById('pasar_penjual').value = p.penjual_nama; document.getElementById('pasar_wa').value = p.no_wa; document.getElementById('pasar_status').value = p.status; document.getElementById('pasar_foto').value = p.foto; document.getElementById('pasar-modal-title').innerText = 'Edit Produk'; document.getElementById('pasar-modal').classList.remove('hidden'); }
    function savePasar(e) { e.preventDefault(); const fd = new FormData(e.target); showLoading('Menyimpan...'); fetch('views/pages/save_produk.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => { if(res.status === 'success') { showToast(res.message); closePasarModal(); loadPasarData(); } else { showToast(res.message, 'error'); } }); }
    function deletePasar(id) { if(confirm('Hapus produk ini secara permanen?')) { const fd = new FormData(); fd.append('id', id); fetch('views/pages/delete_produk.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => { if(res.status === 'success') { showToast('Terhapus'); loadPasarData(); } }); } }

    // Slider JS Logic
    function loadPasarSliders() {
        fetch('views/pages/get_sliders.php').then(r => r.json()).then(res => {
            if(res.status === 'success') {
                let html = '';
                res.data.forEach(s => {
                    html += `<div class="glass-card overflow-hidden p-0 relative group"><img src="${s.image}" class="w-full h-40 object-cover"><div class="p-4"><span class="text-xs font-bold px-2 py-1 bg-${s.theme_color}-100 text-${s.theme_color}-700 rounded-md mb-2 inline-block"><i class="fas ${s.badge_icon} mr-1"></i>${s.badge_text}</span><h4 class="font-bold text-lg leading-tight mb-1 text-[var(--text-color)]">${s.title}</h4><p class="text-sm text-[var(--text-secondary-color)] truncate">${s.subtitle}</p></div><div class="absolute top-2 right-2 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity"><button onclick='editPasarSlider(${JSON.stringify(s).replace(/'/g, "&#39;")})' class="p-2 bg-blue-500 text-white rounded-lg shadow-lg hover:scale-110 transition"><i data-lucide="edit-2" class="w-4 h-4"></i></button><button onclick="deletePasarSlider(${s.id})" class="p-2 bg-red-500 text-white rounded-lg shadow-lg hover:scale-110 transition"><i data-lucide="trash-2" class="w-4 h-4"></i></button></div></div>`;
                }); document.getElementById('pasar-slider-container').innerHTML = html || '<p class="col-span-full text-center py-8 text-[var(--text-secondary-color)]">Belum ada banner promosi.</p>'; lucide.createIcons();
            }
        });
    }
    function openPasarSliderModal() { document.getElementById('pasar-slider-form').reset(); document.getElementById('sl_id').value = 0; document.getElementById('pasar-slider-modal').classList.remove('hidden'); }
    function closePasarSliderModal() { document.getElementById('pasar-slider-modal').classList.add('hidden'); }
    function editPasarSlider(s) { document.getElementById('sl_id').value = s.id; document.getElementById('sl_title').value = s.title; document.getElementById('sl_subtitle').value = s.subtitle; document.getElementById('sl_badge').value = s.badge_text; document.getElementById('sl_icon').value = s.badge_icon; document.getElementById('sl_theme').value = s.theme_color; document.getElementById('sl_urutan').value = s.urutan; document.getElementById('sl_image').value = s.image; document.getElementById('pasar-slider-modal').classList.remove('hidden'); }
    function savePasarSlider(e) { e.preventDefault(); const fd = new FormData(e.target); showLoading('Menyimpan...'); fetch('views/pages/save_slider.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => { if(res.status === 'success') { showToast('Banner Disimpan'); closePasarSliderModal(); loadPasarSliders(); } }); }
    function deletePasarSlider(id) { if(confirm('Hapus banner ini?')) { const fd = new FormData(); fd.append('id', id); fetch('views/pages/delete_slider.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => { if(res.status === 'success') { showToast('Dihapus'); loadPasarSliders(); } }); } }
</script>