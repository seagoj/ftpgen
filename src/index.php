<?php
/**
 * FTPgen: index
 *
 * Generates limited usage FTP accounts via FileZilla
 * 
 * @name      index.php
 * @category  Seagoj
 * @package   Ftpgen
 * @author    Jeremy Seago <seagoj@gmail.com>
 * @copyright 2012 Jeremy Seago
 * @license   http://opensource.org/licenses/mit-license.php, MIT
 * @version   1.0
 * @link      https://github.com/seagoj/ftpgen
 */
namespace Ftpgen;

require_once '../autoloader.php';

$dbg = new \Devtools\Dbg($this);
$dbg->msg('It\'s alive!');

$xmlfolder = 'C:\\Program Files (x86)\\FileZilla Server\\';
$xmlfilename = 'FileZilla Server.xml';

$ftpRoot = 'D:/ftproot/Private/';
$ftpDocumentation = 'C:/Dokumentation ftp server/';

$xmlfile = $xmlfolder . $xmlfilename;
$xmlbackupfile = $xmlfolder . @date("Y-m-d-H-i-s") . '_FileZilla_Server.xml';


// Copy Config for backup
createXMLbackup($xmlfile,$xmlbackupfile);



//Load XML file
$xml = simplexml_load_file($xmlfile);

$msg = "Allowed usernames: 20 characters out of a...z A...Z 0...9 _ \n\nPlease input username (Ctrl+C to quit)";

while(true)
{

// Copy Config for backup before each change, too.
createXMLbackup($xmlfile,$xmlbackupfile);

    echo "\n\n";
    $input = getInput($msg);
    echo "\n";
//echo 'Userinput: ' . $input . "\n";
    $isvalid = isUserID($input);
//var_dump($isvalid);

    if($isvalid)
    {

        $ftpUserFolder = $ftpRoot . $input;

        if ((file_exists($ftpUserFolder) && is_dir($ftpUserFolder)))
        {
            echo "The directory $ftpUserFolder exists.\nPlease select another user name.\n";
        }
        else
        {
            //echo "The directory $ftpUserFolder does not exist\n";

            if(!check_user_exists($xml,$input))

            {
                echo "Adding user $input...\n";

                if (!mkdir($ftpUserFolder))
                {
                    die("Could not create directory $ftpUserFolder \n");
                }
                else
                {
                    echo "Directory $ftpUserFolder created.\n";
                }

                $password = generatePassword();
                //echo 'Password: ' . $password . "\n";

                $user = $xml->Users->addChild('User');
                $user->addAttribute('Name', $input);

                $option = $user->addChild('Option', md5($password));
                $option->addAttribute('Name', 'Pass');

                $option = $user->addChild('Option');
                $option->addAttribute('Name', 'Group');

                $option = $user->addChild('Option', '0');
                $option->addAttribute('Name', 'Bypass server userlimit');

                $option = $user->addChild('Option', '0');
                $option->addAttribute('Name', 'User Limit');

                $option = $user->addChild('Option', '0');
                $option->addAttribute('Name', 'IP Limit');

                $option = $user->addChild('Option', '1');
                $option->addAttribute('Name', 'Enabled');

                $option = $user->addChild('Option', 'none');
                $option->addAttribute('Name', 'Comments');

                $option = $user->addChild('Option', '0');
                $option->addAttribute('Name', 'ForceSsl');

                $filter = $user->addChild('IpFilter');
                $filter->addChild('Disallowed');
                $filter->addChild('Allowed');

                $permissions = $user->addChild('Permissions');
                $permission = $permissions->addChild('Permission');

                $permission->addAttribute('Dir', str_replace("/","\\",$ftpUserFolder));

                $option =  $permission->addChild('Option', '1');
                $option->addAttribute('Name', 'FileRead');

                $option =  $permission->addChild('Option', '1');
                $option->addAttribute('Name', 'FileWrite');

                $option =  $permission->addChild('Option', '0');
                $option->addAttribute('Name', 'FileDelete');

                $option =  $permission->addChild('Option', '1');
                $option->addAttribute('Name', 'FileAppend');

                $option =  $permission->addChild('Option', '1');
                $option->addAttribute('Name', 'DirCreate');

                $option =  $permission->addChild('Option', '0');
                $option->addAttribute('Name', 'DirDelete');

                $option =  $permission->addChild('Option', '1');
                $option->addAttribute('Name', 'DirList');

                $option =  $permission->addChild('Option', '1');
                $option->addAttribute('Name', 'DirSubdirs');

                $option =  $permission->addChild('Option', '1');
                $option->addAttribute('Name', 'IsHome');

                $option =  $permission->addChild('Option', '0');
                $option->addAttribute('Name', 'AutoCreate');

                $speed = $user->addChild('SpeedLimits');
            $speed->addAttribute('DlType', '1');
            $speed->addAttribute('DlLimit', '10');
            $speed->addAttribute('ServerDlLimitBypass', '0');
            $speed->addAttribute('UlType', '1');
            $speed->addAttribute('UlLimit', '10');
                $speed->addAttribute('ServerUlLimitBypass', '0');
                $speed->addChild('Download');
                $speed->addChild('Upload');

                $rv = $xml->asXML($xmlfile);
                //echo $rv . "\n";
                if(!$rv)
                {
                    die('SimpleXML could not write file');
                }


//$newentry = $xml->addChild('element', iconv('ISO-8859-1', 'UTF-8', $write));
//The DOM extension uses UTF-8 encoding. Use utf8_encode() and utf8_decode()
//to work with texts in ISO-8859-1 encoding or Iconv for other encodings.
//make human readable, parse using DOM function
//otherwise everything will be printed on one line

                if( !file_exists($xmlfile) ) die('Missing file: ' . $xmlfile);
                else
                {
                    $dom = new DOMDocument("1.0","ISO-8859-1");
                    //Setze die Flags direkt nach dem Initialisieren des Objektes:
                    $dom->preserveWhiteSpace = false;
                    $dom->formatOutput = true;

                    //$dl = @$dom->load($xmlfile); // remove error control operator (@) to print any error message generated while loading.
                    $dl = $dom->load($xmlfile); // remove error control operator (@) to print any error message generated while loading.
                    if ( !$dl ) die('Error while parsing the document: ' . $xmlfile);
                    //echo $dom->save($xmlfile) . "\n";
                    if(!$dom->save($xmlfile))
                    {
                        die('DOMDocument could not write file');
                    }
                }

//Create documentation

                $docuFile = $ftpDocumentation . $input . '.txt';
                //echo $docuFile . "\n";

                $docuString = "Username: " . $input . "\n";
                $docuString = $docuString . "Password: " . $password . "\n";
                $docuString = $docuString . "Folder: " . str_replace("/","\\",$ftpUserFolder) . "\n";
                $docuString = $docuString . "Date: " . @date("d.m.Y") . "\n";
                $docuString = $docuString . "\n";
                $docuString = $docuString . "Direct link:\n";
                $docuString = $docuString . "ftp://" . $input . ":" . $password . "@ftp.yourcompany.com";




                $handleDocuFile = fopen($docuFile, "wt");
                if(!$handleDocuFile)
                {
                    die('Could not fopen docu file');
                }

                $rv = fwrite($handleDocuFile, $docuString);
                if(!$rv)
                {
                    die('Could not fwrite docu file');
                }

                // Close xml file
                $rv = fclose($handleDocuFile);
                if(!$rv)
                {
                    die('Could not fclose docu file');
                }
                echo "Documentary file written.\n";

                $ftpExecutable = "\"C:\\Programme\\FileZilla Server\\FileZilla server.exe\" /reload-config";

                $command = $ftpExecutable;

                $last_line = system($command, $retval);

                echo ("Filezilla reloaded, user active.\n");

                echo ("Close Notepad to add another user or quit.\n");

                $command = "notepad.exe $docuFile";
                $last_line = system($command, $retval);

            }
            else
            {
                echo "Username $input already exists...\n";

            }

        }

    }
    else
    {
        echo "Username $input is invalid\n";
    }

}



