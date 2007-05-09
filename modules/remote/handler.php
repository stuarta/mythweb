<?php
/**
 * Control a MythFrontend via the telnet interface.  Be aware that this is only
 * active in the default template (i.e. requires javascript).
 *
 * @url         $URL$
 * @date        $Date$
 * @version     $Revision$
 * @author      $Author$
 *
 * @package     MythWeb
 * @subpackage  Remote
 *
/**/

// Make sure this is an array
    if (!is_array($_SESSION['remote']['frontends']))
        $_SESSION['remote']['frontends'] = array();

// Ping a frontend (via ajax) and add it to the list?  (or remove if it fails)
    if ($_REQUEST['ping']) {
        if ($Frontends[$_REQUEST['ping']]) {
            if ($Frontends[$_REQUEST['ping']]->connect(2)) {
                $loc = $Frontends[$_REQUEST['ping']]->query_location();
                $_SESSION['remote']['frontends'][$_REQUEST['ping']] = $loc;
                echo $loc;
                exit;
            }
        }
        unset($_SESSION['remote']['frontends'][$_REQUEST['ping']]);
        echo 0;
        exit;
    }
// Unping a frontend?
    elseif ($_REQUEST['unping']) {
        unset($_SESSION['remote']['frontends'][$_REQUEST['unping']]);
        echo 1;
        exit;
    }

// Use the new directory structure?
    if (empty($_REQUEST['type'])) {
        $_REQUEST['type'] = $Path[1] ? $Path[1] : $_SESSION['remote']['type'];
    }

// Unknown send type?  Use the first one found
    if (empty($_REQUEST['type']) || !array_key_exists($_REQUEST['type'], $Modules['remote']['links'])) {
        $_REQUEST['type'] = reset(array_keys($Modules['remote']['links']));
    }

// Send a command?  (via ajax)
    elseif ($_REQUEST['command']) {
        if (is_array($_SESSION['remote']['frontends']) && count($_SESSION['remote']['frontends'])) {
            foreach (array_keys($_SESSION['remote']['frontends']) as $host) {
                $frontend = $Frontends[$host];
                switch ($_REQUEST['type']) {
                    case 'keys':
                        if ($frontend->send_key($_REQUEST['command']))
                            echo "$host:1\n";
                        else
                            echo "$host:0\n";
                        break;
                    case 'jump':
                        if ($frontend->send_jump($_REQUEST['command']))
                            echo "$host:1\n";
                        else
                            echo "$host:0\n";
                        break;
                    case 'play':
                        #
                        # We actually need to do some extra processing here to deal
                        # with variations in playback control commands...
                        #
                        #$Frontend->send_play($_REQUEST['command']);
                        break;
                    case 'query':
                        $rows = $frontend->query($_REQUEST['command']);
                        if (is_array($rows)) {
                            foreach ($rows as $line) {
                                echo "$host:$line\n";
                            }
                        }
                        break;
                }
            // Host disconnected on us?
                if (!$frontend->connected)
                    unset($_SESSION['remote']['frontends'][$host]);
            }
        }
        exit;
    }

// Update the session
    $_SESSION['remote']['type'] = $_REQUEST['type'];

// Display the page
    require_once tmpl_dir.'/remote.php';