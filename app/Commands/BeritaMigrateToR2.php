<?php

namespace App\Commands;

use App\Libraries\R2Storage;
use App\Models\RtModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * One-off/re-runnable backfill: uploads any berita.foto value that isn't
 * already a full R2 URL (pre-R2 legacy rows, and rows saved by the local
 * fallback when an R2 upload failed at request time) to R2, then updates
 * the row and removes the local file. Safe to run repeatedly - rows
 * already migrated are full URLs and get skipped.
 *
 * Each row's own id_rt resolves its tenant slug, so newly-migrated
 * objects land under "berita/{rt_slug}/..." matching Admin\Berita's live
 * upload path - not the old flat "berita/..." layout some already-
 * migrated rows still use (those are left as-is, not re-keyed).
 */
class BeritaMigrateToR2 extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'berita:migrate-to-r2';
    protected $description = 'Uploads local-disk berita foto files to R2 and updates their DB rows.';

    public function run(array $params)
    {
        $db      = Database::connect();
        $r2      = new R2Storage();
        $rtModel = new RtModel();
        $slugs   = [];
        $rows    = $db->table('berita')
            ->where('foto IS NOT NULL', null, false)
            ->where('foto !=', '')
            ->get()->getResult();

        $pending = array_filter($rows, static fn ($row) => !str_starts_with($row->foto, 'http://') && !str_starts_with($row->foto, 'https://'));

        if (empty($pending)) {
            CLI::write('Nothing to migrate - all berita foto values are already R2 URLs.', 'green');

            return EXIT_SUCCESS;
        }

        CLI::write(count($pending) . ' local berita foto(s) to migrate.');

        $migrated = 0;
        $failed   = 0;

        foreach ($pending as $row) {
            $localPath = FCPATH . 'public/berita/' . $row->foto;

            if (!is_file($localPath)) {
                CLI::error("id_berita={$row->id_berita}: local file not found ({$row->foto}), skipping.");
                $failed++;
                continue;
            }

            try {
                if (!array_key_exists($row->id_rt, $slugs)) {
                    $rt              = $rtModel->find($row->id_rt);
                    $slugs[$row->id_rt] = $rt->slug ?? null;
                }
                $prefix = $slugs[$row->id_rt] !== null ? 'berita/' . $slugs[$row->id_rt] : 'berita';

                $uploaded = new \CodeIgniter\HTTP\Files\UploadedFile($localPath, $row->foto, mime_content_type($localPath) ?: null, null, null, null);
                $url      = $r2->upload($uploaded, $prefix);

                // `timestamp` has ON UPDATE CURRENT_TIMESTAMP - explicitly
                // setting it back to its own value stops MySQL auto-bumping
                // it, which would otherwise reorder the berita list (sorted
                // by timestamp DESC) even though only storage location changed.
                $db->table('berita')->where('id_berita', $row->id_berita)->update([
                    'foto'      => $url,
                    'timestamp' => $row->timestamp,
                ]);
                unlink($localPath);

                CLI::write("id_berita={$row->id_berita}: migrated -> {$url}", 'green');
                $migrated++;
            } catch (\Throwable $e) {
                CLI::error("id_berita={$row->id_berita}: upload failed - {$e->getMessage()}");
                $failed++;
            }
        }

        CLI::write("Done. Migrated: {$migrated}, failed: {$failed}.");

        return $failed > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
