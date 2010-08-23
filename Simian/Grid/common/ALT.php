<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Simian grid services
 *
 * PHP version 5
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    SimianGrid
 * @author     John Hurliman <http://software.intel.com/en-us/blogs/author/john-hurliman/>
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */
require_once('Inventory.php');

class ALT
{
    public $LastError = '';
    
    private $conn;

    public function __construct($db_conn)
    {
        if (!$db_conn || !($db_conn instanceof PDO))
            throw new Exception("ALT::__construct expects first parameter passed to be a valid database resource. " . print_r($db_conn, true));
        
        $this->conn = $db_conn;
    }

    public function GetLastError()
    {
        return $this->LastError;
    }

    public function InsertNode(Inventory $inventory)
    {
        if ($inventory instanceof InventoryFolder)
        {
			$sql = "INSERT INTO inventoryfolders (folderID, parentFolderID, agentID, folderName, type, version)
					VALUES (:folderID, :parentFolderID, :agentID, :folderName, :type, 1)
					ON DUPLICATE KEY UPDATE parentFolderID=VALUES(parentFolderID),
					folderName=VALUES(folderName), type=VALUES(type), version=version+1";
            
            $sth = $this->conn->prepare($sql);

			// simian stuff, e.g. new av skeletons, use string to
			// describe onject types.  need to convert that to
			// integer.
			$mimes =& get_mimes();
			$type = isset($mimes[$inventory->ContentType]) ? $mimes[$inventory->ContentType] : -1;
            
            if ($sth->execute(array(
            	':folderID' => $inventory->ID,
            	':parentFolderID' => $inventory->ParentID,
            	':agentID' => $inventory->OwnerID,
            	':folderName' => $inventory->Name,
            	':type' => $type)))
            {
                if ($inventory->ParentID != NULL)
                {
                    // Increment the parent folder version
                    $sql = "UPDATE inventoryfolder SET version=version+1 WHERE folderID=:parentFolderID";
                    $sth = $this->conn->prepare($sql);
                    $sth->execute(array(':parentFolderID' => $inventory->ParentID));
                }
                
                // Node Inserted!
                return $inventory->ID;
            }
            else
            {
                $this->LastError = sprintf("[ALT::Add/InventoryFolder] Error occurred during query: %d %s",
                        $sth->errorCode(), print_r($sth->errorInfo(), true));
                return FALSE;
            }
        }
        else if ($inventory instanceof InventoryItem)
        {
            if (isset($inventory->CreatorID))
                $creatorIDSql = ":CreatorID";
            else
                $creatorIDSql = "(SELECT CreatorID FROM assets WHERE id=:assetID)";

            if (isset($inventory->ContentType))
                $contentTypeSql = ":assetType";
            else
                $contentTypeSql = "(SELECT assetType FROM assets WHERE id=:assetID)";
            
			$sql = "INSERT INTO inventoryitems (inventoryID, assetID, parentFolderID,
					avatarID, creatorID, inventoryName, inventoryDescription, assetType,
					creationDate) VALUES (:inventoryID, :assetID, :parentFolderID,
					:avatarID, " . $creatorIDSql . ", :inventoryName, :inventoryDescription,
					" . $contentTypeSql . ", CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE
					assetID=VALUES(assetID), parentFolderID=VALUES(parentFolderID),
					creatorID=VALUES(creatorID), inventoryName=VALUES(inventoryName),
					inventoryDescription=VALUES(inventoryDescription), assetType=VALUES(assetType)";
            
            $dbValues = array(
            	':inventoryID' => $inventory->ID,
            	':assetID' => $inventory->AssetID,
            	':parentFolderID' => $inventory->ParentID,
            	':avatarID' => $inventory->OwnerID,
            	':inventoryName' => $inventory->Name,
            	':inventoryDescription' => $inventory->Description);
            if (isset($inventory->CreatorID))
                $dbValues['CreatorID'] = $inventory->CreatorID;
            if (isset($inventory->ContentType))
                $dbValues['assetType'] = $inventory->ContentType;
            
            $sth = $this->conn->prepare($sql);

            if ($sth->execute($dbValues))
            {
                // Increment the parent folder version
                $sql = "UPDATE inventoryfolders SET version=version+1 WHERE folderID=:parentFolderID";
                $sth = $this->conn->prepare($sql);
                $sth->execute(array(':parentFolderID' => $inventory->ParentID));
                
                return $inventory->ID;
            }
            else
            {
                $this->LastError = sprintf("[ALT::Add/InventoryItem] Error occurred during query: %d %s",
                    $sth->errorCode(), print_r($sth->errorInfo(), true));
                return FALSE;
            }
        }
        else
        {
            $this->LastError = "[ALT::Add] Must be either an InventoryFolder or InventoryItem, not " . gettype($inventory);
            return FALSE;
        }
    }

    public function FetchSkeleton($ownerID)
    {
        $sql = "SELECT * FROM inventoryfolders WHERE agentID=:agentID ORDER BY parentFolderID ASC";
        $sth = $this->conn->prepare($sql);
        
        $results = array();
        
        if ($sth->execute(array(':agentID' => $ownerID)))
        {
            while ($obj = $sth->fetchObject())
            {
                $results[] = $this->GetDescendant($obj);
            }
            
            return $results;
        }
        else
        {
            log_message('error', sprintf("Error occurred during query: %d %s SQL:'%s'", $sth->errorCode(), print_r($sth->errorInfo(), true), $sql));
            $this->LastError = '[ALT::Fetch] SQL Query Error ' . sprintf("Error occurred during query: %d %s %s", $sth->errorCode(), print_r($sth->errorInfo(), true), $sql);
            return FALSE;
        }
    }

