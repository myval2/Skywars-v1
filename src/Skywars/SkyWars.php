<?php

/**
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace SkyWars;

use pocketmine\command\Command;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use skywars\arena\Arena;
use skywars\commands\SkyWarsCommand;
use skywars\math\Vector3;
use skywars\provider\YamlDataProvider;

/**
 * Class SkyWars
 * @package skywars
 */
class SkyWars extends PluginBase implements Listener {

    /** @var YamlDataProvider */
    public $dataProvider;

    /** @var Command[] $commands */
    public $commands = [];

    /** @var Arena[] $arenas */
    public $arenas = [];

    /** @var Arena[] $setters */
    public $setters = [];

    /** @var int[] $setupData */
    public $setupData = [];

    public function onEnable() {
        $this->getServer() -> getPluginManager()->registerEvents($this, $this);  $this->dataProvider = new YamlDataProvider($this);
        $this->getServer()->getCommandMap()->register("SkyWars", $this->commands[] = new SkyWarsCommand($this));
    }

    public function onDisable() {
        $this->dataProvider->saveArenas();
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();

        if(!isset($this->setters[$player->getName()])) {
            return;
        }

        $event->setCancelled(\true);
        $args = explode(" ", $event->getMessage());

        /** @var Arena $arena */
        $arena = $this->setters[$player->getName()];

        switch ($args[0]) {
            case "help":
                $player->sendMessage("§4> SkyWars installation assistance:\n".
                "§4help : View Help Commands\n" .
                "§4slots : Arena Slot(Number of People) arttır\n".
                "§4level : Select Arena World\n".
                "§4spawn : Select Arena\n".
                "§4joinsign : Select Arena Sign\n".
                "§4savelevel : Save the Changes You Make in the World\n".
                "§4enable : Activate Arena");
                break;
            case "slots":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUse: §4slot <quantity:>");
                    break;
                }
                $arena->data["slots"] = (int)$args[1];
                $player->sendMessage("§4> Number of Players $args[1] Changed!");
                break;
            case "level":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUse: §4level <World Name>");
                    break;
                }
                if(!$this->getServer()->isLevelGenerated($args[1])) {
                    $player->sendMessage("§c> $args[1] No World Found!");
                    break;
                }
                $player->sendMessage("§a> $args[1] Updated World!");
                $arena->data["level"] = $args[1];
                break;
            case "spawn":
                if(!isset($args[1])) {
                    $player->sendMessage("§cKullanım: §4setspawn <number of players:>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("§cPlease enter!");
                    break;
                }
                if((int)$args[1] > $arena->data["slots"]) {
                    $player->sendMessage("§c This Arena{$arena->data["slots"]} personality is!");
                    break;
                }

                $arena->data["spawns"]["spawn-{$args[1]}"] = (new Vector3($player->getX(), $player->getY(), $player->getZ()))->__toString();
                $player->sendMessage("§4> Join Point recorded coordinates : $args[1]  X: " . (string)round($player->getX()) . " Y: " . (string)round($player->getY()) . " Z: " . (string)round($player->getZ()));
                break;
            case "joinsign":
                $player->sendMessage("§4> Please Break the Sign!");
                $this->setupData[$player->getName()] = 0;
                break;
            case "savelevel":
                if(!$arena->level instanceof Level) {
                    $player->sendMessage("§c> The World You Specified Is Not Found.");
                    if($arena->setup) {
                        $player->sendMessage("§4> Please try again later.");
                    }
                    break;
                }
                $arena->mapReset->saveMap($arena->level);
                $player->sendMessage("§4> World Saved!");
                break;
            case "enable":
                if(!$arena->setup) {
                    $player->sendMessage("§4> This Arena is already active!");
                    break;
                }
                if(!$arena->enable()) {
                    $player->sendMessage("§c> Arena Cannot Load, You Not Complete All Information!");
                    break;
                }
                $player->sendMessage("§4> Arena Active!");
                break;
            case "tamam":
                $player->sendMessage("§4> Successfully Exited Edit Mode!");
                unset($this->setters[$player->getName()]);
                if(isset($this->setupData[$player->getName()])) {
                    unset($this->setupData[$player->getName()]);
                }
                break;
            default:
                $player->sendMessage("§4> You're in Edit Mode.\n".
                    "§4- Chat For All Commands §4§lhelp§f§4 Write\n"  .
                    "§4- To Exit Edit Mode §4§lOK§f§4 Write");
                break;
        }
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(isset($this->setupData[$player->getName()])) {
            switch ($this->setupData[$player->getName()]) {
                case 0:
                    $this->setters[$player->getName()]->data["joinsign"] = [(new Vector3($block->getX(), $block->getY(), $block->getZ()))->__toString(), $block->getLevel()->getFolderName()];
                    $player->sendMessage("§4> Updated Signboard!");
                    unset($this->setupData[$player->getName()]);
                    $event->setCancelled(\true);
                    break;
            }
        }
    }
}