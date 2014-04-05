<?php
/**
 *  Firehose via CLI 
 *  Quickly create objects for modx
 *  Supported class names : modResource, modUser, modChunk, modSnippet, modPlugin
 *
 *  PARAMETERS
 *  --class_name which type of object are we creating. Default: modResource
 *  --count how many objects should be created? Default: 10
 *  --remove remove firehose records
 *  You can use other Object Field as a parameter like --published==1 or --hidemenu=1 or --longtitle="Sample Long Title"
 *
 * SAMPLE USAGE:
 *
 * Run the script via the command line.  The simplest invocation is to just run the 
 * script without any options: this will create 10 records for modResource Class
 *
 * 		php firehose.php
 *
 * You can supply options when you run the script
 *  
 *      php firehose.php --classname=modUser
 *
 * To set count of objects to be created, LIMIT is 200 records
 *
 *      php firehose.php --count=100
 *
 * To Delete Firehose records, will delete all firehose records
 *
 *     php firehose.php --remove
 *
 * To Delete Firehose records of specific classname
 *
 *     php firehose.php --remove --classname=modUser
 *
 * AUTHOR:
 * Daniel Edano (daniel@craftsmancoding.com)
 *
 */

// Avoid running this script on browser, this can only be run via cli
if (php_sapi_name() !== 'cli') {
    error_log('Firehose CLI script can only be executed from the command line.');
    die('CLI access only.');
}

$supported_classnames = array('modResource'=>'pagetitle','modUser'=>'username','modChunk'=>'name','modPlugin'=>'name','modSnippet'=>'name');

/**
 * Add records to the database
 * @param array $args 
 */
function add_records($args) {
    global $modx;

    for ($i=1; $i <= $args['count'] ; $i++) {  
        $obj = $modx->newObject($args['classname']);
        $obj->fromArray($args);

        switch ($args['classname']) {
            case 'modResource':
                // Set the things that need to be unique
                // and the things that must be set.
                $pagetitle = "Firehose_".uniqid();
                $obj->set('pagetitle', $pagetitle);
                $obj->set('alias', $pagetitle);
                $obj->set('content', ucfirst(generate_lorem(150)));
                break;
            case 'modChunk' :
                $obj->set('name', "Firehose_".uniqid());
                $obj->set('snippet', '<p>'.ucfirst(generate_lorem(50)).'</p>');
                break;
            case 'modSnippet':
                $obj->set('name', "Firehose_".uniqid());
                $obj->set('snippet', 'echo '."'".ucfirst(generate_lorem(20))."';");
                break;
            case 'modPlugin':
                $obj->set('name', "Firehose_".uniqid());
                $obj->set('plugincode', 'echo '."'".ucfirst(generate_lorem(20))."';");
                break;
            case 'modUser'  :
                $username = "Firehose_".uniqid();
                $profile = $modx->newObject('modUserProfile');

                $profile->set('email','test@test.com');
                $profile->fromArray($args);

                // force a value
                $obj->set('username',$username);
                $obj->set('password',$username );
                $profile->set('internalKey',0);

                $obj->addOne($profile,'Profile');
                break;
        }

        if (!$obj->save()) {
            print message("Failed to add a {$args['classname']} Record",'ERROR');
            // do not die here!  Keep going!
        }
    }
}


/**
 * Query modx records using class name and field
 *
 * @param string $classname
 * @param string $field
 * @return empty array or $object
 */
function filter_records($classname,$field='pagetitle') {
    global $modx;
    if(empty($classname)) {
        return array();
    }
    $c = $modx->newQuery($classname);
    $c->where(array(
       "$field:LIKE" => 'Firehose_%',
    ));
    return $modx->getCollection($classname,$c);
}

/**
 * generate random length of words
 *
 * @param int count
 * @return string random words
 */
