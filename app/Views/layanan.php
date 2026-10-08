<?php
$__rt    = current_rt();
$__rtWa  = ($__rt !== null && !empty($__rt->no_wa)) ? $__rt->no_wa : '6283869281843';
$__rtNama = $__rt !== null ? $__rt->nama : 'RT 29';
$__prefix = empty($slug) ? '' : $slug . '/';
?>
<?= view('includes/nav-white') ?>

<section class="page-section bg-light detail-page-section" id="layanan" style="min-height: 85vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7">

                <div class="card card-primary border-0 shadow-lg p-3">
                    <div class="card-body">
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= esc(session()->getFlashdata('error')) ?>
                            </div>
                        <?php endif ?>

                        <h3 class="my-4">Surat Keterangan</h3>

                        <?php if ($langkah === 1): ?>
                            <!-- Langkah 1: alamat + PIN -->
                            <p class="text-muted small">Langkah 1 dari 3 &middot; Pilih alamat rumah dan masukkan PIN rumah Anda.</p>

                            <?php if (empty($alamats)): ?>
                                <div class="alert alert-warning">Belum ada alamat yang bisa dipakai untuk layanan ini. Silakan hubungi pengurus <?= esc($__rtNama) ?>.</div>
                            <?php else: ?>
                                <?= form_open($__prefix . 'layanan/verifikasi', ['autocomplete' => 'off']) ?>
                                <div class="form-group">
                                    <label class="mb-2 small" for="cari-alamat">Alamat Rumah</label>
                                    <input type="text" id="cari-alamat" class="form-control mb-2" placeholder="Ketik untuk mencari alamat...">
                                    <select name="id_alamat" id="id_alamat" class="form-control" size="6" required>
                                        <?php foreach ($alamats as $a): ?>
                                            <option value="<?= (int) $a->id_alamat ?>"><?= esc($a->alamat) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="form-group mt-3">
                                    <label class="mb-2 small" for="pin">PIN rumah di <?= esc($__rtNama) ?> &nbsp; <a target="_blank" href="https://wa.me/<?= esc($__rtWa) ?>" class="small">Lupa PIN Anda?</a></label>
                                    <input type="password" id="pin" name="pin" class="form-control" placeholder="PIN rumah" inputmode="numeric" autocomplete="off" required>
                                </div>

                                <button type="submit" style="border-radius: 2rem" class="btn btn-primary py-3 w-100 mt-3">Lanjut</button>
                                <?= form_close() ?>

                                <script>
                                    (function () {
                                        var select = document.getElementById('id_alamat');
                                        var search = document.getElementById('cari-alamat');
                                        var all = Array.prototype.map.call(select.options, function (o) {
                                            return {value: o.value, text: o.text};
                                        });
                                        search.addEventListener('input', function () {
                                            var q = search.value.trim().toLowerCase();
                                            select.innerHTML = '';
                                            all.forEach(function (item) {
                                                if (q === '' || item.text.toLowerCase().indexOf(q) !== -1) {
                                                    var o = document.createElement('option');
                                                    o.value = item.value;
                                                    o.textContent = item.text;
                                                    select.appendChild(o);
                                                }
                                            });
                                            if (select.options.length === 1) {
                                                select.selectedIndex = 0;
                                            }
                                        });
                                    })();
                                </script>
                            <?php endif ?>

                        <?php elseif ($langkah === 2): ?>
                            <!-- Langkah 2: pilih pemohon -->
                            <p class="text-muted small">Langkah 2 dari 3 &middot; Siapa yang mengajukan surat dari <strong><?= esc($alamat->alamat) ?></strong>?</p>

                            <?php if (empty($anggota)): ?>
                                <div class="alert alert-warning">Belum ada warga aktif yang terdaftar di alamat ini. Silakan hubungi pengurus <?= esc($__rtNama) ?>.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($anggota as $w): ?>
                                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= base_url($__prefix . 'layanan/form/' . (int) $w->id_warga) ?>">
                                            <span><?= esc($w->nama_warga) ?></span>
                                            <?php if (!empty($w->status_keluarga)): ?>
                                                <small class="text-muted"><?= esc($w->status_keluarga) ?></small>
                                            <?php endif ?>
                                        </a>
                                    <?php endforeach ?>
                                </div>
                            <?php endif ?>
                            <a class="d-block mt-3 small" href="<?= base_url($__prefix . 'layanan') ?>">&larr; Ganti alamat</a>

                        <?php else: ?>
                            <!-- Langkah 3: formulir -->
                            <p class="text-muted small">Langkah 3 dari 3 &middot; Periksa data di bawah ini. Anda boleh mengubahnya bila ada yang tidak sesuai; RT akan membandingkannya dengan data warga.</p>

                            <div class="row">
                                <div class="col-md-6 small">
                                    Hal : Permohonan serta Pernyataan Kebenaran &amp; Keabsahan Dokumen.
                                </div>
                                <div style="text-align: right" class="col small">
                                    Sleman, <?= date('d-m-Y') ?><br>
                                    Kepada, Yth. Lurah Minomartani<br>
                                    di Minomartani
                                </div>
                            </div>

                            <div class="row my-4">
                                <div class="col-md-6">
                                    Dengan hormat, <br>
                                    Yang bertanda tangan dibawah ini,
                                </div>
                            </div>

                            <?php
                            $tgl = (string) $warga->tanggal_lahir;
                            $tgl = (str_starts_with($tgl, '0000') || $tgl === '') ? '' : date('Y-m-d', strtotime($tgl));
                            $hp  = trim((string) $warga->no_hp) === '' ? '' : '0' . ltrim(preg_replace('/\D+/', '', (string) $warga->no_hp), '0');
                            $agamaList = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
                            $agamaVal  = old('agama', trim((string) $warga->agama));
                            if ($agamaVal !== '' && !in_array($agamaVal, $agamaList, true)) {
                                $agamaList[] = $agamaVal;
                            }
                            ?>

                            <?= form_open($__prefix . 'layanan/store', ['autocomplete' => 'off']) ?>
                            <input type="hidden" name="id_warga" value="<?= (int) $warga->id_warga ?>">

                            <div class="form-group">
                                <label class="mb-1 small" for="nama_pemohon">Nama Lengkap</label>
                                <input type="text" id="nama_pemohon" name="nama_pemohon" class="form-control" maxlength="255" value="<?= esc(old('nama_pemohon', $warga->nama_warga)) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="mb-1 small" for="nik_pemohon">No. KTP/NIK</label>
                                <input type="text" id="nik_pemohon" name="nik_pemohon" class="form-control" maxlength="50" inputmode="numeric" value="<?= esc(old('nik_pemohon', $warga->nik)) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="mb-1 small" for="alamat_pemohon">Alamat Rumah</label>
                                <input type="text" id="alamat_pemohon" name="alamat_pemohon" class="form-control" maxlength="255" value="<?= esc(old('alamat_pemohon', $alamat)) ?>" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="mb-1 small" for="tempat_lahir">Tempat Lahir</label>
                                    <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" maxlength="50" value="<?= esc(old('tempat_lahir', $warga->tempat_lahir)) ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="mb-1 small" for="tanggal_lahir">Tanggal Lahir</label>
                                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="<?= esc(old('tanggal_lahir', $tgl)) ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="mb-1 small" for="agama">Agama</label>
                                    <select id="agama" name="agama" class="form-control" required>
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($agamaList as $ag): ?>
                                            <option value="<?= esc($ag) ?>" <?= $agamaVal === $ag ? 'selected' : '' ?>><?= esc($ag) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="mb-1 small" for="no_hp">No. Telp/HP</label>
                                    <input type="tel" id="no_hp" name="no_hp" class="form-control" maxlength="20" placeholder="08xxxxxxxxxx" value="<?= esc(old('no_hp', $hp)) ?>" required>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label class="mb-1 small" for="maksut">Dengan ini bermaksud mengajukan permohonan :</label>
                                <input type="text" id="maksut" name="maksut" class="form-control" maxlength="100" placeholder="Tulis maksud Anda" value="<?= esc(old('maksut')) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="mb-1 small" for="perlu">Untuk Keperluan :</label>
                                <input type="text" id="perlu" name="perlu" class="form-control" maxlength="100" placeholder="Tulis keperluan Anda" value="<?= esc(old('perlu')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="mb-1 small">Sehubungan dengan hal tersebut di atas, berikut saya lampirkan berkas-berkas sebagai kelengkapan pendukung permohonan :</label>
                                <?php $oldLampiran = old('lampiran') ?: []; ?>
                                <?php for ($i = 0; $i < 10; $i++): ?>
                                    <div class="input-group input-group-sm mb-1">
                                        <div class="input-group-prepend"><span class="input-group-text" style="min-width: 2.5rem"><?= $i + 1 ?>.</span></div>
                                        <input type="text" name="lampiran[]" class="form-control" maxlength="100" placeholder="<?= $i === 0 ? 'Contoh: FC KTP' : '' ?>" value="<?= esc($oldLampiran[$i] ?? '') ?>" <?= $i === 0 ? 'required' : '' ?>>
                                    </div>
                                <?php endfor ?>
                                <small class="text-muted">* Berkas silakan dibawa pada saat surat sudah disetujui RT. Kosongkan baris yang tidak dipakai.</small>
                            </div>

                            <hr class="dot py-1" />
                            <p><strong>Data yang terdapat dalam lampiran dokumen permohonan ini adalah Benar dan Sah.</strong></p>
                            <p class="text-danger">Apabila dikemudian hari ditemukan bahwa dokumen yang telah saya berikan tidak benar, maka saya bersedia dikenakan sanksi sesuai dengan peraturan dan ketentuan yang berlaku.</p>
                            <p>Demikian permohonan dan pernyataan ini saya buat dengan sebenar-benarnya, tanpa ada paksaan dari pihak manapun.</p>
                            <p>Atas perkenan Bapak / Ibu, saya ucapkan terima kasih.</p>

                            <hr class="dot" />

                            <div class="row mt-4">
                                <div class="col">
                                    <button type="submit" style="border-radius: 2rem" class="btn btn-primary py-3 w-100">Ajukan Surat Pernyataan</button>
                                    <a class="d-block text-center mt-3 small" href="<?= base_url($__prefix . 'layanan/pilih') ?>">&larr; Pilih orang lain</a>
                                </div>
                            </div>
                            <?= form_close() ?>
                        <?php endif ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
