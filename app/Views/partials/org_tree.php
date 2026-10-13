<?php
/**
 * Bagan struktur organisasi (pohon, pan/zoom), JS + SVG murni tanpa library.
 * Dipakai halaman admin (Bootstrap 4) dan publik (Bootstrap 5), jadi semua
 * style memakai prefix .ot- dan tidak bergantung pada class Bootstrap.
 *
 * Expects: $tree = ['nodes' => [...], 'edges' => [...]] dari
 * StrukturOrganisasiModel::tree().
 */
$__otId = 'ot' . substr(md5(uniqid('', true)), 0, 8);
?>
<style>
    .ot-wrap { position: relative; border: 1px solid #dee2e6; border-radius: .75rem; background: #f8f9fa; overflow: hidden; }
    .ot-viewport { height: 70vh; min-height: 420px; overflow: hidden; cursor: grab; touch-action: none; user-select: none; background-image: radial-gradient(#dee2e6 1px, transparent 1px); background-size: 20px 20px; }
    .ot-viewport.ot-dragging { cursor: grabbing; }
    .ot-stage { position: absolute; left: 0; top: 0; transform-origin: 0 0; }
    .ot-stage svg { position: absolute; left: 0; top: 0; overflow: visible; pointer-events: none; }
    .ot-edge { fill: none; stroke: #adb5bd; stroke-width: 2; }
    .ot-edge.ot-link { stroke: #6c8ebf; stroke-dasharray: 6 5; }
    .ot-node { position: absolute; box-sizing: border-box; display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #fff; border: 2px solid #adb5bd; border-radius: .75rem; box-shadow: 0 2px 6px rgba(0, 0, 0, .08); overflow: hidden; }
    .ot-node.ot-rw { border-color: #3b6fb6; }
    .ot-node.ot-rt { border-color: #2f9e6f; }
    .ot-avatar { flex: 0 0 40px; width: 40px; height: 40px; border-radius: 50%; color: #fff; font-weight: 700; font-size: 18px; display: flex; align-items: center; justify-content: center; }
    .ot-rw .ot-avatar { background: #3b6fb6; }
    .ot-rt .ot-avatar { background: #2f9e6f; }
    .ot-body { min-width: 0; line-height: 1.25; }
    .ot-nama { font-weight: 700; font-size: 14px; color: #212529; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ot-jabatan { font-size: 12px; color: #495057; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ot-meta { font-size: 11px; color: #868e96; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ot-rangkap { display: inline-block; margin-top: 3px; padding: 0 6px; font-size: 10px; border-radius: 10px; background: #fff3cd; color: #856404; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ot-tools { position: absolute; right: 10px; top: 10px; display: flex; gap: 4px; z-index: 2; }
    .ot-tools button { width: 32px; height: 32px; border: 1px solid #ced4da; border-radius: .4rem; background: #fff; color: #343a40; font-size: 16px; line-height: 1; cursor: pointer; }
    .ot-tools button:hover { background: #e9ecef; }
    .ot-legend { position: absolute; left: 10px; bottom: 8px; z-index: 2; font-size: 12px; color: #495057; background: rgba(255, 255, 255, .9); border-radius: .4rem; padding: 3px 8px; }
    .ot-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin: 0 4px 0 8px; }
    .ot-legend i:first-child { margin-left: 0; }
</style>

<div class="ot-wrap" id="<?= $__otId ?>">
    <div class="ot-tools">
        <button type="button" data-ot="in" title="Perbesar" aria-label="Perbesar">+</button>
        <button type="button" data-ot="out" title="Perkecil" aria-label="Perkecil">&minus;</button>
        <button type="button" data-ot="fit" title="Sesuaikan ke layar" aria-label="Sesuaikan ke layar">&#8634;</button>
    </div>
    <div class="ot-viewport"><div class="ot-stage"></div></div>
    <div class="ot-legend"><i style="background:#3b6fb6"></i>RW<i style="background:#2f9e6f"></i>RT &middot; seret untuk menggeser, Ctrl + scroll untuk zoom</div>
</div>

<script>
(function () {
    var data = <?= json_encode($tree, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var wrap = document.getElementById('<?= $__otId ?>');
    var viewport = wrap.querySelector('.ot-viewport');
    var stage = wrap.querySelector('.ot-stage');
    var NW = 210, NH = 92, GX = 24, GY = 60;

    var byId = {}, children = {}, parentEdge = {};
    data.nodes.forEach(function (n) { byId[n.id] = n; children[n.id] = []; });
    data.edges.forEach(function (e) {
        if (byId[e.from] && byId[e.to] && !parentEdge[e.to]) {
            children[e.from].push(e.to);
            parentEdge[e.to] = e;
        }
    });

    // Prune cycles: every node is visited once; leftovers become extra roots.
    var visited = {}, kids = {};
    function walk(id) {
        visited[id] = true;
        kids[id] = [];
        children[id].forEach(function (k) {
            if (!visited[k]) { kids[id].push(k); walk(k); }
        });
    }
    var roots = data.nodes.filter(function (n) { return !parentEdge[n.id]; }).map(function (n) { return n.id; });
    roots.forEach(walk);
    data.nodes.forEach(function (n) { if (!visited[n.id]) { roots.push(n.id); walk(n.id); } });

    var widths = {};
    function width(id) {
        var sum = 0;
        kids[id].forEach(function (k) { sum += width(k); });
        sum += GX * Math.max(0, kids[id].length - 1);
        return (widths[id] = Math.max(NW, sum));
    }

    var pos = {}, totalW = 0, totalH = 0;
    function place(id, left, depth) {
        var w = widths[id];
        pos[id] = { x: left + (w - NW) / 2, y: depth * (NH + GY) };
        totalH = Math.max(totalH, pos[id].y + NH);
        var kidsW = 0;
        kids[id].forEach(function (k) { kidsW += widths[k]; });
        kidsW += GX * Math.max(0, kids[id].length - 1);
        var x = left + (w - kidsW) / 2;
        kids[id].forEach(function (k) { place(k, x, depth + 1); x += widths[k] + GX; });
    }
    var cursor = 0;
    roots.forEach(function (r) { width(r); place(r, cursor, 0); cursor += widths[r] + GX * 2; });
    totalW = Math.max(NW, cursor - GX * 2);

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) { e.className = cls; }
        if (text !== undefined) { e.textContent = text; }
        return e;
    }

    var NS = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('width', totalW);
    svg.setAttribute('height', totalH);
    data.nodes.forEach(function (n) {
        kids[n.id].forEach(function (k) {
            var p = pos[n.id], c = pos[k];
            var x1 = p.x + NW / 2, y1 = p.y + NH, x2 = c.x + NW / 2, y2 = c.y, my = y1 + GY / 2;
            var path = document.createElementNS(NS, 'path');
            path.setAttribute('d', 'M' + x1 + ',' + y1 + ' V' + my + ' H' + x2 + ' V' + y2);
            path.setAttribute('class', 'ot-edge' + (parentEdge[k].link ? ' ot-link' : ''));
            svg.appendChild(path);
        });
    });
    stage.style.width = totalW + 'px';
    stage.style.height = totalH + 'px';
    stage.appendChild(svg);

    data.nodes.forEach(function (n) {
        var box = el('div', 'ot-node ot-' + n.level);
        box.style.left = pos[n.id].x + 'px';
        box.style.top = pos[n.id].y + 'px';
        box.style.width = NW + 'px';
        box.style.height = NH + 'px';
        box.title = n.nama + ' - ' + n.jabatan;
        box.appendChild(el('div', 'ot-avatar', (n.nama || '?').charAt(0).toUpperCase()));
        var body = el('div', 'ot-body');
        body.appendChild(el('div', 'ot-nama', n.nama));
        body.appendChild(el('div', 'ot-jabatan', n.jabatan));
        if (n.nama_rt) { body.appendChild(el('div', 'ot-meta', n.nama_rt)); }
        if (n.rangkap && n.rangkap.length) { body.appendChild(el('span', 'ot-rangkap', 'Rangkap: ' + n.rangkap.join(', '))); }
        box.appendChild(body);
        stage.appendChild(box);
    });

    var s = 1, tx = 0, ty = 0;
    function apply() { stage.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + s + ')'; }
    function zoomAt(cx, cy, factor) {
        var ns = Math.min(2, Math.max(0.2, s * factor));
        tx = cx - (cx - tx) * (ns / s);
        ty = cy - (cy - ty) * (ns / s);
        s = ns;
        apply();
    }
    function fit() {
        var vw = viewport.clientWidth, vh = viewport.clientHeight;
        s = Math.min(1, (vw - 32) / totalW, (vh - 32) / totalH);
        s = Math.max(0.2, s);
        tx = (vw - totalW * s) / 2;
        ty = Math.max(16, (vh - totalH * s) / 2);
        apply();
    }
    fit();
    window.addEventListener('resize', fit);

    wrap.querySelector('.ot-tools').addEventListener('click', function (e) {
        var act = e.target.getAttribute('data-ot');
        var cx = viewport.clientWidth / 2, cy = viewport.clientHeight / 2;
        if (act === 'in') { zoomAt(cx, cy, 1.2); }
        if (act === 'out') { zoomAt(cx, cy, 1 / 1.2); }
        if (act === 'fit') { fit(); }
    });

    viewport.addEventListener('wheel', function (e) {
        if (!e.ctrlKey && !e.metaKey) { return; }
        e.preventDefault();
        var r = viewport.getBoundingClientRect();
        zoomAt(e.clientX - r.left, e.clientY - r.top, e.deltaY < 0 ? 1.1 : 1 / 1.1);
    }, { passive: false });

    var drag = null;
    viewport.addEventListener('pointerdown', function (e) {
        drag = { x: e.clientX, y: e.clientY, tx: tx, ty: ty };
        viewport.setPointerCapture(e.pointerId);
        viewport.classList.add('ot-dragging');
    });
    viewport.addEventListener('pointermove', function (e) {
        if (!drag) { return; }
        tx = drag.tx + (e.clientX - drag.x);
        ty = drag.ty + (e.clientY - drag.y);
        apply();
    });
    function endDrag() { drag = null; viewport.classList.remove('ot-dragging'); }
    viewport.addEventListener('pointerup', endDrag);
    viewport.addEventListener('pointercancel', endDrag);
})();
</script>