function generate_lorem($count) {
    $random_words = array();
    $words = array('lorem','ipsum','dolor','sit','amet','consectetur','adipiscing','elit','curabitur','vel','hendrerit','libero','eleifend','blandit','nunc','ornare','odio','ut','orci','gravida','imperdiet','nullam','purus','lacinia','a','pretium','quis','congue','praesent','sagittis','laoreet','auctor','mauris','non','velit','eros','dictum','proin','accumsan','sapien','nec','massa','volutpat','venenatis','sed','eu','molestie','lacus','quisque','porttitor','ligula','dui','mollis','tempus','at','magna','vestibulum','turpis','ac','diam','tincidunt','id','condimentum','enim','sodales','in','hac','habitasse','platea','dictumst','aenean','neque','fusce','augue','leo','eget','semper','mattis','tortor','scelerisque','nulla','interdum','tellus','malesuada','rhoncus','porta','sem','aliquet','et','nam','suspendisse','potenti','vivamus','luctus','fringilla','erat','donec','justo','vehicula','ultricies','varius','ante','primis','faucibus','ultrices','posuere','cubilia','curae','etiam','cursus','aliquam','quam','dapibus',
        'nisl','feugiat','egestas','class','aptent','taciti','sociosqu','ad','litora','torquent','per','conubia','nostra','inceptos','himenaeos','phasellus','nibh','pulvinar','vitae','urna','iaculis','lobortis','nisi','viverra','arcu','morbi','pellentesque','metus','commodo','ut','facilisis','felis','tristique','ullamcorper','placerat','aenean','convallis','sollicitudin','integer','rutrum','duis','est','etiam','bibendum','donec','pharetra','vulputate','maecenas','mi','fermentum','consequat','suscipit','aliquam','habitant','senectus','netus','fames','quisque','euismod','curabitur','lectus','elementum','tempor','risus','cras' );

    $i = 0;
    
    for($i; $i < $count; $i++)
    {
        $index = array_rand($words);
        $word = $words[$index];
        //echo $index . '=>' . $word . '<br />';
        
        if($i > 0 && $random_words[$i - 1] == $word)
            $i--;
        else
            $random_words[$i] = $word;
    }
    return  implode(' ', $random_words);
}

/**
 * Colorize text for cleaner CLI UX. 
 * TODO: Windows compatible?
 *
 * Adapted from 
 * http://softkube.com/blog/generating-command-line-colors-with-php/
 * http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 * 
 * @param string $text
 * @param string $status
 * @return string
 */
function message($text, $status) {
    $out = '';
    switch($status) {
        case 'SUCCESS':
            $out = '[42m SUCCESS: '.chr(27).'[0;32m '; //Green background
            break;
        case 'ERROR':
            $out = '[41m ERROR: '. chr(27).'[0;31m '; //Red
            break;
        case 'WARNING':
            $out = '[43m WARNING: '; //Yellow background
            break;
        case 'INFO':
            $out = '[46m NOTE: '. chr(27).'[0;34m '; //Blue
            break;
        case 'HEADER':
            $out = '[46m '; //Blue            
            break;
        case 'HELP':
            $out = '[46m HELP: '. chr(27).'[0;34m '; //Blue
            break;
        default:
            throw new Exception('Invalid status: ' . $status);
    }
    return "\n".chr(27) . $out . $text .' '. chr(27) . '[0m'."\n\n";
}


/**
 * Parse command line arguments
 * Set params default
 * @param array $args
 * @return array
 */
function parse_args($args) {
    $overrides = array();
    foreach($args as $a) {
        if (substr($a,0,2) == '--') {
            if ($equals_sign = strpos($a,'=',2)) {
                $key = substr($a, 2, $equals_sign-2);
                $val = substr($a, $equals_sign+1);
                $overrides[$key] = $val;
            }
            else {
                $flag = substr($a, 2);
                $overrides[$flag] = true;
            }
        }
    }   

    $overrides['remove'] = !isset($overrides['remove']) ? 0 : 1;
    if(!isset($overrides['classname']) && $overrides['remove'] == 1) {
        $overrides['classname'] = '';
    } elseif(isset($overrides['classname']) && $overrides['remove'] == 1) {
        $overrides['classname'] = $overrides['classname'];
    } elseif(isset($overrides['classname'])) {
        $overrides['classname'] = $overrides['classname'];
    } else {
        $overrides['classname'] = 'modResource';
    }

    $overrides['count'] = !isset($overrides['count']) ? 10 : (int) $overrides['count'];
    return $overrides;
}


/**
 * Remove records from the database
 * @param string $classname
 */
function remove_records($classname) {

    global $modx;
    global $supported_classnames;

    // Default is to delete all records that firehose added
    // but if classname is set, then we only remove records from that table
    print 'Are you sure you want to delete all Firehose Records? (y/n) [n] > ';
    $yn = strtolower(trim(fgets(STDIN)));
    if ($yn!='y') {
        die();
    }
    if (!empty($classname) && array_key_exists($classname, $supported_classnames)) {
        $supported_classnames = array($classname=>$supported_classnames[$classname]); 
    }
    
    foreach ($supported_classnames as $classname => $field) {
        $c = $modx->newQuery($classname);
        $c->where(array(
           "$field:LIKE" => 'Firehose_%',
        ));
        $collection = $modx->getIterator($classname,$c);
        foreach ($collection as $obj) {
            if ($obj->remove() == false) {
                print message("Failed to delete a {$classname} Record with field ".$obj->get($field),'ERROR');
            }
        }
        print message("Sample {$classname} were Successfully Deleted.",'SUCCESS');
    }
    die();
}