function check_user_exists($xml,$username)
{
    $children=$xml->Users->children();

    foreach($children as $child)
    {
        if ($child->getName()=='User')
        {

            foreach($child->attributes() as $attributes )
            {
                if(trim($attributes) == trim($username))
                {
                    echo "Username $username already exits... \n";
                    return true;
                }

            }

        }


    }

    return false;
}




function isUserID($username)
{
    //return preg_match('/^\w{2,20}$/', $username);
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/', $username);


}

function isValid($str)
{
    //return !preg_match('/[^A-Za-z0-9.#\\-$]/', $str);
    return !preg_match('/[^A-Za-z0-9\_\-]/', $str);
}


function getInput($msg)
{
    fwrite(STDOUT, "$msg: ");
    $varin = trim(fgets(STDIN,20));
    return $varin;

    //$input = fgets($fr,128);        // read a maximum of 128 characters
}

function createXMLbackup($xmlfile,$xmlbackupfile)
{
// Copy Config for backup
$rv = copy($xmlfile,$xmlbackupfile);
if(!$rv)
{
    die('Problem creating xml backup file');
}
echo "\nBackup file created\n";
}


function generatePassword ($length = 10)
{

    // start with a blank password
    $password = "";

    $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";

    // we refer to the length of $possible a few times, so let's grab it now
    $maxlength = strlen($possible);

    // check for length overflow and truncate if necessary
    if ($length > $maxlength)
    {
        $length = $maxlength;
    }

    // set up a counter for how many characters are in the password so far
    $i = 0;

    // add random characters to $password until $length is reached
    while ($i < $length)
    {

        // pick a random character from the possible ones
        $char = substr($possible, mt_rand(0, $maxlength-1), 1);

        // have we already used this character in $password?
        if (!strstr($password, $char))
        {
            // no, so it's OK to add it onto the end of whatever we've already got...
            $password .= $char;
            // ... and increase the counter by one
            $i++;
        }

    }

    // done!
    return $password;

}

