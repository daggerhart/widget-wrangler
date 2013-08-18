<?php
/*
 * $corral - array this corral
 * $widgets - array of already themed widgets
 */
?>
<div id="corral-<?php print $corral['id']; ?>" class="corral">
	<?php foreach ($widgets as $widget){ ?>
		<?php print $widget; ?>	
	<?php } ?>
</div>