    // TODO: fix me
    public function FetchDescendants($rootID, $fetchFolders = TRUE, $fetchItems = TRUE, $childrenOnly = TRUE)
    {
        if (($fetchFolders && $fetchItems) || !$childrenOnly)
            $fetchTypes = "'Folder','Item'";
        else if ($fetchFolders)
            $fetchTypes = "'Folder'";
        else
            $fetchTypes = "'Item'";
        
        $sql = "SELECT * FROM Inventory WHERE (ID=:ParentID OR ParentID=:ParentID) AND Type IN (" . $fetchTypes . ")";
        $sth = $this->conn->prepare($sql);
        
        $results = array();
        $rootFound = FALSE;
        
        // Hold a spot for the item we requested
        $results[] = '!';
        
        if ($sth->execute(array(':ParentID' => $rootID)))
        {
            while ($item = $sth->fetchObject())
            {
                $descendant = $this->GetDescendant($item);
                
                // The item we requested goes in the first slot of the array
                if ($descendant->ID == $rootID)
                {
                    $results[0] = $descendant;
                    $rootFound = TRUE;
                }
                else if ($childrenOnly || $descendant instanceof InventoryItem)
                {
                    if (($fetchItems && $descendant instanceof InventoryItem) || ($fetchFolders && $descendant instanceof InventoryFolder))
                        $results[] = $descendant;
                }
                else
                {
                    // Recursively fetch folder descendants
                    $childResults = $this->FetchDescendants($descendant->ID, $fetchFolders, $fetchItems, $childrenOnly);
                    foreach ($childResults as $child)
                    {
                        if (($fetchItems && $child instanceof InventoryItem) || ($fetchFolders && $child instanceof InventoryFolder))
                            $results[] = $child;
                    }
                }
            }
        }
        else
        {
            log_message('error', sprintf("Error occurred during query: %d %s SQL:'%s'", $sth->errorCode(), print_r($sth->errorInfo(), true), $sql));
            $this->LastError = '[ALT::Fetch] SQL Query Error ' . sprintf("Error occurred during query: %d %s %s", $sth->errorCode(), print_r($sth->errorInfo(), true), $sql);
        }
        
        if ($rootFound)
            return $results;
        else
            return FALSE;
    }

    public function MoveNodes($sourceIDs, $newParentID)
    {
        if (!is_array($sourceIDs) || count($sourceIDs) < 1)
        {
            $this->LastError = "[ALT::Move] No list of items to be moved was given";
            return FALSE;
        }
        
        $sql = "UPDATE Inventory SET ParentID=:FolderID WHERE ID=:ID0";
        $i = 0;
        
        $dbValues = array();
        $dbValues[':FolderID'] = $newParentID;
        
        foreach ($sourceIDs as $itemID)
        {
            $dbValues[':ID' . $i] = $itemID;
            
            if ($i > 0)
                $sql .= " OR ID=:ID" . $i;
            
            ++$i;
        }
        
        $sth = $this->conn->prepare($sql);
        
        if ($sth->execute($dbValues))
        {
            return TRUE;
        }
        else
        {
            log_message('error', sprintf("Error occurred during query: %d %s SQL:'%s'", $sth->errorCode(), print_r($sth->errorInfo(), true), $sql));
            $this->LastError = '[ALT::Move] SQL Query Error ' . sprintf("Error occurred during query: %d %s %s", $sth->errorCode(), print_r($sth->errorInfo(), true), $sql);
            return FALSE;
        }
    }

    public function RemoveNode($itemID, $childrenOnly = FALSE)
    {
        if (!$childrenOnly)
            $sql = "DELETE FROM Inventory WHERE ID=:ItemID";
        else
            $sql = "DELETE FROM Inventory WHERE ParentID=:ItemID";
        
        $sth = $this->conn->prepare($sql);
        
        if ($sth->execute(array(':ItemID' => $itemID)))
        {
            log_message('debug', '[ALT::Remove] Success for ' . $itemID . ', childrenOnly=' . $childrenOnly);
            return TRUE;
        }
        else
        {
            log_message('error', sprintf("Error occurred during query: %d %s SQL:'%s'", $sth->errorCode(), print_r($sth->errorInfo(), true), $sql));
            $this->LastError = '[ALT::Remove] SQL Query Error ' . sprintf("Error occurred during query: %d %s %s", $sth->errorCode(), print_r($sth->errorInfo(), true), $sql);
            return FALSE;
        }
    }
    
    private function GetDescendant($item)
    {
        $descendant = NULL;
        
        if ($item->Type == 'Folder')
        {
            $descendant = new InventoryFolder(UUID::Parse($item->ID));
            
            if (!UUID::TryParse($item->ParentID, $descendant->ParentID))
                $descendant->ParentID = UUID::Parse(UUID::Zero);
            
            $descendant->OwnerID = UUID::Parse($item->OwnerID);
            $descendant->Name = $item->Name;
            $descendant->ContentType = $item->ContentType;
            $descendant->Version = $item->Version;
            $descendant->ExtraData = $item->ExtraData;
        }
        else
        {
            $descendant = new InventoryItem(UUID::Parse($item->ID));
            $descendant->AssetID = UUID::Parse($item->AssetID);
            $descendant->ParentID = UUID::Parse($item->ParentID);
            $descendant->OwnerID = UUID::Parse($item->OwnerID);
            $descendant->CreatorID = UUID::Parse($item->CreatorID);
            $descendant->Name = $item->Name;
            $descendant->Description = $item->Description;
            $descendant->ContentType = $item->ContentType;
            $descendant->Version = $item->Version;
            $descendant->ExtraData = $item->ExtraData;
            $descendant->CreationDate = gmdate('U', (int)$item->CreationDate);
        }
        
        return $descendant;
    }
}
