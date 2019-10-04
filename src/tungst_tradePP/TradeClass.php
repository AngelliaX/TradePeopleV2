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
use pocketmine\event\player\PlayerQuitEvent;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\InvMenu;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;
use pocketmine\plugin\Plugin;

use muqsit\invmenu\inventories\ChestInventory;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use tungst_tradePP\Main;
use pocketmine\inventory\PlayerInventory;
use pocketmine\event\inventory\InventoryCloseEvent;
class TradeClass implements Listener {
  public $menu;
  public $owner;
  public $p1;
  public $p2;
  public $itemP1 = []; //Item of p1
  public $itemP2 = [];
  public $isLock = false;
  public $isP1acc = false;
  public $isP2acc = false;
  public function onClose(InventoryCloseEvent $e){
    if($e->getPlayer() == $this->p1 || $e->getPlayer() == $this->p2){
     
      $this->onDecline($e->getPlayer()->getName(),"§a".$e->getPlayer()->getName()." §chas closed the trade due to failed trade");
    }
   
  }
  public function onQuit(PlayerQuitEvent $e){
    if($e->getPlayer() == $this->p1 || $e->getPlayer() == $this->p2){
      $this->onDecline($e->getPlayer()->getName(),"§a".$e->getPlayer()->getName()." §chas quit the server due to failed trade");
    }
  }
  public function __construct(Main $owner,$requester,$requested){
	  $this->owner = $owner;
	  $this->p1 = $requester;
	  $this->p2 = $requested; 
	  $this->on($requester,$requested);
  }
  public function on($sender,$sender2){
      $this->menu = $menu = InvMenu::create(InvMenu::TYPE_CHEST);
	  $menu->getInventory()->setItem(18,Item::get(35,14, 1));
    $menu->getInventory()->setItem(26,Item::get(35,14, 1));
    $menu->getInventory()->setItem(4,Item::get(160,15, 1));
    $menu->getInventory()->setItem(13,Item::get(160,15, 1));
    $menu->getInventory()->setItem(22,Item::get(160,15, 1));
    $n1 = $sender->getName();$n2 = $sender2->getName();
	  $menu->send($sender,"$n1 vs $n2");
	  $menu->send($sender2,"$n1 vs $n2");
  } 
  public function onTransaction(InventoryTransactionEvent $e){
   $trans = $e->getTransaction();
	$inv = array_values($e->getTransaction()->getInventories());
  $act = array_values($e->getTransaction()->getActions());
  $itemholder = $trans->getSource();
  if($itemholder != $this->p1 && $itemholder != $this->p2){return false;}
  $count = count($inv);
  if($count == 1){
    if(!$inv[0] instanceof PlayerInventory){
    //  var_dump($inv[0]);
      $e->setCancelled();return false;}
  // print("return false");
    return false;
  }

  if($inv[0] instanceof PlayerInventory){
    print("call1");
    $this->addItem($act[0]->getSourceItem(),$act[0]->getTargetItem(),$itemholder,$e);
  }else{
    print("call2");
    $this->takeItem($act[0]->getSourceItem(),$act[0]->getTargetItem(),$itemholder,$e);
  }
  }

