<?php

declare(strict_types=1);

namespace OCA\Files_FullTextSearch_Tesseract\Integration;

use OCA\Files_FullTextSearch_Tesseract\Service\ConfigService;
use OCA\Files_FullTextSearch_Tesseract\Service\TesseractService;
use OCP\EventDispatcher\GenericEvent;
use OCP\Files_FullTextSearch\Model\AFilesDocument;
use PHPUnit\Framework\TestCase;

final class OcrPdfIntegrationTest extends TestCase {
	private static string $pdfFixture;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		$path = realpath(__DIR__ . '/fixtures/scanned-two-page.pdf');
		if ($path === false) {
			self::fail('Fixture PDF not found: ' . __DIR__ . '/fixtures/scanned-two-page.pdf');
		}
		self::$pdfFixture = $path;
	}

	public function testScannedPdfOcrsExpectedTokens(): void {
		$config = new ArrayConfig([
			ConfigService::TESSERACT_ENABLED => '1',
			ConfigService::TESSERACT_PSM => '4',
			ConfigService::TESSERACT_LANG => 'eng',
			ConfigService::TESSERACT_PDF => '1',
			ConfigService::TESSERACT_PDF_LIMIT => '0',
		], ['loglevel' => 0]);

		$logger = new BufferingLogger();
		$service = new TesseractService(new ConfigService($config), $logger);
		$file = new IntegrationFile(self::$pdfFixture);
		$document = new AFilesDocument(259, self::$pdfFixture, 'application/pdf');
		$event = new GenericEvent(null, [
			'file' => $file,
			'document' => $document,
		]);

		$service->onFileIndexing($event);

		$ocrText = $document->getPart('ocr');
		$normalized = strtoupper((string)preg_replace('/[^A-Z0-9]+/i', ' ', $ocrText));
		$tokens = array_values(array_filter(explode(' ', trim($normalized))));

		self::assertTrue(
			$this->containsFuzzyToken($tokens, 'ALPHA123', 1),
			"Expected token 'ALPHA123' not found in OCR output.\nOCR text: {$ocrText}\nLogs:\n" . $this->formatLogs($logger)
		);
		self::assertTrue(
			$this->containsFuzzyToken($tokens, 'BRAVO456', 2),
			"Expected token 'BRAVO456' not found in OCR output.\nOCR text: {$ocrText}\nLogs:\n" . $this->formatLogs($logger)
		);
	}

	private function containsFuzzyToken(array $haystack, string $needle, int $maxDistance): bool {
		foreach ($haystack as $token) {
			if (levenshtein($token, $needle) <= $maxDistance) {
				return true;
			}
		}
		return false;
	}

	private function formatLogs(BufferingLogger $logger): string {
		return implode("\n", array_map(
			static fn(array $r) => strtoupper((string)$r['level']) . ': ' . $r['message'],
			$logger->records
		));
	}
}
