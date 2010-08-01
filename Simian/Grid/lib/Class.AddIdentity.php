<?php
/** Simian grid services
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
 * @author     Jim Radford <http://www.jimradford.com/>
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */

class AddIdentity implements IGridService
{
    private $UserID;

    public function Execute($db, $params)
    {
        if (isset($params["Identifier"], $params["Credential"], $params["Type"], $params["UserID"]) && UUID::TryParse($params["UserID"], $this->UserID))
        {

			// right now we don't care about a1hash (used by WebDAV
			// interface to inventory).  a1hash is easy to contruct,
			// so we don't need to explicitly add it to the db
			// anywhere.
			if($params["Type"] == 'a1hash')
			{
				header("Content-Type: application/json", true);
				echo '{ "Success": true }';
				exit();
			}

			// generate salt.  bail if crypto_strong == false...
			$new_salt = openssl_random_pseudo_bytes(16, $good_crypto);
			if ($good_crypto) {
				$new_salt = bin2hex($new_salt);
			} else {
				header("Content-Type: application/json", true);
				echo '{ "Message": "OpenSSL error" }';
				exit();
			}

			// ROBUST doesn't use $1$salt$... format so strip it...
			$new_password = preg_replace('/^.*\$/', '', $params["Credential"]);

			// salt it...
			$new_password = md5($new_password . ":" . $new_salt);

            $sql = "INSERT INTO auth (UUID, passwordHash, passwordSalt, webLoginKey, accountType)
            		VALUES (:UUID, :passwordHash, :passwordSalt, :webLoginKey, :accountType)
            		ON DUPLICATE KEY UPDATE passwordHash=VALUES(passwordHash), passwordSalt=VALUES(passwordSalt)";
            
            $sth = $db->prepare($sql);
            
            if ($sth->execute(array(':UUID' => $params["UserID"], ':passwordHash' => $new_password,
					':passwordSalt' => $new_salt, ':webLoginKey' => '00000000-0000-0000-0000-000000000000',
					':accountType' => 'UserAccount')))
            {
                header("Content-Type: application/json", true);
                echo '{ "Success": true }';
                exit();
            }
            else
            {
                log_message('error', sprintf("Error occurred during query: %d %s", $sth->errorCode(), print_r($sth->errorInfo(), true)));
                log_message('debug', sprintf("Query: %s", $sql));
                
                header("Content-Type: application/json", true);
                echo '{ "Message": "Database query error" }';
                exit();
            }
        }
        else
        {
            header("Content-Type: application/json", true);
            echo '{ "Message": "Invalid parameters" }';
            exit();
        }
    }
}
