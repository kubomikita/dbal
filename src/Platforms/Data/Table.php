<?php declare(strict_types=1);

/**
 * This file is part of the Nextras\Orm library.
 * @license    MIT
 * @link       https://github.com/nextras/orm
 */

namespace Nextras\Dbal\Platforms\Data;


class Table
{
	/** @var string */
	public $name;

	/** @var string */
	public $schema;

	/** @var bool */
	public $isView;


	public function getNameFqn(): string
	{
		return "$this->schema.$this->name";
	}
}
