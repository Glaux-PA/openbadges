<?php

/**
 * @defgroup plugins_generic_openbadges OpenBadgesPlugin plugin
 */

/**
 * @file plugins/generic/openbadges/index.php
 *
 * Copyright (c) 2024+ glaux.es
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @ingroup plugins_generic_openbadges
 *
 * @brief Wrapper for OpenBadgesPlugin plugin.
 *
 */

require_once('OpenBadgesPlugin.inc.php');
return new OpenBadgesPlugin();