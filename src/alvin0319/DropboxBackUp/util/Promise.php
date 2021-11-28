<?php

/*
 *     ___                   ___             ___            _
 *    /   \_ __ ___  _ __   / __\ _____  __ / __\ __ _  ___| | __/\ /\ _ __
 *   / /\ / '__/ _ \| '_ \ /__\/// _ \ \/ //__\/// _` |/ __| |/ / / \ \ '_ \
 *  / /_//| | | (_) | |_) / \/  \ (_) >  </ \/  \ (_| | (__|   <\ \_/ / |_) |
 * /___,' |_|  \___/| .__/\_____/\___/_/\_\_____/\__,_|\___|_|\_\\___/| .__/
 *                  |_|                                               |_|
 *
 * Copyright (C) 2020 alvin0319
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace alvin0319\DropBoxBackUp\util;

use Closure;

final class Promise{

	public const PENDING = "pending";

	public const REJECTED = "rejected";

	public const FULFILLED = "fulfilled";

	/** @var mixed */
	protected $value = null;

	protected string $now = self::PENDING;

	/** @var Closure[] */
	protected array $fulfilled = [];

	/** @var Closure[] */
	protected array $rejected = [];

	public function then(Closure $callback) : Promise{
		if($this->now === self::FULFILLED){
			$callback($this->value);
			return $this;
		}
		$this->fulfilled[] = $callback;
		return $this;
	}

	public function catch(Closure $callback) : Promise{
		if($this->now === self::REJECTED){
			$callback($this->value);
			return $this;
		}
		$this->rejected[] = $callback;
		return $this;
	}

	public function resolve($value) : Promise{
		$this->setNow(self::FULFILLED, $value);
		return $this;
	}

	public function reject($reason) : Promise{
		$this->setNow(self::REJECTED, $reason);
		return $this;
	}

	public function setNow(string $now, $value) : Promise{
		$this->now = $now;
		$this->value = $value;

		$callbacks = $this->now === self::FULFILLED ? $this->fulfilled : $this->rejected;
		foreach($callbacks as $closure){
			$closure($this->value);
		}
		$this->fulfilled = $this->rejected = [];
		return $this;
	}
	
}