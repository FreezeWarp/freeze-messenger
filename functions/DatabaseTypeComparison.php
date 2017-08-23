<?php
class DatabaseTypeComparison {
    const __default = self::equals;

    const notin = -4;
    const lessThan = -2;
    const lessThanEquals = -1;
    const equals = 0;
    const greaterThan = 1;
    const greaterThanEquals = 2;
    const search = 3;
    const in = 4;
    const binaryAnd = 5;

    const assignment = 1000;
}
?>