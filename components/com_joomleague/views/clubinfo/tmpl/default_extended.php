<?php defined( '_JEXEC' ) or die( 'Restricted access' ); ?>
<!-- EXTENDED DATA-->
<?php
//check if there is data
$hasData = false;
foreach ( $this->extended->getGroups() as $group => $groups )
{
	$params = $this->extended->getElements($group);
	foreach ($params as $param)
	{
		if (!empty($param->value))
		{
			$hasData = true;
			break;
		}
	}
}
?>	
<?php
//if there is data , show it
if ($hasData)
{
?>
<h2><?php echo '&nbsp;' . JText::_( 'JL_CLUBINFO_EXTENDED' ); ?></h2>

<table>
	<?php
			foreach ( $this->extended->getGroups() as $group => $groups )
			{
				$params = $this->extended->getElements($group);
				foreach ($params as $param)
				{
					if (!empty($param->value) && !$param->backendonly)
					{
					?>
					<tr>
						<td class="label">
							<?php echo $param->label; ?>
						</td>
						<td class="data">
							<?php echo $param->value; ?>
						</td>
					</tr>
					<?php
					}
				}
			}
			?>	
</table>
<br/>
<?php
}
?>