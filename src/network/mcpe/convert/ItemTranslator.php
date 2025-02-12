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

namespace pocketmine\network\mcpe\convert;

use pocketmine\data\bedrock\LegacyItemIdToStringIdMap;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use Webmozart\PathUtil\Path;
use function array_key_exists;
use function file_get_contents;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;

/**
 * This class handles translation between network item ID+metadata to PocketMine-MP internal ID+metadata and vice versa.
 */
final class ItemTranslator{
	use SingletonTrait;

	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $simpleCoreToNetMapping = [];
	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $simpleNetToCoreMapping = [];

	/**
	 * runtimeId = array[internalId][metadata]
	 * @var int[][]
	 * @phpstan-var array<int, array<int, int>>
	 */
	private array $complexCoreToNetMapping = [];
	/**
	 * [internalId, metadata] = array[runtimeId]
	 * @var int[][]
	 * @phpstan-var array<int, array{int, int}>
	 */
	private array $complexNetToCoreMapping = [];

	/**
	 * @param int[] $simpleMappings
	 * @param int[][] $complexMappings
	 * @phpstan-param array<string, int> $simpleMappings
	 * @phpstan-param array<string, array<int, int>> $complexMappings
	 */
	public function __construct(ItemTypeDictionary $dictionary, array $simpleMappings, array $complexMappings){
		foreach($dictionary->getEntries() as $entry){
			$stringId = $entry->getStringId();
			$netId = $entry->getNumericId();
			if(isset($complexMappings[$stringId])){
				[$id, $meta] = $complexMappings[$stringId];
				$this->complexCoreToNetMapping[$id][$meta] = $netId;
				$this->complexNetToCoreMapping[$netId] = [$id, $meta];
			}elseif(isset($simpleMappings[$stringId])){
				$this->simpleCoreToNetMapping[$simpleMappings[$stringId]] = $netId;
				$this->simpleNetToCoreMapping[$netId] = $simpleMappings[$stringId];
			}else{
				//not all items have a legacy mapping - for now, we only support the ones that do
				continue;
			}
		}
	}

	/**
	 * @return int[]|null
	 * @phpstan-return array{int, int}|null
	 */
	public function toNetworkIdQuiet(int $internalId, int $internalMeta) : ?array{
		if($internalMeta === -1){
			$internalMeta = 0x7fff;
		}
		if(isset($this->complexCoreToNetMapping[$internalId][$internalMeta])){
			return [$this->complexCoreToNetMapping[$internalId][$internalMeta], 0];
		}
		if(array_key_exists($internalId, $this->simpleCoreToNetMapping)){
			return [$this->simpleCoreToNetMapping[$internalId], $internalMeta];
		}

		return null;
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 */
	public function toNetworkId(int $internalId, int $internalMeta) : array{
		return $this->toNetworkIdQuiet($internalId, $internalMeta) ??
			throw new \InvalidArgumentException("Unmapped ID/metadata combination $internalId:$internalMeta");
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 * @throws TypeConversionException
	 */
	public function fromNetworkId(int $networkId, int $networkMeta, ?bool &$isComplexMapping = null) : array{
		if(isset($this->complexNetToCoreMapping[$networkId])){
			if($networkMeta !== 0){
				throw new TypeConversionException("Unexpected non-zero network meta on complex item mapping");
			}
			$isComplexMapping = true;
			return $this->complexNetToCoreMapping[$networkId];
		}
		$isComplexMapping = false;
		if(isset($this->simpleNetToCoreMapping[$networkId])){
			return [$this->simpleNetToCoreMapping[$networkId], $networkMeta];
		}
		throw new TypeConversionException("Unmapped network ID/metadata combination $networkId:$networkMeta");
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 * @throws TypeConversionException
	 */
	public function fromNetworkIdWithWildcardHandling(int $networkId, int $networkMeta) : array{
		$isComplexMapping = false;
		if($networkMeta !== 0x7fff){
			return $this->fromNetworkId($networkId, $networkMeta);
		}
		[$id, $meta] = $this->fromNetworkId($networkId, 0, $isComplexMapping);
		return [$id, $isComplexMapping ? $meta : -1];
	}
}
