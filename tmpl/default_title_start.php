<?php defined('_JEXEC') or die;
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.uk3tabs
 * @copyright   Copyright (C) Aleksey A. Morozov. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ($position === 'left') {
?>
<div class="uk-grid-coolapse" data-uk-grid><div class="uk-width-auto">
<?php
}
if ($position === 'right') {
?>
<div class="uk-width-auto">
<?php
}
?>
<ul class="plg_uk3tabs_titles<?php echo $tabs_class; ?>" data-uk-tab<?php echo $tabs_params; ?>>
