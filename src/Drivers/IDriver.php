<?php declare(strict_types = 1);

namespace Nextras\Dbal\Drivers;


use DateTimeZone;
use Nextras\Dbal\Drivers\Exception\DriverException;
use Nextras\Dbal\Exception\NotSupportedException;
use Nextras\Dbal\IConnection;
use Nextras\Dbal\ILogger;
use Nextras\Dbal\Platforms\IPlatform;
use Nextras\Dbal\Result\Result;


interface IDriver
{
	public const TYPE_BOOL = 1;
	public const TYPE_DATETIME = 2;
	public const TYPE_DATETIME_SIMPLE = 3;
	public const TYPE_IDENTIFIER = 4;
	public const TYPE_STRING = 5;
	public const TYPE_DATE_INTERVAL = 6;
	public const TYPE_BLOB = 7;

	public const TIMEZONE_AUTO_PHP_NAME = 'auto';
	public const TIMEZONE_AUTO_PHP_OFFSET = 'auto-offset';


	/**
	 * Connects the driver to database.
	 * @phpstan-param array<string, mixed> $params
	 * @internal
	 */
	public function connect(array $params, ILogger $logger): void;


	/**
	 * Disconnects from the database.
	 * @internal
	 */
	public function disconnect(): void;


	/**
	 * Returns true, if there is created connection.
	 */
	public function isConnected(): bool;


	/**
	 * Returns connection resource.
	 * @return mixed
	 */
	public function getResourceHandle();


	/**
	 * Returns connection time zone.
	 * If unsupported by driver, throws {@link NotSupportedException}.
	 */
	public function getConnectionTimeZone(): DateTimeZone;


	/**
	 * Runs query and returns a result. Returns a null if the query does not select any data.
	 * @throws DriverException
	 * @internal
	 */
	public function query(string $query): Result;


	/**
	 * Returns the last inserted id.
	 * @return mixed
	 * @internal
	 */
	public function getLastInsertedId(?string $sequenceName = null);


	/**
	 * Returns number of affected rows.
	 * @internal
	 */
	public function getAffectedRows(): int;


	/**
	 * Returns time taken by the last query.
	 */
	public function getQueryElapsedTime(): float;


	/**
	 * Creates database platform.
	 */
	public function createPlatform(IConnection $connection): IPlatform;


	/**
	 * Returns server version in X.Y.Z format.
	 */
	public function getServerVersion(): string;


	/**
	 * Pings server.
	 * Returns true if the ping was successful and connection is alive.
	 * @internal
	 */
	public function ping(): bool;


	/**
	 * @internal
	 */
	public function setTransactionIsolationLevel(int $level): void;


	/**
	 * Begins a transaction.
	 * @throws DriverException
	 * @internal
	 */
	public function beginTransaction(): void;


	/**
	 * Commits the current transaction.
	 * @throws DriverException
	 * @internal
	 */
	public function commitTransaction(): void;


	/**
	 * Rollbacks the current transaction.
	 * @throws DriverException
	 * @internal
	 */
	public function rollbackTransaction(): void;


	/**
	 * Creates a savepoint.
	 * @throws DriverException
	 * @internal
	 */
	public function createSavepoint(string $name): void;


	/**
	 * Releases the savepoint.
	 * @throws DriverException
	 * @internal
	 */
	public function releaseSavepoint(string $name): void;


	/**
	 * Rollbacks the savepoint.
	 * @throws DriverException
	 * @internal
	 */
	public function rollbackSavepoint(string $name): void;


	/**
	 * Converts database value to php boolean.
	 * @param mixed $value
	 * @param mixed $nativeType
	 * @return mixed
	 */
	public function convertToPhp($value, $nativeType);


	/**
	 * Converts string to safe escaped SQL expression including surrounding quotes.
	 */
	public function convertStringToSql(string $value): string;
}
