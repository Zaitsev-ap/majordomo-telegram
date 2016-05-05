<?php
/**
* Telegram Bot 
*
*
* @package project
* @author Isupov Andrey <eraser1981@gmail.com>
* @copyright (c)
*/
//
//
class telegram extends module {
/**
* blank
*
* Module class constructor
*
* @access private
*/
function telegram() {
  $this->name="telegram";
  $this->title="Telegram";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams() {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab; 
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
    global $ajax; 
    global $filter;
    global $atype;
    
    if ($ajax) {
        header ("HTTP/1.0: 200 OK\n");
        header ('Content-Type: text/html; charset=utf-8');
        $limit=50;
        
        // Find last midifed
        $filename=ROOT.'debmes/log_*-cycle_telegram.php.txt';
        foreach(glob($filename) as $file) {      
          $LastModified[] = filemtime($file);
          $FileName[] = $file;
        }    
        $files = array_multisort($LastModified, SORT_NUMERIC, SORT_ASC, $FileName);
        $lastIndex = count($LastModified) - 1;
    
        // Open file
        $data=LoadFile( $FileName[$lastIndex] );    
    
        $lines=explode("\n", $data);
        $lines=array_reverse($lines);
        $res_lines=array();
        $total=count($lines);
        $added=0;
        for($i=0;$i<$total;$i++) {
            if (trim($lines[$i])=='') {
            continue;
            }

            if ($filter && preg_match('/'.preg_quote($filter).'/is', $lines[$i])) {
                $res_lines[]=$lines[$i];
                $added++;
            } elseif (!$filter) {
                $res_lines[]=$lines[$i];
                $added++;
            }

            if ($added>=$limit) {
                break;
            }
        }

    echo implode("<br/>", $res_lines);
    exit;
    }
    
    $this->getConfig();
    $out['TLG_TOKEN']=$this->config['TLG_TOKEN'];
    $out['TLG_STORAGE']=$this->config['TLG_STORAGE'];
    $out['TLG_DEBUG']=$this->config['TLG_DEBUG'];
    $out['TLG_test']=$this->data_source."_".$this->view_mode."_".$this->tab;
    if ($this->data_source=='telegram' || $this->data_source=='') {
        if ($this->view_mode=='update_settings') {
            global $tlg_token;
            $this->config['TLG_TOKEN']=$tlg_token;
            global $tlg_storage;
            $this->config['TLG_STORAGE']=$tlg_storage;
            global $tlg_debug;
            $this->config['TLG_DEBUG']=$tlg_debug;
            $this->saveConfig();
            $this->redirect("?");      
            }
        if ($this->view_mode=='user_edit') {
            $this->edit_user($out, $this->id);
        }
        if ($this->view_mode=='cmd_edit') {
            $this->edit_cmd($out, $this->id);
        }
        if ($this->view_mode=='user_delete') {
          $this->delete_user($this->id);
          $this->redirect("?");
        } 
        if ($this->view_mode=='cmd_delete') {
          $this->delete_cmd($this->id);
          $this->redirect("?");
        } 
        
        if ($this->view_mode=='' || $this->view_mode=='search_ms') {
          if ($this->tab=='cmd'){
            $this->tlg_cmd($out);
          } else if ($this->tab=='log'){
            $this->tlg_log($out);
          } else {
            $this->tlg_users($out);
          }
        }
    }
}

/**
* Edit/add
*
* @access public
*/
function edit_user(&$out, $id) {
  require(DIR_MODULES.$this->name.'/user_edit.inc.php');
}

function edit_cmd(&$out, $id) {
  require(DIR_MODULES.$this->name.'/cmd_edit.inc.php');
}
/**
* Delete user
*
* @access public
*/
function delete_user($id) {
  $rec=SQLSelectOne("SELECT * FROM tlg_user WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM tlg_user WHERE ID='".$rec['ID']."'"); 
  SQLExec("DELETE FROM tlg_user_cmd WHERE USER_ID='".$rec['ID']."'"); 
}
function delete_CMD($id) {
  $rec=SQLSelectOne("SELECT * FROM tlg_cmd WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM tlg_cmd WHERE ID='".$rec['ID']."'"); 
  SQLExec("DELETE FROM tlg_user_cmd WHERE CMD_ID='".$rec['ID']."'"); 
}

function tlg_users(&$out) {
  require(DIR_MODULES.$this->name.'/tlg_users.inc.php');
}

function tlg_log(&$out) {
  require(DIR_MODULES.$this->name.'/tlg_log.inc.php');
}

function tlg_cmd(&$out) {
  require(DIR_MODULES.$this->name.'/tlg_cmd.inc.php');
}

function getKeyb($user) {
    $visible = true;
    // Create option for the custom keyboard. Array of array string
    if ($user['ADMIN'] == 0 && $user['CMD']==0)
    {
        $option = array( );
        $visible = false;
    }
    else
    {
        //$option = array( array("A", "B"), array("C", "D") );
        $option = array();
        $rec=SQLSelect("SELECT *,(select VALUE from pvalues where Property_name=`LINKED_OBJECT`+'.'+`LINKED_PROPERTY` ORDER BY updated DESC limit 1) as pvalue".
                " FROM tlg_cmd INNER JOIN tlg_user_cmd on tlg_cmd.ID=tlg_user_cmd.CMD_ID where tlg_user_cmd.USER_ID=".$user['ID']." and ACCESS>0 order by tlg_cmd.ID;");  
        $total=count($rec);
        if ($total) {
            for($i=0;$i<$total;$i++) {
                $view = false;
                if ($rec[$i]["SHOW_MODE"] == 1)
                    $view = true;
                elseif ($rec[$i]["SHOW_MODE"] == 3)
                {
                    if ($rec[$i]["CONDITION"] == 1 && $rec[$i]["pvalue"] == $rec[$i]["CONDITION_VALUE"]) $view = true;
                    if ($rec[$i]["CONDITION"] == 2 && $rec[$i]["pvalue"] >  $rec[$i]["CONDITION_VALUE"]) $view = true;
                    if ($rec[$i]["CONDITION"] == 3 && $rec[$i]["pvalue"] <  $rec[$i]["CONDITION_VALUE"]) $view = true;
                    if ($rec[$i]["CONDITION"] == 4 && $rec[$i]["pvalue"] <> $rec[$i]["CONDITION_VALUE"]) $view = true;
                }
                if ($view)
                    $option[] = $rec[$i]["TITLE"];
            }
            $option = array_chunk($option, 3);
        }
    }
    
    // Get the keyboard
    $telegramBot = new TelegramBot("");
    $keyb = $telegramBot->buildKeyBoard($option , $resize= true,$selective = $visible);
    //print_r($keyb);
    return $keyb;
}


function sendContent($content) {
    $this->getConfig();
    include_once("./modules/telegram/Telegram.php");
    $telegramBot = new TelegramBot($this->config['TLG_TOKEN']);
    if ($this->config['TLG_DEBUG'])
        print_r ($content);
    $res = $telegramBot->sendMessage($content);
    if ($this->config['TLG_DEBUG'])
        print_r ($res);
}

function getUsers($where)
{
    $query = "SELECT * FROM tlg_user";
    if ($where!="")
        $query = $query." WHERE ".$where;
    $users=SQLSelect($query); 
    return $users;
}

// send message
function sendMessageTo($where, $message,array $key = NULL) {
    $this->getConfig();
    include_once("./modules/telegram/Telegram.php");
    $telegramBot = new TelegramBot($this->config['TLG_TOKEN']);
    $users = $this->getUsers($where);
    foreach ($users as $user)
    {
            $user_id = $user['USER_ID'];
            if ($key == NULL)
                $keyboard = $this->getKeyb($user);
            else 
                $keyboard = $telegramBot->buildKeyBoard($key , $resize= true);
            $content = array('chat_id' => $user_id, 'text' => $message, 'reply_markup' => $keyboard, 'parse_mode'=>'HTML');
            $res = $telegramBot->sendMessage($content);
            if ($this->config['TLG_DEBUG'])
            {
                print_r ($res);
            }
    }
}

function sendMessageToUser($user_id, $message,$key = NULL) {
    $this->sendMessageTo("USER_ID=".$user_id, $message, $key); 
}

function sendMessageToAdmin($message, $key = NULL) {
    $this->sendMessageTo("ADMIN=1", $message, $key); 
}

function sendMessageToAll($message, $key = NULL) {
    $this->sendMessageTo("", $message, $key); 
}

///send image
function sendImageTo($where, $image_path, array $key = NULL) {
    $this->getConfig();
    include_once("./modules/telegram/Telegram.php");
    $telegramBot = new TelegramBot($this->config['TLG_TOKEN']);
    $img = curl_file_create($image_path,'image/png'); 
    $users = $this->getUsers($where);
    foreach ($users as $user)
    {
        $user_id = $user['USER_ID'];
            if ($key == NULL)
                $keyboard = $this->getKeyb($user);
            else 
                $keyboard = $telegramBot->buildKeyBoard($keyboard , $resize= true);
            $content = array('chat_id' => $user_id, 'photo' => $img, 'reply_markup' => $keyboard);
            $res = $telegramBot->sendPhoto($content);
            if ($this->config['TLG_DEBUG'])
				print_r ($res);
    }
}

function sendImageToUser($user_id, $image_path, $key = NULL) {
    $this->sendImageTo("USER_ID=".$user_id, $image_path, $key); 
}

function sendImageToAdmin($image_path, $key = NULL) {
    $this->sendImageTo("ADMIN=1", $image_path, $key); 
}

function sendImageToAll($image_path, $key = NULL) {
    $this->sendImageTo("", $image_path, $key); 
}


function sendFileTo($where, $file_path, array $key = NULL) {
    $this->getConfig();
    include_once("./modules/telegram/Telegram.php");
    $telegramBot = new TelegramBot($this->config['TLG_TOKEN']);
    $file = curl_file_create($file_path); 
    $users = $this->getUsers($where);
    foreach ($users as $user)
    {
        $user_id = $user['USER_ID'];
            if ($key == NULL)
                $keyboard = $this->getKeyb($user);
            else 
                $keyboard = $telegramBot->buildKeyBoard($keyboard , $resize= true);
            $content = array('chat_id' => $user_id, 'document' => $file, 'reply_markup' => $keyboard);
            $res = $telegramBot->sendDocument($content);
			if ($this->config['TLG_DEBUG'])
				print_r ($res);
    }
}

function sendFileToUser($user_id, $file_path, $key = NULL) {
    $this->sendFileTo("USER_ID=".$user_id, $file_path, $key); 
}

function sendFileToAdmin($file_path, $key = NULL) {
    $this->sendFileTo("ADMIN=1", $file_path, $key); 
}

function sendFileToAll($file_path, $key = NULL) {
    $this->sendFileTo("", $file_path, $key); 
}

function sendStickerTo($where, $sticker, array $key = NULL) {
    $this->getConfig();
    include_once("./modules/telegram/Telegram.php");
    $telegramBot = new TelegramBot($this->config['TLG_TOKEN']);
    $users = $this->getUsers($where);
    foreach ($users as $user)
    {
        $user_id = $user['USER_ID'];
            if ($key == NULL)
                $keyboard = $this->getKeyb($user);
            else 
                $keyboard = $telegramBot->buildKeyBoard($keyboard , $resize= true);
            $content = array('chat_id' => $user_id, 'sticker' => $sticker, 'reply_markup' => $keyboard);
            $res = $telegramBot->sendSticker($content);
			if ($this->config['TLG_DEBUG'])
				print_r ($res);
    }
}

function sendStickerToUser($user_id, $sticker, $key = NULL) {
    $this->sendStickerTo("USER_ID=".$user_id, $sticker, $key); 
}

function sendStickerToAdmin($sticker, $key = NULL) {
    $this->sendStickerTo("ADMIN=1", $sticker, $key); 
}

function sendStickerToAll($sticker, $key = NULL) {
    $this->sendStickerTo("", $sticker, $key); 
}

function sendLocationTo($where, $lat, $lon, array $key = NULL) {
    $this->getConfig();
    include_once("./modules/telegram/Telegram.php");
    $telegramBot = new TelegramBot($this->config['TLG_TOKEN']);
    $users = $this->getUsers($where);
    foreach ($users as $user)
    {
        $user_id = $user['USER_ID'];
            if ($key == NULL)
                $keyboard = $this->getKeyb($user);
            else 
                $keyboard = $telegramBot->buildKeyBoard($keyboard , $resize= true);
            $content = array('chat_id' => $user_id, 'latitude' => $lat, 'longitude' => $lon, 'reply_markup' => $keyboard);
            $res = $telegramBot->sendLocation($content);
			if ($this->config['TLG_DEBUG'])
				print_r ($res);
    }
}

function sendLocationToUser($user_id, $lat, $lon, $key = NULL) {
    $this->sendLocationTo("USER_ID=".$user_id, $lat, $lon, $key); 
}

function sendLocationToAdmin($lat, $lon, $key = NULL) {
    $this->sendLocationTo("ADMIN=1", $lat, $lon, $key); 
}

function sendLocationToAll($lat, $lon, $key = NULL) {
    $this->sendLocationTo("", $lat, $lon, $key); 
}

function init() {
    $this->getConfig();
    $this->lastID = 0;
    echo "Token bot - ".$this->config['TLG_TOKEN']."\n";
    $rec = SQLSelectOne("SELECT * FROM `shouts` ORDER BY `ID` DESC LIMIT 1"); 
    if ($rec)
        $this->lastID = $rec['ID'];  
    echo "Shouts LastID=".$this->lastID."\n";
    // create bot
    require("./modules/telegram/Telegram.php");
    $telegramBot = new TelegramBot($this->config['TLG_TOKEN']);
    $me=$telegramBot->getMe();
    if ($me)
        echo "Me: @".$me["result"]["username"]." (".$me["result"]["id"].")\n"; 
    else
        echo "Error connect, invalid token\n";
}

function processCycle() {
    $this->getConfig();
    $telegramBot = new TelegramBot($this->config['TLG_TOKEN']);
    $me=$telegramBot->getMe();
    $bot_name = $me["result"]["username"]; 
    
    // отправка истории
    $rec=SQLSelect("SELECT * FROM `shouts` where ID > ".$this->lastID." order by ID;");  
    $total=count($rec);
    if ($total) {
        // найти кому отправить
        $users=SQLSelect("SELECT * FROM tlg_user WHERE HISTORY=1;"); 
        $c_users=count($users);
        if ($c_users) {
            for($i=0;$i<$total;$i++) {
                $reply = $rec[$i]['MESSAGE'];
                //отправлять всем у кого есть разрешения на получение истории
                for($j=0;$j<$c_users;$j++) {
                    $user_id = $users[$j]['USER_ID'];
                    //самому себе не отправлять
                    if (($rec[$i]['MEMBER_ID'] != $users[$j]['MEMBER_ID']) &&
                        ($rec[$i]['IMPORTANCE'] >= $users[$j]['HISTORY_LEVEL']))
                    {
                        echo  date("Y-m-d H:i:s ")." Send to ".$user_id." - ".$reply."\n";
                        $keyb = $this->getKeyb($users[$j]);
                        $content = array('chat_id' => $user_id, 'text' => $reply, 'reply_markup' => $keyb);
                        $this->sendContent($content);
                    }
                }
                echo  date("Y-m-d H:i:s ")." Sended - ".$reply."\n";
                $this->lastID = $rec[$i]['ID'];
            }
        }
        else
            $this->lastID = $rec[$total-1]['ID'];
    }  

    
    // Get all the new updates and set the new correct update_id
    $req = $telegramBot->getUpdates($timeout=5);
    for ($i = 0; $i < $telegramBot-> UpdateCount(); $i++) {
        // You NEED to call serveUpdate before accessing the values of message in Telegram Class
        $telegramBot->serveUpdate($i);
        $data = $telegramBot->getData();
        if ($this->config['TLG_DEBUG'])
        {
            print_r($data);
        }
        $text = $telegramBot->Text();
        $chat_id = $telegramBot->ChatID();
        $document = $telegramBot->Document();
        $audio = $telegramBot->Audio();
        $voice = $telegramBot->Voice();
        $sticker = $telegramBot->Sticker();
        $photo_id = $telegramBot->PhotoIdBigSize();
		$location = $telegramBot->Location();
        // найти в базе пользователя
		$user=SQLSelectOne("SELECT * FROM tlg_user WHERE USER_ID LIKE '".DBSafe($chat_id)."';"); 
		if ($location) 
        {
			$latitude = $location["latitude"];
			$longitude = $location["longitude"];
			echo  date("Y-m-d H:i:s ")." Get location from ".$chat_id." - ".$latitude.",".$longitude."\n";
			if ($user['MEMBER_ID'])
		    {
				$sqlQuery = "SELECT * FROM users WHERE ID = '" . $user['MEMBER_ID'] . "'";
				$userObj = SQLSelectOne($sqlQuery);
				if ($userObj['LINKED_OBJECT'])
				{
					echo  date("Y-m-d H:i:s ")." Update location to user '".$userObj['LINKED_OBJECT']."'\n";
					setGlobal($userObj['LINKED_OBJECT'] . '.Coordinates', $latitude . ',' . $longitude);
					setGlobal($userObj['LINKED_OBJECT'] . '.CoordinatesUpdated', date('H:i'));
					setGlobal($userObj['LINKED_OBJECT'] . '.CoordinatesUpdatedTimestamp', time());
				}
			}
			continue;
		}
        //permission download file
        if ($user['DOWNLOAD']==1)
        {
            //папку с файлами в настройках
            $storage = $this->config['TLG_STORAGE'].DIRECTORY_SEPARATOR;
            if ($photo_id) 
            {
                $file = $telegramBot->getFile($photo_id);
                echo  date("Y-m-d H:i:s ")." Get photo from ".$chat_id." - ".$file["result"]["file_path"]."\n";
                $file_path = $storage.$chat_id.DIRECTORY_SEPARATOR.$file["result"]["file_path"];
            }
            if ($document) 
            {
                $file = $telegramBot->getFile($document["file_id"]);
                echo  date("Y-m-d H:i:s ")." Get document from ".$chat_id." - ".$document["file_name"]."\n";
                //print_r($file);
                if(!isset($file['error_code'])) 
                {
                    $file_path = $storage.$chat_id.DIRECTORY_SEPARATOR."document".DIRECTORY_SEPARATOR.$document["file_name"];
                }
                else
                {
                    $file_path = "";
                    echo  date("Y-m-d H:i:s ").$file['description']."\n";
                }
            }
            if ($audio) 
            {
                $file = $telegramBot->getFile($audio["file_id"]);
                //print_r($file);
                echo  date("Y-m-d H:i:s ")." Get audio from ".$chat_id." - ".$file["result"]["file_path"]."\n";
                $path_parts = pathinfo($file["result"]["file_path"]);
                $filename = $path_parts["basename"];
                //use title and performer
                if(isset($audio['title'])) $filename = $audio['title'].".".$path_parts['extension'];
                if(isset($audio['performer'])) $filename = $audio['performer']."-".$filename;
                $file_path = $storage.$chat_id.DIRECTORY_SEPARATOR."audio".DIRECTORY_SEPARATOR.$filename;
            }
            if ($voice) 
            {
                $file = $telegramBot->getFile($voice["file_id"]);
                //print_r($file);
                echo  date("Y-m-d H:i:s ")." Get voice from ".$chat_id." - ".$file["result"]["file_path"]."\n";
                $file_path = $storage.$chat_id.DIRECTORY_SEPARATOR.$file["result"]["file_path"];
            }
            if ($sticker) 
            {
                $file = $telegramBot->getFile($sticker["file_id"]);
                echo  date("Y-m-d H:i:s ")." Get sticker from ".$chat_id." - ".$sticker["file_id"]."\n";
                //$file_path = $storage.$chat_id.DIRECTORY_SEPARATOR.$file["result"]["file_path"];
            }
            if ($file_path){ 
                // качаем файл
                $path_parts = pathinfo($file_path);
                if (!is_dir($path_parts['dirname'])) mkdir($path_parts['dirname'], 0777, true);
                $telegramBot->downloadFile($file["result"]["file_path"], $file_path);
            }
            if ($voice && $user['PLAY']==1) 
            {
                //проиграть голосовое сообщение
                echo  date("Y-m-d H:i:s ")." Play voice from ".$chat_id." - ".$file_path."\n";
                @touch($file_path);
                playSound($file_path, 1, $level);
            }
            $file_path = "";
        }    
        if ($text=="") {
            continue;
        }
        echo  date("Y-m-d H:i:s ").$chat_id."=".$text."\n";

        if ($text == "/start" || $text == "/start@".$bot_name) {
            // найти в базе пользователя
            // если нет добавляем
            $user=SQLSelectOne("SELECT * FROM tlg_user WHERE USER_ID LIKE '".DBSafe($chat_id)."';"); 
            if (!$user['ID']) {
                $user['USER_ID']=$chat_id;
                $name = $telegramBot->Username();
                $user['NAME']=$name;
                $user['CREATED'] = date('Y/m/d H:i:s');
                $user['ID']=SQLInsert('tlg_user', $user);
                echo  date("Y-m-d H:i:s ")." Added - ".$name."-".$chat_id."\n";
            } 
            
            $reply = "Вы зарегистрированы! Обратитесь к администратору для получения доступа к функциям.";
            $content = array('chat_id' => $chat_id, 'text' => $reply);
            $this->sendContent($content);
            continue;
        }
        
        if ($user['ID']) {
            //смотрим разрешения на обработку команд
            if ($user['ADMIN']==1 || $user['CMD']==1)
            {
                $keyb = $this->getKeyb($user);
                $cmd=SQLSelectOne("SELECT * FROM tlg_cmd INNER JOIN tlg_user_cmd on tlg_cmd.ID=tlg_user_cmd.CMD_ID where tlg_user_cmd.USER_ID=".$user['ID']." and ACCESS>0 and '".DBSafe($text)."' LIKE CONCAT(TITLE,'%');"); 
                if ($cmd['ID']) {
                    echo  date("Y-m-d H:i:s ")." Find command\n";
                    //нашли команду
                    if ($cmd['CODE'])
                    {
                        echo  date("Y-m-d H:i:s ")." Execute user`s code command\n";
                        try {
                            $success = eval($cmd['CODE']);
                            echo  date("Y-m-d H:i:s ")." Command:".$text." Result:".$success."\n";
                            if ($success == false) {
                                //нет в выполняемом куске кода return
                                //$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Ошибка выполнения кода команды ".$text);
                                //$telegramBot->sendMessage($content);
                            }
                            else
                            {
                                $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $success, 'parse_mode'=>'HTML');
                                $this->sendContent($content);
                                echo  date("Y-m-d H:i:s ")." Send result to ".$chat_id.". Command:".$text." Result:".$success."\n";
                            }
                            
                        } catch (Exception $e) {
                            registerError('telegram', sprintf('Exception in "%s" method '.$e->getMessage(), $text));
                            $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Ошибка выполнения кода команды ".$text);
                            $this->sendContent($content);
                        }
                        continue;
                    }
                    // если нет кода, который надо выполнить, то передаем дальше на обработку
                }
                else
                    echo  date("Y-m-d H:i:s ")." Command not found\n";
                
                if ($text == "/test") {
                    if ($telegramBot->messageFromGroup()) {
                        $reply = "Chat Group";
                    } else {
                        $reply = "Private Chat";
                    }
                        
                    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $reply);
                    $this->sendContent($content);
                } 
                else if ($text == "/git") {
                    $reply = "Check me on GitHub: https://github.com/Eleirbag89/TelegramBotPHP";
                    // Build the reply array
                    $content = array('chat_id' => $chat_id, 'text' => $reply);
                    $this->sendContent($content);
                }
                else
                {
                    $rec=array();
                    $rec['ROOM_ID']=0;
                    $rec['MEMBER_ID']=$user['MEMBER_ID'];
                    $rec['MESSAGE']=htmlspecialchars($text);
                    $rec['ADDED']=date('Y-m-d H:i:s');
                    SQLInsert('shouts', $rec);
                    
                    include_once(DIR_MODULES.'patterns/patterns.class.php');
                    $pt=new patterns();
                    echo  date("Y-m-d H:i:s ")." Check pattern \n";
                    $res=$pt->checkAllPatterns($rec['MEMBER_ID']);
                    if (!$res) {
                        echo  date("Y-m-d H:i:s ")." Pattern not found. Run ThisComputer.processCommand\n";
                        getObject("ThisComputer")->callMethod("commandReceived", array("command" => $text));
                    } 
                }

            }
        }
    }
} 

