<?php

use App\Libraries\TenantContext;
use App\Models\StrukturOrganisasiModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Struktur organisasi: two-level ownership (RT vs shared RW), cross-RT warga
 * picker for RW only, parent (atasan) integrity, and the combined tree.
 *
 * @internal
 */
final class StrukturOrganisasiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    private int $idRw;
    private int $rtB;
    private int $wargaA;
    private int $wargaB;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();
        $this->seedWarga();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    private function seedWarga(): void
    {
        $db = Database::connect();

        $this->idRw = (int) $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;

        if ($db->table('rt')->where('slug', 'rt30-test')->countAllResults() === 0) {
            $db->table('rt')->insert(['id_rw' => $this->idRw, 'nama' => 'RT 30 Test', 'slug' => 'rt30-test']);
        }
        $this->rtB = (int) $db->table('rt')->where('slug', 'rt30-test')->get()->getRow()->id_rt;

        $warga = static fn (string $nama, string $nik, int $idRt) => [
            'no_kk' => $nik, 'nama_warga' => $nama, 'nik' => $nik,
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'Sleman', 'tanggal_lahir' => '1990-01-01',
            'id_pekerjaan' => 1, 'id_rt' => $idRt,
        ];
        $db->table('warga')->insert($warga('Warga A', '4444444444444441', 1));
        $this->wargaA = $db->insertID();
        $db->table('warga')->insert($warga('Warga B', '4444444444444442', $this->rtB));
        $this->wargaB = $db->insertID();
    }

    /** Inserts a struktur row directly; returns its id. */
    private function row(string $level, int $idWarga, string $jabatan, ?int $idAtasan = null, ?int $idRt = null): int
    {
        $db = Database::connect();
        $db->table('struktur_organisasi')->insert([
            'level'     => $level,
            'id_rt'     => $level === 'rt' ? ($idRt ?? 1) : null,
            'id_rw'     => $level === 'rw' ? $this->idRw : null,
            'id_warga'  => $idWarga,
            'jabatan'   => $jabatan,
            'id_atasan' => $idAtasan,
        ]);

        return (int) $db->insertID();
    }

    public function testDaftarScopesRtLevelToCurrentRtAndRwLevelToSharedRw(): void
    {
        $this->row('rt', $this->wargaA, 'Ketua RT A', null, 1);
        $this->row('rt', $this->wargaB, 'Ketua RT B', null, $this->rtB);
        $this->row('rw', $this->wargaA, 'Ketua RW');

        $model = new StrukturOrganisasiModel();

        tenant_set_rt(1);
        $this->assertSame(['Ketua RT A'], array_column($model->daftar('rt'), 'jabatan'));
        $this->assertSame(['Ketua RW'], array_column($model->daftar('rw'), 'jabatan'));

        tenant_set_rt($this->rtB);
        $this->assertSame(['Ketua RT B'], array_column($model->daftar('rt'), 'jabatan'));
        $this->assertSame(['Ketua RW'], array_column($model->daftar('rw'), 'jabatan'));
    }

    public function testDetailRejectsRowOfAnotherRt(): void
    {
        $idB = $this->row('rt', $this->wargaB, 'Ketua RT B', null, $this->rtB);
        $idRw = $this->row('rw', $this->wargaA, 'Ketua RW');

        tenant_set_rt(1);
        $model = new StrukturOrganisasiModel();

        $this->assertNull($model->detail($idB));
        $this->assertNotNull($model->detail($idRw));
    }

    public function testWargaPickerRtLevelIsCurrentRtOnlyWhileRwLevelSpansRw(): void
    {
        tenant_set_rt(1);
        $model = new StrukturOrganisasiModel();

        $this->assertTrue($model->wargaValid('rt', $this->wargaA));
        $this->assertFalse($model->wargaValid('rt', $this->wargaB));
        $this->assertTrue($model->wargaValid('rw', $this->wargaA));
        $this->assertTrue($model->wargaValid('rw', $this->wargaB));

        $rtNames = array_column($model->wargaOptions('rt'), 'nama_warga');
        $this->assertContains('Warga A', $rtNames);
        $this->assertNotContains('Warga B', $rtNames);
        $this->assertContains('Warga B', array_column($model->wargaOptions('rw'), 'nama_warga'));
    }

    public function testAtasanMustBeSameLevelAndNotSelfOrDescendant(): void
    {
        tenant_set_rt(1);
        $model = new StrukturOrganisasiModel();

        $ketua = $this->row('rt', $this->wargaA, 'Ketua');
        $sekretaris = $this->row('rt', $this->wargaA, 'Sekretaris', $ketua);
        $rwKetua = $this->row('rw', $this->wargaA, 'Ketua RW');

        $this->assertTrue($model->atasanValid('rt', null));
        $this->assertTrue($model->atasanValid('rt', $ketua, $sekretaris));
        $this->assertFalse($model->atasanValid('rt', $rwKetua), 'RW row cannot be the parent of an RT row');
        $this->assertFalse($model->atasanValid('rt', $ketua, $ketua), 'self');
        $this->assertFalse($model->atasanValid('rt', $sekretaris, $ketua), 'descendant would form a cycle');
        $this->assertFalse($model->atasanValid('rt', 999999));
    }

    public function testHapusMovesChildrenUpToTheDeletedRowsParent(): void
    {
        tenant_set_rt(1);
        $model = new StrukturOrganisasiModel();

        $atas = $this->row('rt', $this->wargaA, 'Ketua');
        $tengah = $this->row('rt', $this->wargaA, 'Sekretaris', $atas);
        $bawah = $this->row('rt', $this->wargaA, 'Seksi', $tengah);

        $model->hapus($model->detail($tengah));

        $db = Database::connect();
        $this->assertSame(0, $db->table('struktur_organisasi')->where('id_struktur', $tengah)->countAllResults());
        $this->assertSame($atas, (int) $db->table('struktur_organisasi')->where('id_struktur', $bawah)->get()->getRow()->id_atasan);
    }

    public function testTreeMarksDoubleRolesAndLinksRtRootUnderRwRoot(): void
    {
        tenant_set_rt(1);
        $model = new StrukturOrganisasiModel();

        $rwKetua = $this->row('rw', $this->wargaA, 'Ketua RW');
        $rtKetua = $this->row('rt', $this->wargaA, 'Ketua RT');
        $sekretaris = $this->row('rt', $this->wargaB, 'Sekretaris RT', $rtKetua);

        $tree = $model->tree();

        $this->assertTrue($tree['has_data']);
        $this->assertCount(3, $tree['nodes']);

        $nodes = array_column($tree['nodes'], null, 'id');
        $this->assertSame(['Ketua RT RT'], $nodes['s' . $rwKetua]['rangkap']);
        $this->assertSame(['Ketua RW RW'], $nodes['s' . $rtKetua]['rangkap']);
        $this->assertSame([], $nodes['s' . $sekretaris]['rangkap']);

        $edges = array_map(static fn ($e) => [$e['from'], $e['to'], $e['link']], $tree['edges']);
        $this->assertContains(['s' . $rwKetua, 's' . $rtKetua, true], $edges);
        $this->assertContains(['s' . $rtKetua, 's' . $sekretaris, false], $edges);
    }

    public function testTreeIsEmptyWithoutData(): void
    {
        tenant_set_rt(1);

        $this->assertFalse((new StrukturOrganisasiModel())->tree()['has_data']);
    }
}
