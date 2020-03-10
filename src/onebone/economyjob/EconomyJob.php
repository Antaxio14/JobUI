<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2015  antaxio discord: Antaxio#4013
 * Sub My Channel Youtube AntaxioYT
 *
 * This param is free software: you can redistribute it and/or modify
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace onebone\economyjob;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

class EconomyJob extends PluginBase implements Listener{
	/** @var Config */
	private $jobs;
	/** @var Config */
	private $player;

	/** @var  EconomyAPI */
	private $api;

	/** @var EconomyJob   */
	private static $instance;

	public function onEnable(){
		@mkdir($this->getDataFolder());
		if(!is_file($this->getDataFolder()."jobs.yml")){
			$this->jobs = new Config($this->getDataFolder()."jobs.yml", Config::YAML, yaml_parse($this->readResource("jobs.yml")));
		}else{
			$this->jobs = new Config($this->getDataFolder()."jobs.yml", Config::YAML);
		}
		$this->player = new Config($this->getDataFolder()."players.yml", Config::YAML);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->api = EconomyAPI::getInstance();
		self::$instance = $this;
	}

	private function readResource($res){
		$path = $this->getFile()."resources/".$res;
		$resource = $this->getResource($res);
		if(!is_resource($resource)){
			$this->getLogger()->debug("Tried to load unknown resource ".TextFormat::AQUA.$res.TextFormat::RESET);
			return false;
		}
		$content = stream_get_contents($resource);
		@fclose($content);
		return $content;
	}

	public function onDisable(){
		$this->player->save();
	}

	/**
	 * @priority LOWEST
	 * @ignoreCancelled true
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$job = $this->jobs->get($this->player->get($player->getName()));
		if($job !== false){
			if(isset($job[$block->getID().":".$block->getDamage().":break"])){
				$money = $job[$block->getID().":".$block->getDamage().":break"];
				if($money > 0){
					$this->api->addMoney($player, $money);
					$player->sendPopup("§1[§0+§4Money On Your 🏦");
				}else{
					$this->api->reduceMoney($player, $money);
				}
			}
		}
	}

	/**
	 * @priority LOWEST
	 * @ignoreCancelled true
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$job = $this->jobs->get($this->player->get($player->getName()));
		if($job !== false){
			if(isset($job[$block->getID().":".$block->getDamage().":place"])){
				$money = $job[$block->getID().":".$block->getDamage().":place"];
				if($money > 0){
					$this->api->addMoney($player, $money);
					$player->sendPopup("§b+§1[§4Money for your job§1]");
				}else{
					$this->api->reduceMoney($player, $money);
				}
			}
		}
	}

	/**
	 * @return EconomyJob
	*/
	public static function getInstance(){
		return static::$instance;
	}

	/**
	 * @return array
	 */
	public function getJobs(){
		return $this->jobs->getAll();
	}

	/**
	 * @return array
	 *
	 */
	public function getPlayers(){
		return $this->player->getAll();
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
		switch(array_shift($params)){
			default:
				$this->FormJob($sender);
		}
		return true;
	}
	
	public function FormJob($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
				}
				switch($result){
					case "0";
					$this->FormJobJoin($player);
					break;
					
					case "1";
					$player->sendMessage("§l§7[§6Jobs§7] §aอาชีพของคุณคือ : ".$this->player->get($player->getName()));
					break;
					
					case "2";
					$this->FormInfo($player);
					break;
					
					case "3";
					$job = $this->player->get($player->getName());
					$this->player->remove($player->getName());
					$player->sendMessage("§l§7[§6Jobs§7] §cคุณได้ลาออกจากอาชีพ \"$job\");
					break;
					
				}
			});
			$form->setTitle("§1JOBS");
			$job = $this->player->get($player->getName());
			$form->setContent("plugin by Antaxio discord:§4Antaxio#4013: 
			$job");
			$form->addButton("Join\", 1, "http://avengetech.me/items/271-0.png");
			$form->addButton("Status\", 1, "http://avengetech.me/items/271-0.png");
			$form->addButton("About\", 1, "http://avengetech.me/items/271-0.png");
			$form->addButton("Retire\", 1, "http://avengetech.me/items/271-0.png");
			$form->sendToPlayer($player);
			return $form;
	}
	
	public function FormJobJoin($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
				}
				switch($result){
					case "0";
					$this->player->set($player->getName(), "tree-cutter");
					$player->sendMessage("§l§7[§6Jobs§7] §aคุณได้ทำการสมัคอาชีพ §eTree-Cutter");
					break;
					
					case "1";
					$this->player->set($player->getName(), "miner");
					$player->sendMessage("§l§7[§6Jobs§7] §aคุณได้ทำการสมัคอาชีพ §eMiner");
					break;
					
					case "2";
					$this->player->set($player->getName(), "melon");
					$player->sendMessage("§l§7[§6Jobs§7] §aคุณได้ทำการสมัคอาชีพ §eMelon");
					break;
					
					case "3";
					$this->player->set($player->getName(), "pumpkin");
					$player->sendMessage("§l§7[§6Jobs§7] §aคุณได้ทำการสมัคอาชีพ §ePumpkin");
					break;
					
					case "4";
					$this->player->set($player->getName(), "flower");
					$player->sendMessage("§l§7[§6Jobs§7] §aคุณได้ทำการสมัคอาชีพ §eFlower");
					break;
					
				}
			});
			$form->setTitle("Antaxio Jobs");
			$form->addButton("Tree-Cutter\n2$", 1, "http://avengetech.me/items/17-0.png");
			$form->addButton("Mineur\n$1", 1, "http://avengetech.me/items/1-0.png");
			$form->addButton("Farmer\n5$", 1, "http://avengetech.me/items/103-0.png");
			$form->addButton("Hunter\n5$", 1, "http://avengetech.me/items/86-0.png");
			$form->addButton("Lumberjack\n1$", 1, "http://avengetech.me/items/37-0.png");
			$form->sendToPlayer($player);
			return $form;
	}
	
	public function FormInfo($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, $data = null){
		$result = $data[0];
					
		if($result === null){
			return true;
		}
			switch($result){
				case 0:
				break;
			}
		});
		$form->setTitle("Job Info");
		$form->setContent("plugin by antaxio custom plugin in data and master thank you !!");
		$form->addButton("Okey!");	
		$form->sendToPlayer($player);
	}
}
