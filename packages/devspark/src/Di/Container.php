<?php
namespace Devspark\Di;

/**
 * Dependencie injection container
 * 
 * @author sarrubia
 */
class Container extends \Devspark\Common\Singleton {
    
    private $_dependencies = array();
    
    public function get($key){

        return $this->_dependencies[$key];
    }
    
    public function set($key,$object){
        $this->_dependencies[$key] = $object;
    }
}
