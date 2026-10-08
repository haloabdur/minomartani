<?php

namespace App\Libraries;

/**
 * Pure helpers for the Layanan / Surat flow: normalising what a resident
 * typed, comparing it against the RT's own `warga` record, and
 * (de)serialising the lampiran list. No DB access - SuratModel supplies
 * the rows - so everything here is unit-testable.
 */
class SuratPemohon
{
    public const ALAMAT_SUFFIX = 'Minomartani, Ngaglik, Sleman, Daerah Istimewa Yogyakarta';

    public const MAX_LAMPIRAN = 10;

    /**
     * Compared fields: key => [label, surat column, aliased warga column].
     * SuratModel::SELECT aliases the warga side as rt_*, because surat now
     * carries same-named snapshot columns (tempat_lahir, agama, ...).
     */
    public const FIELDS = [
        'nama'          => ['Nama Lengkap', 'nama_pemohon', 'rt_nama'],
        'nik'           => ['No. KTP/NIK', 'nik_pemohon', 'rt_nik'],
        'alamat'        => ['Alamat Rumah', 'alamat_pemohon', 'rt_alamat_lengkap'],
        'tempat_lahir'  => ['Tempat Lahir', 'tempat_lahir', 'rt_tempat_lahir'],
        'tanggal_lahir' => ['Tanggal Lahir', 'tanggal_lahir', 'rt_tanggal_lahir'],
        'agama'         => ['Agama', 'agama', 'rt_agama'],
        'no_hp'         => ['No. Telp/HP', 'no_hp', 'rt_no_hp'],
    ];

    public static function normalizeText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    /**
     * Digits only, without the leading 0 / 62 country prefix - the form
     * `warga.no_hp` already uses (the legacy views prepend 0 or 62 when
     * displaying). "0812-3456", "+62 812 3456" and "812 3456" all map to
     * "8123456".
     */
    public static function normalizePhone(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }

        return ltrim($digits, '0');
    }

    public static function normalizeDate(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000')) {
            return '';
        }

        $time = strtotime($value);

        return $time === false ? '' : date('Y-m-d', $time);
    }

    /**
     * The address as the RT has it: the free-text `warga.alamat_lengkap`
     * when filled in, otherwise the block address from `alamat`
     * formatted like the printed sheet always did ("Jl. <alamat> ...").
     */
    public static function alamatRt(?string $alamatLengkap, ?string $alamatBlok): string
    {
        $alamatLengkap = trim((string) $alamatLengkap);
        if ($alamatLengkap !== '') {
            return $alamatLengkap;
        }

        $alamatBlok = trim((string) $alamatBlok);

        return $alamatBlok === '' ? '' : 'Jl. ' . $alamatBlok . ' ' . self::ALAMAT_SUFFIX;
    }

    private static function normalizeField(string $key, ?string $value): string
    {
        return match ($key) {
            'no_hp'         => self::normalizePhone($value),
            'tanggal_lahir' => self::normalizeDate($value),
            default         => self::normalizeText($value),
        };
    }

    /**
     * Whether this surat row carries a snapshot of the applicant's data
     * (rows created before the snapshot columns existed do not).
     */
    public static function hasSnapshot(object $surat): bool
    {
        return $surat->nama_pemohon !== null;
    }

    /**
     * Field-by-field comparison of what the applicant submitted against
     * the RT's warga record. Empty array for legacy rows without a
     * snapshot (nothing to compare).
     *
     * @return array<string, array{label: string, pemohon: string, rt: string, sama: bool}>
     */
    public static function bandingkan(object $surat): array
    {
        if (! self::hasSnapshot($surat)) {
            return [];
        }

        $rows = [];
        foreach (self::FIELDS as $key => [$label, $suratCol, $rtCol]) {
            $pemohon = (string) ($surat->{$suratCol} ?? '');
            $rt      = $key === 'alamat'
                ? self::alamatRt($surat->rt_alamat_lengkap ?? null, $surat->rt_alamat ?? null)
                : (string) ($surat->{$rtCol} ?? '');

            $rows[$key] = [
                'label'   => $label,
                'pemohon' => $pemohon,
                'rt'      => $rt,
                'sama'    => self::normalizeField($key, $pemohon) === self::normalizeField($key, $rt),
            ];
        }

        return $rows;
    }

    /**
     * Applicant data as printed on the sheet: what the applicant
     * submitted; legacy rows (no snapshot) fall back to the warga record.
     *
     * @return array{nama: string, nik: string, alamat: string, tempat_lahir: string, tanggal_lahir: string, agama: string, no_hp: string}
     */
    public static function dataCetak(object $surat): array
    {
        $hp = self::hasSnapshot($surat) ? $surat->no_hp : ($surat->rt_no_hp ?? '');
        $hp = self::normalizePhone($hp);

        if (self::hasSnapshot($surat)) {
            return [
                'nama'          => (string) $surat->nama_pemohon,
                'nik'           => (string) $surat->nik_pemohon,
                'alamat'        => (string) $surat->alamat_pemohon,
                'tempat_lahir'  => (string) $surat->tempat_lahir,
                'tanggal_lahir' => self::normalizeDate($surat->tanggal_lahir),
                'agama'         => (string) $surat->agama,
                'no_hp'         => $hp === '' ? '' : '0' . $hp,
            ];
        }

        return [
            'nama'          => (string) $surat->rt_nama,
            'nik'           => (string) $surat->rt_nik,
            'alamat'        => self::alamatRt($surat->rt_alamat_lengkap, $surat->rt_alamat),
            'tempat_lahir'  => (string) $surat->rt_tempat_lahir,
            'tanggal_lahir' => self::normalizeDate($surat->rt_tanggal_lahir),
            'agama'         => (string) $surat->rt_agama,
            'no_hp'         => $hp === '' ? '' : '0' . $hp,
        ];
    }

    public static function jumlahBeda(object $surat): int
    {
        return count(array_filter(self::bandingkan($surat), static fn ($r) => ! $r['sama']));
    }

    /**
     * @param mixed $input raw lampiran[] POST value
     * @return list<string> trimmed, non-empty, at most MAX_LAMPIRAN items
     */
    public static function cleanLampiran(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $items = [];
        foreach ($input as $item) {
            if (! is_string($item)) {
                continue;
            }
            $item = trim(preg_replace('/\s+/u', ' ', $item) ?? $item);
            if ($item !== '') {
                $items[] = mb_substr($item, 0, 100);
            }
        }

        return array_slice($items, 0, self::MAX_LAMPIRAN);
    }

    public static function encodeLampiran(array $items): string
    {
        return json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Reads both formats: the JSON array written by the current form and
     * the legacy comma-separated string written by the old one.
     *
     * @return list<string>
     */
    public static function parseLampiran(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        if ($value[0] === '[') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $decoded), static fn ($v) => $v !== ''));
            }
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn ($v) => $v !== ''));
    }
}
