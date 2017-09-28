<?php
trait DatabaseManualInsertIDTrait {
    /**
     * @var mixed A holder for the last insert ID, since it may be unset by subsequent queries.
     */
    public $lastInsertId;

    public function incrementLastInsertId($insertId) {
        $this->lastInsertId = $insertId ?: $this->lastInsertId;
    }

    public function getLastInsertId() {
        return $this->lastInsertId;
    }
}