<?php

use App\Models\WargaModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class WargaFamilyTreeStructureTest extends CIUnitTestCase
{
    public function testGetFamilyTreeMethodExists(): void
    {
        $this->assertTrue(method_exists(WargaModel::class, 'getFamilyTree'));

        $ref = new ReflectionMethod(WargaModel::class, 'getFamilyTree');
        $this->assertTrue($ref->isPublic());
        $this->assertSame('array', (string) $ref->getReturnType());
        $this->assertSame(1, $ref->getNumberOfRequiredParameters());
    }
}
