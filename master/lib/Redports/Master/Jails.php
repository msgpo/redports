<?php

namespace Redports\Master;

/**
 * Manage jails for various FreeBSD versions.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2015 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://freebsd.github.io/redports/
 */
class Jails
{
    protected $_db;

    public function __construct()
    {
        $this->_db = Config::getDatabaseHandle();
    }

    public function addJail($name, $data)
    {
        if ($this->_db->sAdd('jails', $name) != 1) {
            return false;
        }

        $this->_db->set('jails:'.$name, json_encode($data));

        return true;
    }

    public function getJail($name)
    {
        if (($data = $this->_db->get('jails:'.$name)) === false) {
            return false;
        }

        return json_decode($data, true);
    }

    public function getJails()
    {
        return $this->_db->sMembers('jails');
    }

    public function deleteJail($name)
    {
        $this->_db->sRemove('jails', $name);
        $this->_db->delete('jails:'.$name);

        return true;
    }

    public function exists($name)
    {
        return $this->_db->sIsMember('jails', $name);
    }
}