  public function addItem($old,$new,$holder,$e){
    if($this->isLock){$e->setCancelled();return false;}
    if($new->getId() != 0 && $old->getId() != 0){$e->setCancelled();return false;}
    $item = $old;
    if($item->getId() == 0){$item = $new;}
    $n = $holder->getName();
    if(null !== $this->owner->getConfig()->getNested("trade.$n")){
      $id = count($this->owner->getConfig()->getNested("trade.$n"));
      $this->owner->getConfig()->setNested("trade.$n.$id",utf8_encode(serialize($item)));
      $this->owner->getConfig()->setAll($this->owner->getConfig()->getAll());
      $this->owner->getConfig()->save();
    }else{
      $this->owner->getConfig()->setNested("trade.$n",[utf8_encode(serialize($new))]);
      $this->owner->getConfig()->setAll($this->owner->getConfig()->getAll());
      $this->owner->getConfig()->save();
    }
  }
  public function takeItem($old,$new,$holder,$e){
    if($new->getId() != 0 && $old->getId() != 0){$e->setCancelled();return false;}
    $item = $old;
    if($item->getId() == 0){$item = $new;}
    $n = $holder->getName();
    if(null !== $this->owner->getConfig()->getNested("trade.$n")){
      $array = $this->owner->getConfig()->getNested("trade.$n");
    //  var_dump(utf8_encode(serialize($item)));
      if(!in_array(utf8_encode(serialize($item)),$array)){
        $e->setCancelled();
        var_dump($item->getId());
        if($item->getId() == 35){
          $this->accepting($holder);
        }
        return false;
      }
      if($this->isLock){$e->setCancelled();return false;}
      $a = utf8_encode(serialize($item));		 
      unset($array[array_search($a,$array)]);    
      $this->owner->getConfig()->setNested("trade.$n",$array);
      $this->owner->getConfig()->setAll($this->owner->getConfig()->getAll());
      $this->owner->getConfig()->save();
    }else{
      $e->setCancelled();return false;
    }
  }
  public function accepting($accepter){
    $this->isLock = true;
    if($accepter == $this->p1){
      $this->isP1acc = true;
      $this->menu->getInventory()->setItem(18,Item::get(35,11, 1));
    }else{
      $this->isP2acc = true;
      $this->menu->getInventory()->setItem(26,Item::get(35,11, 1));
    }
    if($this->isP2acc && $this->isP1acc){
      $this->onDone();
    }
    
  }
  public function onDone(){
    $n1 = $this->p1->getName();$n2 = $this->p2->getName();
    if(null != $this->owner->getConfig()->getNested("trade.$n1")){
			foreach($this->owner->getConfig()->getNested("trade.$n1") as $item){
			    $itemtoadd = unserialize(utf8_decode($item));	
          if($this->p2->getInventory()->canAddItem($itemtoadd)){
            $this->p2->getInventory()->addItem($itemtoadd);
            $a = $item;
				     $array = $this->owner->getConfig()->getNested("trade.$n1");				 
				     unset($array[array_search($a,$array)]);            
				     $this->owner->getConfig()->setNested("trade.$n1",$array);
				     $this->owner->getConfig()->setAll($this->owner->getConfig()->getAll());
				     $this->owner->getConfig()->save();	
          }else{
            $core = $item;
            if(null != $this->owner->getConfig()->getNested("delayTrade.$n2")){
							$a = $this->owner->getConfig()->getNested("delayTrade.$n2");
							
              array_push($a,$core);
							$this->owner->getConfig()->setNested("delayTrade.$n2",$a);
						}else{
							$this->owner->getConfig()->setNested("delayTrade.$n2",[$core]);
						}
            $a = $item;
            $array = $this->owner->getConfig()->getNested("trade.$n1");				 
            unset($array[array_search($a,$array)]);            
            $this->owner->getConfig()->setNested("trade.$n1",$array);		
          }
  
      }
    }
    if(null != $this->owner->getConfig()->getNested("trade.$n2")){
			foreach($this->owner->getConfig()->getNested("trade.$n2") as $item){
			    $itemtoadd = unserialize(utf8_decode($item));	
          if($this->p1->getInventory()->canAddItem($itemtoadd)){
            $this->p1->getInventory()->addItem($itemtoadd);
            $a = $item;
				     $array = $this->owner->getConfig()->getNested("trade.$n2");				 
				     unset($array[array_search($a,$array)]);            
				     $this->owner->getConfig()->setNested("trade.$n2",$array);
				     $this->owner->getConfig()->setAll($this->owner->getConfig()->getAll());
				     $this->owner->getConfig()->save();	
          }else{
            $core = $item;
            if(null != $this->owner->getConfig()->getNested("delayTrade.$n1")){
							$a = $this->owner->getConfig()->getNested("delayTrade.$n1");
							
              array_push($a,$core);
							$this->owner->getConfig()->setNested("delayTrade.$n1",$a);
						}else{
							$this->owner->getConfig()->setNested("delayTrade.$n1",[$core]);
						}
             $a = $item;
				     $array = $this->owner->getConfig()->getNested("trade.$n2");				 
				     unset($array[array_search($a,$array)]);            
				     $this->owner->getConfig()->setNested("trade.$n2",$array);				
          } 
      }
    }
      $this->owner->getConfig()->setAll($this->owner->getConfig()->getAll());
      $this->owner->getConfig()->save();
      $this->p1->removeWindow($this->menu->getInventory($this->p1));
      $this->p2->removeWindow($this->menu->getInventory($this->p2));
      $this->p1->sendMessage("§bTrade §esuccessfull!!!");
      $this->p2->sendMessage("§bTrade §esuccessfull!!!");
      if(null != $this->owner->getConfig()->getNested("delayTrade.$n1")){$this->p1->sendMessage("§cYour inventory is full!!, Clean it then join again to take the item from last trade");}
      if(null != $this->owner->getConfig()->getNested("delayTrade.$n2")){$this->p2->sendMessage("§cYour inventory is full!!, Clean it then join again to take the item from last trade");}
      $this->p1 = null;$this->p2 = null;
  }
  public function onDecline($name,$reason){
    $n1 = $this->p1->getName();$n2 = $this->p2->getName();
    if(null != $this->owner->getConfig()->getNested("trade.$n1")){
			foreach($this->owner->getConfig()->getNested("trade.$n1") as $item){
			    $itemtoadd = unserialize(utf8_decode($item));	
			    $this->p1->getInventory()->addItem($itemtoadd);
  
      }
    }
    if(null != $this->owner->getConfig()->getNested("trade.$n2")){
			foreach($this->owner->getConfig()->getNested("trade.$n2") as $item){
			    $itemtoadd = unserialize(utf8_decode($item));	
			    $this->p2->getInventory()->addItem($itemtoadd);
  
      }
    }
      $this->owner->getConfig()->setNested("trade.$n1",[]);
      $this->owner->getConfig()->setNested("trade.$n2",[]);
      $this->owner->getConfig()->setAll($this->owner->getConfig()->getAll());
      $this->owner->getConfig()->save();
      if($name == $n1){
        $this->p2->removeWindow($this->menu->getInventory($this->p2));
      }else{
        $this->p1->removeWindow($this->menu->getInventory($this->p1));
      }
      $this->p1->sendMessage($reason);
      $this->p2->sendMessage($reason);
      $this->p1 = null;$this->p2 = null;

  }
}