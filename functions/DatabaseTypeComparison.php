<?php
class DatabaseTypeComparison {
    const __default = self::equals;

    const notin = 'notin';
    const lessThan = 'lt';
    const lessThanEquals = 'lte';
    const equals = 'e';
    const greaterThan = 'gt';
    const greaterThanEquals = 'gte';
    const search = 'search';
    const in = 'in';
    const binaryAnd = 'bAnd';

    const assignment = 1000;
}
?>