/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install() {
  parent::install();
 }
 
 /**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS tlg_user_cmd');
  SQLExec('DROP TABLE IF EXISTS tlg_user');
  SQLExec('DROP TABLE IF EXISTS tlg_cmd');
  parent::uninstall();
 }
 
 /**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
  $data = <<<EOD
 tlg_user: ID int(10) unsigned NOT NULL auto_increment
 tlg_user: NAME varchar(255) NOT NULL DEFAULT ''
 tlg_user: USER_ID int(10) NOT NULL DEFAULT '0'
 tlg_user: MEMBER_ID int(10) NOT NULL DEFAULT '1'
 tlg_user: CREATED datetime
 tlg_user: ADMIN int(3) unsigned NOT NULL DEFAULT '0' 
 tlg_user: HISTORY int(3) unsigned NOT NULL DEFAULT '0' 
 tlg_user: HISTORY_LEVEL int(3) unsigned NOT NULL DEFAULT '0' 
 tlg_user: CMD int(3) unsigned NOT NULL DEFAULT '0' 
 tlg_user: DOWNLOAD int(3) unsigned NOT NULL DEFAULT '0' 
 tlg_user: PLAY int(3) unsigned NOT NULL DEFAULT '0' 
 
 tlg_cmd: ID int(10) unsigned NOT NULL auto_increment
 tlg_cmd: TITLE varchar(255) NOT NULL DEFAULT ''
 tlg_cmd: DESCRIPTION text
 tlg_cmd: CODE text
 tlg_cmd: ACCESS int(10) NOT NULL DEFAULT '0'
 tlg_cmd: SHOW_MODE int(10) NOT NULL DEFAULT '1'
 tlg_cmd: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 tlg_cmd: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT '' 
 tlg_cmd: CONDITION int(10) NOT NULL DEFAULT '1' 
 tlg_cmd: CONDITION_VALUE varchar(255) NOT NULL DEFAULT '' 
 
 tlg_user_cmd: ID int(10) unsigned NOT NULL auto_increment
 tlg_user_cmd: USER_ID int(10) NOT NULL
 tlg_user_cmd: CMD_ID int(10) NOT NULL
 
EOD;
  parent::dbInstall($data);
  
  $cmds=SQLSelectOne("SELECT * FROM tlg_cmd;"); 
  if (count($cmds)==0) {
      $rec['TITLE']='Ping';
      $rec['DESCRIPTION']='Example command Ping-Pong';
      $rec['CODE']='return "Pong!";';
      $rec['ACCESS']=2;
      SQLInsert('tlg_cmd', $rec);
  }
 }
// --------------------------------------------------------------------
}
?>