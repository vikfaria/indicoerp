<?php

namespace Tests\Unit;

use App\Services\SaftExportService;
use RuntimeException;
use Tests\TestCase;

class SaftExportServiceTest extends TestCase
{
    private const VALID_XML = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<AuditFile xmlns="urn:OECD:StandardAuditFile-Tax:MZ_1.0">
  <Header>
    <AuditFileVersion>1.0_01</AuditFileVersion>
  </Header>
</AuditFile>
XML;

    private const VALID_XSD = <<<'XSD'
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"
    targetNamespace="urn:OECD:StandardAuditFile-Tax:MZ_1.0"
    xmlns="urn:OECD:StandardAuditFile-Tax:MZ_1.0"
    elementFormDefault="qualified">
  <xs:element name="AuditFile">
    <xs:complexType>
      <xs:sequence>
        <xs:element name="Header">
          <xs:complexType>
            <xs:sequence>
              <xs:element name="AuditFileVersion" type="xs:string" />
            </xs:sequence>
          </xs:complexType>
        </xs:element>
      </xs:sequence>
    </xs:complexType>
  </xs:element>
</xs:schema>
XSD;

    private const INVALID_XML = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<AuditFile xmlns="urn:OECD:StandardAuditFile-Tax:MZ_1.0">
  <Header />
</AuditFile>
XML;

    public function test_validate_generated_xml_accepts_xml_against_configured_xsd(): void
    {
        $xsdPath = $this->writeTempXsd(self::VALID_XSD);

        try {
            config([
                'sce.saft.require_xsd_validation' => true,
                'sce.saft.xsd_path' => $xsdPath,
            ]);

            app(SaftExportService::class)->validateGeneratedXml(self::VALID_XML);

            $this->assertTrue(true);
        } finally {
            @unlink($xsdPath);
        }
    }

    public function test_validate_generated_xml_rejects_xml_against_configured_xsd(): void
    {
        $xsdPath = $this->writeTempXsd(self::VALID_XSD);

        try {
            config([
                'sce.saft.require_xsd_validation' => true,
                'sce.saft.xsd_path' => $xsdPath,
            ]);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('SAF-T inválido contra o XSD oficial');

            app(SaftExportService::class)->validateGeneratedXml(self::INVALID_XML);
        } finally {
            @unlink($xsdPath);
        }
    }

    private function writeTempXsd(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'saft_xsd_');

        if ($path === false) {
            $this->fail('Unable to create temporary XSD file.');
        }

        file_put_contents($path, $contents);

        return $path;
    }
}
