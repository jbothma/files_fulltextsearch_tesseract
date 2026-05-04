<?php

declare(strict_types=1);

// OC\Files\View is a Nextcloud server internal — not shipped by nextcloud/ocp.
// Everything else (OCP\*, Psr\Log\*) comes from composer deps.
namespace OC\Files {
	class View {
		public function __construct(string $root = '') {
		}

		public function getLocalFile(string $path): string {
			return $path;
		}
	}
}

namespace OCA\Files_FullTextSearch_Tesseract\Integration {

use OCP\Files\File;
use OCP\IConfig;
use Psr\Log\AbstractLogger;

final class ArrayConfig implements IConfig {
	private array $appValues;
	private array $systemValues;

	public function __construct(array $appValues, array $systemValues = []) {
		$this->appValues = $appValues;
		$this->systemValues = $systemValues;
	}

	public function setSystemValues(array $configs): void {}
	public function setSystemValue($key, $value): void {}
	public function getSystemValue($key, $default = '') { return $this->systemValues[$key] ?? $default; }
	public function getSystemValueBool(string $key, bool $default = false): bool { return (bool)($this->systemValues[$key] ?? $default); }
	public function getSystemValueInt(string $key, int $default = 0): int { return (int)($this->systemValues[$key] ?? $default); }
	public function getSystemValueString(string $key, string $default = ''): string { return (string)($this->systemValues[$key] ?? $default); }
	public function getFilteredSystemValue($key, $default = '') { return $this->systemValues[$key] ?? $default; }
	public function deleteSystemValue($key): void {}
	public function getAppKeys($appName): array { return array_keys($this->appValues); }
	public function setAppValue($appName, $key, $value): void { $this->appValues[$key] = $value; }
	public function getAppValue($appName, $key, $default = ''): string { return $this->appValues[$key] ?? (string)$default; }
	public function deleteAppValue($appName, $key): void { unset($this->appValues[$key]); }
	public function deleteAppValues($appName): void {}
	public function setUserValue($userId, $appName, $key, $value, $preCondition = null): void {}
	public function getUserValue($userId, $appName, $key, $default = '') { return $default; }
	public function getUserValueForUsers($appName, $key, $userIds): array { return []; }
	public function getUserKeys($userId, $appName): array { return []; }
	public function getAllUserValues(string $userId): array { return []; }
	public function deleteUserValue($userId, $appName, $key): void {}
	public function deleteAllUserValues($userId): void {}
	public function deleteAppFromAllUsers($appName): void {}
	public function getUsersForUserValue($appName, $key, $value): array { return []; }
}

// AbstractLogger from psr/log supplies emergency/alert/critical/… in terms of log().
final class BufferingLogger extends AbstractLogger {
	public array $records = [];

	public function log($level, $message, array $context = []): void {
		$this->records[] = [
			'level' => $level,
			'message' => (string)$message,
			'context' => $context,
		];
	}
}

// Implements the real OCP\Files\File interface.
// Only getPath() is exercised by TesseractService; all other methods are no-ops.
final class IntegrationFile implements File {
	public function __construct(private string $path) {}

	public function getPath(): string { return $this->path; }
	public function getInternalPath(): string { return $this->path; }
	public function getName(): string { return basename($this->path); }
	public function getExtension(): string { return pathinfo($this->path, PATHINFO_EXTENSION); }
	public function getMimeType(): string { return ''; }
	public function getContent(): string { return (string)file_get_contents($this->path); }
	public function getId(): int { return 0; }
	public function stat(): array { return []; }
	public function getMTime(): int { return 0; }
	public function getSize($includeMounts = true): int { return 0; }
	public function getEtag(): string { return ''; }
	public function getPermissions(): int { return 0; }
	public function isReadable(): bool { return true; }
	public function isUpdateable(): bool { return false; }
	public function isDeletable(): bool { return false; }
	public function isShareable(): bool { return false; }
	public function lock($type): void {}
	public function changeLock($targetType): void {}
	public function unlock($type): void {}
	public function touch($mtime = null): void {}
	public function move($targetPath) { throw new \BadMethodCallException('not implemented'); }
	public function delete() { throw new \BadMethodCallException('not implemented'); }
	public function copy($targetPath) { throw new \BadMethodCallException('not implemented'); }
	public function getStorage() { throw new \BadMethodCallException('not implemented'); }
	public function getParent() { throw new \BadMethodCallException('not implemented'); }
	public function putContent($data): void { throw new \BadMethodCallException('not implemented'); }
	public function fopen($mode) { throw new \BadMethodCallException('not implemented'); }
	public function hash($type, $raw = false): string { return ''; }
	public function getChecksum(): string { return ''; }
}
} // namespace OCA\Files_FullTextSearch_Tesseract\Integration
