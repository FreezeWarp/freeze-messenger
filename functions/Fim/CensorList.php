<?php

namespace Fim;

class CensorList
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
}
