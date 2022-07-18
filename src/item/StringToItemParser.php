<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\utils\CoralType;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\SlabType;
use pocketmine\block\VanillaBlocks as Blocks;
use pocketmine\item\VanillaItems as Items;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\StringToTParser;

/**
 * Handles parsing items from strings. This is used to interpret names from the /give command (and others).
 *
 * @phpstan-extends StringToTParser<Item>
 */
final class StringToItemParser extends StringToTParser{
	use SingletonTrait;

	/** @phpstan-param \Closure(string $input) : Block $callback */
	public function registerBlock(string $alias, \Closure $callback) : void{
		$this->register($alias, fn(string $input) => $callback($input)->asItem());
	}

	public function parse(string $input) : ?Item{
		return parent::parse($input);
	}
}