/**
 * save object base on specified classname
 * this will also merge custom_attrs set via cli and developers default attrs
 *
 * @param array $argv
 * @param array $default_attrs
 * @param string $classname
 */
function save_obj($argv,$default_attrs=array(),$classname) {
    global $modx;
    // get extra attrs from cli
    $custom_attrs = parse_args($argv);
    // merge attrs, overrides custom by defaults
    $attrs = array_merge($custom_attrs, $default_attrs);
    
    if(!empty($classname)) {
        $obj = $modx->newObject($classname);
        $obj->fromArray($attrs);

        if (!$obj->save()) {
            print message("Failed to add a {$classname} Record",'ERROR');
            die();
        }
    }

}

/**
 * display help on cli
 */
function show_help() {

    print "
    ----------------------------------------------
    Firehose via CLI 
    ----------------------------------------------
    This Utility Quickly create objects for modx 
    Supported class name : modResource, modUser, modChunk, modSnippet, modPlugin
    ----------------------------------------------
    PARAMETERS:
    ----------------------------------------------
    --classname which type of object are we creating. Default: modResource
    --count how many objects should be created? Default: 10
    --remove remove firehose records
    --help : displays this help page.

    ----------------------------------------------
    USAGE EXAMPLES:
    * Run the script via the command line.
    ----------------------------------------------
    php ".basename(__FILE__)."

        The simplest invocation is to just run the script without any options: this will create 10 records for modResource Class

    php ".basename(__FILE__)." --classname=modUser

        You can supply --classname option when you run the script

    php ".basename(__FILE__)." --count=100

        --count set count of objects to be created, LIMIT is 200 records

    php ".basename(__FILE__)." --remove

        Use --remove to Delete Firehose records, will delete all firehose records

    php ".basename(__FILE__)." --remove --classname=modUser

        To Delete Firehose records of specific classname, add --classname

    ----------------------------------------------
    EXTRA Parameters
    ----------------------------------------------
    * You can use other Object Field as a parameter like --published==1 or --hidemenu=1 or --longtitle='Sample Long Title'
    *
    ";
}

// Find MODX...

// As long as this script is built placed inside a MODX docroot, this will sniff out
// a valid MODX_CORE_PATH.  This will effectively force the MODX_CONFIG_KEY too.
// The config key controls which config file will be loaded. 
// Syntax: {$config_key}.inc.php
// 99.9% of the time this will be "config", but it's useful when dealing with
// dev/prod pushes to have a config.inc.php and a prod.inc.php, stg.inc.php etc.
$dir = '';
if (!defined('MODX_CORE_PATH') && !defined('MODX_CONFIG_KEY')) {
    $max = 10;
    $i = 0;
    $dir = dirname(__FILE__);
    while(true) {
        if (file_exists($dir.'/config.core.php')) {
            include $dir.'/config.core.php';
            break;
        }
        $i++;
        $dir = dirname($dir);
        if ($i >= $max) {
            print message("Could not find a valid MODX config.core.php file.\n"
            ."Make sure your repo is inside a MODX webroot and try again.",'ERROR');
            die(1);
        }
    }
}


if (!defined('MODX_CORE_PATH') || !defined('MODX_CONFIG_KEY')) {    
    print message("Could not load MODX.\n"
    ."MODX_CORE_PATH or MODX_CONFIG_KEY undefined in\n"
    ."{$dir}/config.core.php",'ERROR');
    die(2);
}

if (!file_exists(MODX_CORE_PATH.'model/modx/modx.class.php')) {
    print message("modx.class.php not found at ".MODX_CORE_PATH,'ERROR');
    die(3);
}

// fire up MODX
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('mgr');

// get args from cli
$params = parse_args($argv);

// Validate the args, e.g.
if($params['count'] == 0 || $params['count'] > 200) {
     print message("--count must be > 0 or <= 200",'ERROR');
     die();
}
if ( !in_array( $params['classname'], array_keys( $supported_classnames) ) && $params['classname'] !== '') {
    print message("Unsupported classname.",'ERROR');
    die();
}


// do the action
if ($params['remove']) {
    remove_records($params['classname']);
}
else {
    add_records($params);
    print message("{$params['count']} {$params['classname']} records Created.",'SUCCESS');
}