<?php

namespace tungst_tradePP;

use pocketmine\plugin\PluginBase;
use pocketmine\Player; 
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerJoinEvent;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\inventories\ChestInventory;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use tungst_tradePP\TradeClass;
use tungst_tradePP\CheckTask;
use pocketmine\scheduler\TaskScheduler;
class Main extends PluginBase implements Listener {

    public $task;
	public $request = [];
	public function onEnable(){
		$this->getLogger()->info("Trade enable");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onJoin(PlayerJoinEvent $e){
		$p = $e->getPlayer();$n = $p->getName();
		if(null != $this->getConfig()->getNested("delayTrade.$n")){
			foreach($this->getConfig()->getNested("delayTrade.$n") as $item){
			   $itemtoadd = unserialize(utf8_decode($item));	
			   if($p->getInventory()->canAddItem($itemtoadd)){
			      	$p->getInventory()->addItem($itemtoadd);
				 	 $a = $item;
				     $array = $this->getConfig()->getNested("delayTrade.$n");				 
				     unset($array[array_search($a,$array)]);            
				     $this->getConfig()->setNested("delayTrade.$n",$array);
				     $this->getConfig()->setAll($this->getConfig()->getAll());
				     $this->getConfig()->save();	
				    $p->sendMessage("\n§aYou have receive an item from last trade\n");
			   }else{
                $p->sendMessage("\n§cClean your inventory then join sv again to get an item from last trade\n");
			   }
			}
		}
	}
	public function onCommand(CommandSender $sender, Command $command, String $label, array $args) : bool {
        //string $minname; 
		if($sender instanceof Player){
		   switch(strtolower($command->getName())){
               case "trade":
			    if(!isset($args[0])){
					$sender->sendMessage("Use /trade help");
					return false;
				}else{
			     switch(strtolower($args[0])){   
					case "h":
				 case "help":
				  $sender->sendMessage("/trade {name} to trade with that player");
				  $sender->sendMessage("/trade accept to accept trade");
				  $sender->sendMessage("/trade decline to quickly decline a trade invite (auto decline after 10s)");
				  break;
				  case "a":
				 case "accept":
				  if(in_array($sender->getName(),$this->request)){			
					  var_dump(array_search($sender->getName(),$this->request));
						if($this->getServer()->getPlayer(array_search($sender->getName(),$this->request)) instanceof Player){
							$a = new TradeClass($this,$this->getServer()->getPlayer(array_search($sender->getName(),$this->request)),$sender);
							$this->getServer()->getPluginManager()->registerEvents($a, $this);		
						    unset($this->request[array_search($sender->getName(),$this->request)]);
						}
				  }else{
					$sender->sendMessage("§cYou dont have any trade request"); 
				  }
				  break;
				  case "d":
				 case "decline":
				   if(in_array($sender,$this->request)){
				    unset($this->request[array_search($sender,$this->request)]);
					$sender->sendMessage("§aDecline successful");
				   }else{
					$sender->sendMessage("§cYou dont have any trade request");
				   }
				  break;			
				 default:		    
                         if($this->getServer()->getPlayer($args[0]) != null && $this->getServer()->getPlayer($args[0]) != $sender){
				   	       $this->request[$sender->getName()] = $this->getServer()->getPlayer($args[0])->getName();
							  $this->getServer()->getPlayer($args[0])->sendMessage("§a".$sender->getName(). " want to trade with you,auto decline after 30s");
						   $sender->sendMessage("§aSent a request to ".$this->getServer()->getPlayer($args[0])->getName());
							  $this->getScheduler()->scheduleDelayedTask(new CheckTask($this,$sender->getName(),$this->getServer()->getPlayer($args[0])->getName()), 1200);				  
						 }else{
					       $sender->sendMessage("§cCant find that player");
						 }
							      
                   break;
				 }
				}
		   }
		
		
		}
	return true;
	}
}