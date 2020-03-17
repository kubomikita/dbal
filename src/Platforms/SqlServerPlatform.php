<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras\Dbal library.
 * @license    MIT
 * @link       https://github.com/nextras/dbal
 */

namespace Nextras\Dbal\Platforms;

use Nextras\Dbal\Connection;
use Nextras\Dbal\Platforms\Data\Column;
use Nextras\Dbal\Platforms\Data\ForeignKey;
use Nextras\Dbal\Platforms\Data\Table;


class SqlServerPlatform implements IPlatform
{
	/** @var Connection */
	private $connection;


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}


	public function getName(): string
	{
		return 'mssql';
	}


	/** @inheritDoc */
	public function getTables(): array
	{
		$result = $this->connection->query("
			SELECT TABLE_NAME, TABLE_TYPE, TABLE_SCHEMA
 			FROM information_schema.tables
 			ORDER BY TABLE_NAME
 		");

		$tables = [];
		foreach ($result as $row) {
			$table = new Table();
			$table->name = $row->TABLE_NAME;
			$table->schema = $row->TABLE_SCHEMA;
			$table->isView = $row->TABLE_TYPE === 'VIEW';

			$tables[$table->getNameFqn()] = $table;
		}
		return $tables;
	}


	/** @inheritDoc */
	public function getColumns(string $table): array
	{
		$result = $this->connection->query("
			SELECT
				[a].[COLUMN_NAME] AS [name],
				UPPER([a].[DATA_TYPE]) AS [type],
				CASE
					WHEN [a].[CHARACTER_MAXIMUM_LENGTH] IS NOT NULL THEN [a].[CHARACTER_MAXIMUM_LENGTH]
					WHEN  [a].[NUMERIC_PRECISION] IS NOT NULL THEN [a].[NUMERIC_PRECISION]
					ELSE NULL
				END AS [size],
				[a].[COLUMN_DEFAULT] AS [default],
				CASE
					WHEN [b].[CONSTRAINT_TYPE] = 'PRIMARY KEY'
					THEN CONVERT(BIT, 1)
					ELSE CONVERT(BIT, 0)
				END AS [is_primary],
				CONVERT(
					BIT, COLUMNPROPERTY(object_id([a].[TABLE_NAME]), [a].[COLUMN_NAME], 'IsIdentity')
				) AS [is_autoincrement],
				CASE
					WHEN [a].[IS_NULLABLE] = 'YES'
					THEN CONVERT(BIT, 1)
					ELSE CONVERT(BIT, 0)
				END AS [is_nullable]
			FROM [INFORMATION_SCHEMA].[COLUMNS] AS [a]
			LEFT JOIN (
				SELECT [c].[TABLE_SCHEMA], [c].[TABLE_CATALOG], [c].[TABLE_NAME], [c].[COLUMN_NAME], [d].[CONSTRAINT_TYPE]
				FROM [INFORMATION_SCHEMA].[CONSTRAINT_COLUMN_USAGE] AS [c]
				INNER JOIN [INFORMATION_SCHEMA].[TABLE_CONSTRAINTS] AS [d]
				ON ([d].[CONSTRAINT_NAME] = [c].[CONSTRAINT_NAME] AND [d].[CONSTRAINT_TYPE] = 'PRIMARY KEY')
			) AS [b] ON (
				[b].[TABLE_CATALOG] = [a].[TABLE_CATALOG] AND
				[b].[TABLE_SCHEMA] = [a].[TABLE_SCHEMA] AND
				[b].[TABLE_NAME] = [a].[TABLE_NAME] AND
				[b].[COLUMN_NAME] = [a].[COLUMN_NAME]
			)
			WHERE [a].[TABLE_NAME] = %s
			ORDER BY [a].[ORDINAL_POSITION]
		", $table);

		$columns = [];
		foreach ($result as $row) {
			$column = new Column();
			$column->name = $row->name;
			$column->type = $row->type;
			$column->size = $row->size;
			$column->default = $row->default;
			$column->isPrimary = $row->is_primary;
			$column->isAutoincrement = $row->is_autoincrement;
			$column->isUnsigned = false; // not available in SqlServer
			$column->isNullable = $row->is_nullable;
			$column->meta = [];

			$columns[$column->name] = $column;
		}

		return $columns;
	}


	/** @inheritDoc */
	public function getForeignKeys(string $table): array
	{
		$result = $this->connection->query("
			SELECT
				[a].[CONSTRAINT_NAME] AS [name],
				[d].[COLUMN_NAME] AS [column],
				[d].[TABLE_SCHEMA] AS [schema],
				[c].[TABLE_NAME] AS [ref_table],
				[c].[TABLE_SCHEMA] AS [ref_table_schema],
				[e].[COLUMN_NAME] AS [ref_column]
			FROM [INFORMATION_SCHEMA].[REFERENTIAL_CONSTRAINTS] AS [a]
			INNER JOIN [INFORMATION_SCHEMA].[TABLE_CONSTRAINTS] AS [b]
				ON [a].[CONSTRAINT_NAME] = [b].[CONSTRAINT_NAME]
			INNER JOIN [INFORMATION_SCHEMA].[TABLE_CONSTRAINTS] AS [c]
				ON [a].[UNIQUE_CONSTRAINT_NAME] = [c].[CONSTRAINT_NAME]
			INNER JOIN [INFORMATION_SCHEMA].[KEY_COLUMN_USAGE] AS [d]
				ON [a].[CONSTRAINT_NAME] = [d].[CONSTRAINT_NAME]
			INNER JOIN (
				SELECT [i1].[TABLE_NAME], [i2].[COLUMN_NAME]
				FROM [INFORMATION_SCHEMA].[TABLE_CONSTRAINTS] AS [i1]
				INNER JOIN [INFORMATION_SCHEMA].[KEY_COLUMN_USAGE] AS [i2]
					ON [i1].[CONSTRAINT_NAME] = [i2].[CONSTRAINT_NAME]
				WHERE [i1].[CONSTRAINT_TYPE] = 'PRIMARY KEY'
			) AS [e] ON [e].[TABLE_NAME] = [c].[TABLE_NAME]
			WHERE [b].[TABLE_NAME] = %s
			ORDER BY [d].[COLUMN_NAME]
		", $table);

		$keys = [];
		foreach ($result as $row) {
			$foreignKey = new ForeignKey();
			$foreignKey->name = $row->name;
			$foreignKey->schema = $row->schema;
			$foreignKey->column = $row->column;
			$foreignKey->refTable = $row->ref_table;
			$foreignKey->refTableSchema = $row->ref_table_schema;
			$foreignKey->refColumn = $row->ref_column;

			$keys[$foreignKey->column] = $foreignKey;
		}
		return $keys;
	}


	public function getPrimarySequenceName(string $table): ?string
	{
		return null;
	}


	public function isSupported(int $feature): bool
	{
		static $supported = [
		];
		return isset($supported[$feature]);
	}
}
