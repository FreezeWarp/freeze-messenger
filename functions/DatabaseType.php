<?php
class DatabaseType {
    const null = null;

    public $type;
    public $value;
    public $comparison;

    public function __construct($type, $value, $comparison) {
        /* Validation Checks */
        if ($type === DatabaseTypeType::arraylist && !($comparison === DatabaseTypeComparison::in || $comparison === DatabaseTypeComparison::notin))
            throw new Exception('Arrays can only be compared with in and notin.');
        if ($type !== DatabaseTypeType::arraylist && ($comparison === DatabaseTypeComparison::in || $comparison === DatabaseTypeComparison::notin)) {
            throw new Exception('in and notin can only be used with arrays.');
        }


        $this->type = $type;
        $this->value = $value;
        $this->comparison = $comparison;
    }
}
?>