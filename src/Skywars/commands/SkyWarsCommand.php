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

namespace SkyWars\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use skywars\arena\Arena;
use skywars\SkyWars;

/**
 * Class SkyWarsCommand
 * @package skywars\commands
 */
class SkyWarsCommand extends Command implements PluginIdentifiableCommand {

    /** @var SkyWars $plugin */
    protected $plugin;

    /**
     * SkyWarsCommand constructor.
     * @param SkyWars $plugin
     */
    public function __construct(SkyWars $plugin) {
        $this->plugin = $plugin;
        parent::__construct("skywars", "SkyWars ", \null, ["sw"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender->hasPermission("sw.cmd")) {
            $sender->sendMessage("§cYou Are Not Authorized to Use This Command!");
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage("§cuse: §4/sw ");
            return;
        }

        switch ($args[0]) {
            case "help":
                if(!$sender->hasPermission("sw.cmd.help")) {
                    $sender->sendMessage("§cYou Are Not Authorized to Use This Command!");
                    break;
                }
                $sender->sendMessage("§a> SkyWars commands:\n" .
                    "§4/sw All SkyWars View Commands\n".
                    "§4/sw create : SkyWars Create Arena\n".
                    "§4/sw remove : SkyWars Deletes Arena\n".
                    "§4/sw set : Edit your chosen arena\n".
                    "§4/sw arenas : View All Arenas");

                break;
            case "create":
                if(!$sender->hasPermission("sw.cmd.create")) {
                    $sender->sendMessage("§cYou Are Not Authorized to Use This Command!");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUse: §4/sw create <arena>");
                    break;
                }
                if(isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> $args[1] The Arena Is Already Available!");
                    break;
                }
                $this->plugin->arenas[$args[1]] = new Arena($this->plugin, []);
                $sender->sendMessage("§a> $args[1] Created!");
                break;
            case "remove":
                if(!$sender->hasPermission("sw.cmd.remove")) {
                    $sender->sendMessage("§cYou Are Not Authorized to Use This Command!");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUse: §4/sw remove <Arena>");
                    break;
                }
                if(!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> $args[1] No Arena Found!");
                    break;
                }

                /** @var Arena $arena */
                $arena = $this->plugin->arenas[$args[1]];

                foreach ($arena->players as $player) {
                    $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
                }

                if(is_file($file = $this->plugin->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . $args[1] . ".yml")) unlink($file);
                unset($this->plugin->arenas[$args[1]]);

                $sender->sendMessage("§a> Arena Deleted!");
                break;
            case "set":
                if(!$sender->hasPermission("sw.cmd.set")) {
                    $sender->sendMessage("§cYou Are Not Authorized to Use This Command!");
                    break;
                }
                if(!$sender instanceof Player) {
                    $sender->sendMessage("§c> Enter the command in the game!");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUse: §4/sw set <arenaismi>");
                    break;
                }
                if(isset($this->plugin->setters[$sender->getName()])) {
                    $sender->sendMessage("§c> You're already in Setup Mode");
                    break;
                }
                if(!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> $args[1] Arena not Found");
                    break;
                }
                $sender->sendMessage("§4> Join the Setup Mode.\n".
                    "§4- Chat for all commands §4§lhelp§f§7 Write\n"  .
                    "§4- Exit Edit Mode §b§lOK§f§7 write");
                $this->plugin->setters[$sender->getName()] = $this->plugin->arenas[$args[1]];
                break;
            case "arenas":
                if(!$sender->hasPermission("sw.cmd.arenas")) {
                    $sender->sendMessage("§cYou Are Not Authorized to Use This Command!");
                    break;
                }
                if(count($this->plugin->arenas) === 0) {
                    $sender->sendMessage("§4> Total 0 Arena.");
                    break;
                }
                $list = "§4> Arenas:\n";
                foreach ($this->plugin->arenas as $name => $arena) {
                    if($arena->setup) {
                        $list .= "§7- $name : §4De-Activated\n";
                    }
                    else {
                        $list .= "§7- $name : §4Active\n";
                    }
                }
                $sender->sendMessage($list);
                break;
        }

    }

    /**
     * @return SkyWars|Plugin $skywars
     */
    public function getPlugin(): Plugin {
        return $this->plugin;
    }

}
