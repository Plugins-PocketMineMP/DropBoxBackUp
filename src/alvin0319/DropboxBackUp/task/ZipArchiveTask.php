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

namespace alvin0319\DropboxBackUp\task;

use alvin0319\DropboxBackUp\DropboxBackUp;
use alvin0319\DropBoxBackUp\util\Promise;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Utils;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

use function explode;
use function mb_substr_count;
use function str_replace;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

class ZipArchiveTask extends AsyncTask{

	protected string $path;

	protected string $os;

	protected string $fileName;

	public function __construct(Promise $promise, string $path, string $fileName, CommandSender $sender){
		$this->storeLocal('promise', $promise);
		$this->storeLocal('sender', $sender);
		if(substr($path, -1) !== DIRECTORY_SEPARATOR){
			$path .= DIRECTORY_SEPARATOR;
		}
		$this->path = $path;
		$this->os = Utils::getOS(true);
		$this->fileName = $fileName;
	}

	public function onRun() : void{
		if($this->os === "win"){
			$path = str_replace(explode('\\', $this->path)[mb_substr_count($this->path, '\\', 'utf-8') - 1] . '\\', '', $this->path);
		}else{
			$path = str_replace(explode('/', $this->path)[mb_substr_count($this->path, '/', 'utf-8') - 1] . '/', '', $this->path);
		}
		$this->osCheck();
		$this->setResult($path);
	}

	public function osCheck() : void{
		if($this->os === "win"){
			$this->doWindowsBackUp();
		}else{
			$this->doLinuxBackUp();
		}
	}

	public function onCompletion() : void{
		$server = Server::getInstance();
		/** @var Promise $promise */
		$promise = $this->fetchLocal('promise');
		/** @var CommandSender $sender */
		$sender = $this->fetchLocal('sender');
		$promise->resolve([$this->getResult() . $this->fileName, DropboxBackUp::$id, DropBoxBackUp::$token]);
		if($sender instanceof Player){
			if($sender->isOnline()){
				$sender->sendMessage("Backup completed!");
				$sender->sendMessage("Start uploading to Dropbox...");
			}
		}else{
			$server->getLogger()->notice("Backup completed!");
			$server->getLogger()->notice("Start uploading to Dropbox...");
		}
	}

	public function doLinuxBackUp() : void{
		$path = str_replace(explode('/', $this->path)[mb_substr_count($this->path, '/', 'utf-8') - 1] . '/', '', $this->path);
		$zip = new ZipArchive();
		$zip->open($path . $this->fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path), RecursiveIteratorIterator::LEAVES_ONLY);
		/** @var SplFileInfo $objects */
		foreach($files as $objects){
			if(!$objects->isDir()){
				$zip->addFile($objects->getRealPath(), substr($objects->getRealPath(), strlen($this->path)));
			}
		}
		$zip->close();
	}

	public function doWindowsBackUp() : void{
		$path = str_replace(explode('\\', $this->path)[mb_substr_count($this->path, '\\', 'utf-8') - 1] . '\\', '', $this->path);
		$zip = new ZipArchive();
		$zip->open($path . $this->fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path), RecursiveIteratorIterator::LEAVES_ONLY);
		/** @var SplFileInfo $objects */
		foreach($files as $objects){
			if(!$objects->isDir()){
				$zip->addFile($objects->getRealPath(), substr($objects->getRealPath(), strlen($this->path)));
			}
		}
		$zip->close();
	}
}