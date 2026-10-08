<?php

use App\Libraries\PinThrottle;
use App\Libraries\SuratPemohon;
use CodeIgniter\Cache\Handlers\FileHandler;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SuratPemohonTest extends CIUnitTestCase
{
    /** A surat row as SuratModel::SELECT returns it, snapshot equal to the RT data. */
    private function row(array $override = []): object
    {
        return (object) array_merge([
            'nama_pemohon'      => 'Budi Santoso',
            'nik_pemohon'       => '3404010101900001',
            'alamat_pemohon'    => 'Jl. Bandeng/5 Minomartani, Ngaglik, Sleman, Daerah Istimewa Yogyakarta',
            'tempat_lahir'      => 'Sleman',
            'tanggal_lahir'     => '1990-01-01',
            'agama'             => 'Islam',
            'no_hp'             => '81234567890',
            'rt_nama'           => 'Budi Santoso',
            'rt_nik'            => '3404010101900001',
            'rt_alamat_lengkap' => null,
            'rt_alamat'         => 'Bandeng/5',
            'rt_tempat_lahir'   => 'Sleman',
            'rt_tanggal_lahir'  => '1990-01-01',
            'rt_agama'          => 'Islam',
            'rt_no_hp'          => '81234567890',
        ], $override);
    }

    public function testIdenticalDataHasNoDifferences(): void
    {
        $this->assertSame(0, SuratPemohon::jumlahBeda($this->row()));
    }

    public function testCaseAndWhitespaceDoNotCountAsDifferences(): void
    {
        $row = $this->row(['nama_pemohon' => '  budi   SANTOSO ', 'agama' => 'islam']);

        $this->assertSame(0, SuratPemohon::jumlahBeda($row));
    }

    public function testRealDifferencesAreFlaggedPerField(): void
    {
        $row = $this->row(['nama_pemohon' => 'Budi S.', 'tanggal_lahir' => '1990-01-02']);

        $banding = SuratPemohon::bandingkan($row);

        $this->assertFalse($banding['nama']['sama']);
        $this->assertFalse($banding['tanggal_lahir']['sama']);
        $this->assertTrue($banding['nik']['sama']);
        $this->assertSame(2, SuratPemohon::jumlahBeda($row));
    }

    /**
     * @dataProvider phoneProvider
     */
    public function testPhoneNormalization(string $input, string $expected): void
    {
        $this->assertSame($expected, SuratPemohon::normalizePhone($input));
    }

    public static function phoneProvider(): array
    {
        return [
            'leading zero'     => ['0812-3456-7890', '81234567890'],
            'plus country'     => ['+62 812 3456 7890', '81234567890'],
            'country no plus'  => ['6281234567890', '81234567890'],
            'already stripped' => ['81234567890', '81234567890'],
            'empty'            => ['', ''],
        ];
    }

    public function testDifferentPhoneFormatsOfSameNumberMatch(): void
    {
        $row = $this->row(['no_hp' => '0812 3456 7890', 'rt_no_hp' => '81234567890']);

        $this->assertSame(0, SuratPemohon::jumlahBeda($row));
    }

    public function testRtAddressFallsBackToFormattedBlockAddress(): void
    {
        $this->assertSame(
            'Jl. Bandeng/5 ' . SuratPemohon::ALAMAT_SUFFIX,
            SuratPemohon::alamatRt(null, 'Bandeng/5')
        );
        $this->assertSame('Gang Mawar 3', SuratPemohon::alamatRt(' Gang Mawar 3 ', 'Bandeng/5'));
        $this->assertSame('', SuratPemohon::alamatRt(null, null));
    }

    public function testLegacyRowWithoutSnapshotHasNothingToCompare(): void
    {
        $row = $this->row(['nama_pemohon' => null, 'no_hp' => null]);

        $this->assertSame([], SuratPemohon::bandingkan($row));
        $this->assertSame(0, SuratPemohon::jumlahBeda($row));
        $this->assertSame('Budi Santoso', SuratPemohon::dataCetak($row)['nama']);
        $this->assertSame('081234567890', SuratPemohon::dataCetak($row)['no_hp']);
    }

    public function testPrintDataUsesWhatTheApplicantSubmitted(): void
    {
        $row = $this->row(['nama_pemohon' => 'Budi Santoso Jr']);

        $this->assertSame('Budi Santoso Jr', SuratPemohon::dataCetak($row)['nama']);
    }

    public function testLampiranRoundTripAndLegacyFormat(): void
    {
        $clean = SuratPemohon::cleanLampiran([' FC KTP ', '', 'FC  KK', 5, null]);
        $this->assertSame(['FC KTP', 'FC KK'], $clean);

        $this->assertSame($clean, SuratPemohon::parseLampiran(SuratPemohon::encodeLampiran($clean)));
        $this->assertSame(['FC KTP', 'FC KK'], SuratPemohon::parseLampiran('FC KTP, FC KK'));
        $this->assertSame([], SuratPemohon::parseLampiran(null));
    }

    public function testLampiranIsCappedAtTen(): void
    {
        $clean = SuratPemohon::cleanLampiran(array_map(static fn ($i) => 'Berkas ' . $i, range(1, 15)));

        $this->assertCount(SuratPemohon::MAX_LAMPIRAN, $clean);
    }

    public function testPinThrottleLocksAfterMaxFailuresAndResets(): void
    {
        $config = new \Config\Cache();
        $config->file['storePath'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pin_throttle_test_' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir($config->file['storePath'], 0777, true);
        $handler = new FileHandler($config);
        $handler->initialize();

        $throttle = new PinThrottle($handler);

        for ($i = 0; $i < PinThrottle::MAX_FAILURES - 1; $i++) {
            $throttle->recordFailure(1, 7);
            $this->assertFalse($throttle->isLocked(1, 7));
        }

        $throttle->recordFailure(1, 7);
        $this->assertTrue($throttle->isLocked(1, 7));
        $this->assertFalse($throttle->isLocked(1, 8), 'other addresses are unaffected');
        $this->assertFalse($throttle->isLocked(2, 7), 'other tenants are unaffected');

        $throttle->reset(1, 7);
        $this->assertFalse($throttle->isLocked(1, 7));
    }
}
