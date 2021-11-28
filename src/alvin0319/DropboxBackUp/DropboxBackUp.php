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

namespace alvin0319\DropboxBackUp;

use alvin0319\DropboxBackUp\task\SendTask;
use alvin0319\DropboxBackUp\task\ZipArchiveTask;
use alvin0319\DropboxBackUp\util\Promise;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

final class DropboxBackUp extends PluginBase{

	public static int|string $id = 0;
	public static string $fileName = "";
	public static string $token = "";

	public function onEnable() : void{
		$config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, [
			"id" => 0,
			"fileName" => TextFormat::clean($this->getServer()->getMotd()) . ".zip",
			"token" => ""
		]);
		self::$id = $config->getNested("id", 0);
		self::$fileName = $config->getNested("fileName", TextFormat::clean($this->getServer()->getMotd()) . "zip");
		self::$token = $config->getNested("token", "");
	}

	public function onDisable() : void{
		$config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, [
			"id" => 0,
			"fileName" => TextFormat::clean($this->getServer()->getMotd()) . ".zip",
			"token" => ""
		]);
		$config->setNested("id", self::$id);
		$config->setNested("fileName", self::$fileName);
		$config->save();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$this->start($sender);
		return true;
	}

	public function start(CommandSender $sender) : void{
		$sender->sendMessage("Starting BackUp...");
		$promise = new Promise();
		$this->getServer()->getAsyncPool()->submitTask(new ZipArchiveTask($promise, $this->getServer()->getDataPath(), self::$fileName, $sender));
		$promise->then(function(array $result){
			$this->getServer()->getAsyncPool()->submitTask(new SendTask($result[0], $result[1], $result[2]));
		});
	}
	
}