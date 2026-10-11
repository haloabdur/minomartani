<?php

use App\Libraries\TenantContext;
use App\Models\ActivityLogModel;
use App\Models\WargaModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Trait Auditable di WargaModel: create/update/delete tercatat dengan diff
 * field, dan log tidak bocor lintas tenant.
 *
 * @internal
 */
final class ActivityLogTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    private int $rtB;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();
        tenant_set_rt(1);

        $db = Database::connect();
        $db->table('activity_log')->truncate();

        if ($db->table('rt')->where('slug', 'rt30-test')->countAllResults() === 0) {
            $idRw = $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;
            $db->table('rt')->insert(['id_rw' => $idRw, 'nama' => 'RT 30 Test', 'slug' => 'rt30-test']);
        }
        $this->rtB = (int) $db->table('rt')->where('slug', 'rt30-test')->get()->getRow()->id_rt;
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    private function wargaRow(string $nik, int $idRt = 1): array
    {
        return [
            'no_kk' => '111', 'nama_warga' => 'Budi', 'nik' => $nik,
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'Sleman', 'tanggal_lahir' => '1990-01-01',
            'id_pekerjaan' => 1, 'no_hp' => '0812', 'id_rt' => $idRt,
        ];
    }

    public function testInsertUpdateDeleteAreLogged(): void
    {
        $model = new WargaModel();
        $id    = $model->insert($this->wargaRow('9000000000000001'));

        $model->update($id, ['no_hp' => '0813', 'nama_warga' => 'Budi']);
        $model->delete($id);

        $logs = Database::connect()->table('activity_log')->orderBy('id')->get()->getResultArray();
        $this->assertSame(['create', 'update', 'delete'], array_column($logs, 'action'));
        $this->assertSame('warga', $logs[0]['module']);
        $this->assertSame('Budi', $logs[0]['record_label']);
        $this->assertSame(1, (int) $logs[0]['id_rt']);
        $this->assertNotSame('', $logs[0]['user_name']);

        // Hanya field yang benar-benar berubah (nama_warga sama -> tidak ada).
        $this->assertSame(['no_hp' => ['0812', '0813']], json_decode($logs[1]['changes'], true));

        $deleted = json_decode($logs[2]['changes'], true);
        $this->assertSame(['9000000000000001', null], $deleted['nik']);
    }

    public function testUpdateWithoutChangesWritesNothing(): void
    {
        $model = new WargaModel();
        $id    = $model->insert($this->wargaRow('9000000000000002'));

        $model->update($id, ['no_hp' => '0812', 'email' => '']);

        $count = Database::connect()->table('activity_log')->where('action', 'update')->countAllResults();
        $this->assertSame(0, $count);
    }

    public function testLogsAreScopedToCurrentTenant(): void
    {
        $model = new WargaModel();
        $model->insert($this->wargaRow('9000000000000003', 1));
        $model->insert($this->wargaRow('9000000000000004', $this->rtB));

        tenant_set_rt(1);
        $rowsA = (new ActivityLogModel())->forCurrentRt([])->findAll();
        $this->assertCount(1, $rowsA);
        $this->assertSame(1, (int) $rowsA[0]['id_rt']);

        tenant_set_rt($this->rtB);
        $rowsB = (new ActivityLogModel())->forCurrentRt([])->findAll();
        $this->assertCount(1, $rowsB);
        $this->assertSame($this->rtB, (int) $rowsB[0]['id_rt']);
    }
}
