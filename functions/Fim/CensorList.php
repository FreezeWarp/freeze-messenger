<?php

namespace Fim;

class CensorList extends MagicGettersSetters
{
    /**
     * The censor list is enabled, and will be in force.
     */
    const CENSORLIST_ENABLED = 1;

    /**
     * The censor list can be disabled.
     */
    const CENSORLIST_DISABLEABLE = 2;

    /**
     * The censor list is not shown to end users when editing rooms.
     */
    const CENSORLIST_HIDDEN = 4;

    /**
     * The censor list will be deactivated in private rooms.
     */
    const CENSORLIST_PRIVATE_DISABLED = 8;

    /**
     * @var int The ID of the list.
     */
    public $id;

    /**
     * @var int The name of the list.
     */
    public $name;

    /**
     * @var int The type of the list.
     */
    public $type;

    /**
     * @var int The type of the list.
     */
    public $status;

    /**
     * @var int The list's options.
     */
    public $options;

    
    function __construct($censorData) {
        if ($censorData instanceof \Database\Result) {
            $censorData = $censorData->getAsArray(false);

            $this->id = (int) $censorData['id'] ?? new \Fim\Error('badCensorlist', 'CensorList when invoked with a Database\Result must have id column.');
            $this->name = $censorData['name'] ?? new \Fim\Error('badCensorlist', 'CensorList when invoked with a Database\Result must have name column.');
            $this->type = $censorData['type'] ?? new \Fim\Error('badCensorlist', 'CensorList when invoked with a Database\Result must have type column.');
            $this->options = (int) $censorData['options'] ?? new \Fim\Error('badCensorlist', 'CensorList when invoked with a Database\Result must have options column.');

            if ($censorData['status'])
                $this->status = $censorData['status'];
        }

        elseif ($censorData !== null) {
            throw new \Exception('Invalid message data specified -- must be an associative array corresponding to a table row. Passed: ' . print_r($censorData, true));
        }
    }

    /**
     * @see \Fim\MagicGettersSetters::get()
     */
    public function __get($property)
    {
        return parent::get($property);
    }

    public function getDisableable() : bool {
        return $this->options & self::CENSORLIST_DISABLEABLE;
    }
}