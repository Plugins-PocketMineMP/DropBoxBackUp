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
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;

use function curl_close;
use function curl_exec;
use function curl_setopt;
use function fclose;
use function file_exists;
use function filesize;
use function fopen;
use function is_array;
use function json_decode;
use function json_encode;
use function mt_rand;
use function unlink;

use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_INFILE;
use const CURLOPT_INFILESIZE;
use const CURLOPT_PUT;
use const CURLOPT_RETURNTRANSFER;

class SendTask extends AsyncTask{

	public function __construct(
		protected string $filename,
		protected $fileId,
		protected string $token
	){}

	public function onRun() : void{
		$fp = fopen($this->filename, 'rb');
		$size = filesize($this->filename);
		$arg = [
			"path" => $this->filename,
			"mode" => $this->fileId !== 0 ? "overwrite" : "add"
		];

		$cheaders = [
			'Authorization: Bearer ' . $this->token,
			'Content-Type: application/octet-stream',
			'Dropbox-API-Arg: ' . json_encode($arg)
		];

		$ch = curl_init('https://content.dropboxapi.com/2/files/upload');

		curl_setopt($ch, CURLOPT_HTTPHEADER, $cheaders);
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_INFILE, $fp);
		curl_setopt($ch, CURLOPT_INFILESIZE, $size);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		$result = json_decode($response, true);
		$this->setResult($result);
	}

	public function onCompletion() : void{
		Server::getInstance()->getLogger()->notice("Succeed to upload dropbox.");
		DropboxBackUp::$id = is_array($this->getResult()) ? $this->getResult()["id"] ?? mt_rand(1, 100) . Uuid::uuid4()->toString() : mt_rand(1, 100) . Uuid::uuid4()->toString();
		if(file_exists($this->filename)){
			@unlink($this->filename);
		}
	}
}